<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Plugin event classes are defined here.
 *
 * @package     coursereport_discussion_metrics
 * @copyright   2020 Takahiro Nakahara <nakahara@3strings.co.jp>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace report_discussion_metrics\select;

use engagement;
use engagementresult;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../engagement.php');

/**
 * The viewed event class.
 *
 * @package    coursereport_discussion_metrics
 * @copyright  2020 Takahiro Nakahara <nakahara@3strings.co.jp>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class get_dialogue_data {
    
    public $data = array();
    
    public function __construct($courseid,$discussions,$groups=NULL,$starttime=0,$endtime=0, $engagementmethod){
        global $DB;
        if (!isset($countries)) {
            $countries = [];
        }
        if(!$groups){
            $groups = groups_get_all_groups($courseid);
        }
        $discussionmodcontextidlookup = report_discussion_metrics_getdiscussionmodcontextidlookup($courseid);
        $engagementcalculators = [];
        foreach ($discussions as $discussion) {
            $engagementcalculators[$discussion->id] = engagement::getinstancefrommethod($engagementmethod, $discussion->id, $starttime, $endtime);
        }
        foreach($groups as $group){
            if(!$groupusers = groups_get_members($group->id, 'u.id', 'u.id ASC')){
                continue;
            }
            $wheregroupusers = '(';
            $groupusernum = count($groupusers);
            foreach($groupusers as $guser){
                $student = $DB->get_record('user',array('id'=>$guser->id));
                @$countries[$student->country]++;
                $wheregroupusers .= $student->id.',';
            }
            $wheregroupusers .= '0)';
            $countrynum = count($countries);
            foreach($discussions as $discussion){
                $threads = 0;
                $firstpostdata = $DB->get_record('forum_posts',array('id'=>$discussion->firstpost));
                $firstpost = $firstpostdata->created;
                $lastpost = 0;
                $replytimearr = array();
                $depthsum = 0;
                $replies = 0;
                $bereplied = 0;
                $levels = array(0,0,0,0);
                $dialoguedata = new dialoguedata();
                $forum = $DB->get_record('forum',array('id'=>$discussion->forum));
                $dialoguedata->forumname = $forum->name;
                $dialoguedata->name = $discussion->name;
                $discswhere = "discussion=?";
                $dparam = ['discussionid'=>$discussion->id];
                if($participants = $DB->get_fieldset_select('forum_posts', 'DISTINCT userid', $discswhere,$dparam)){
                    $dialoguedata->participants += count($participants);
                    list($partin,$partparam) = $DB->get_in_or_equal($participants);
                    $countrywhere = "id {$partin}";
                    $countryids = $DB->get_fieldset_select('user', 'DISTINCT country', $countrywhere,$partparam);
                    $dialoguedata->multinationals += count($countryids);
                }
                $postssql = 'SELECT * FROM {forum_posts} WHERE userid IN '.$wheregroupusers.' AND discussion = '.$discussion->id. " AND id <> ".$discussion->firstpost;
                if($starttime){
                    $postssql = $postssql.' AND created>'.$starttime;
                }
                if($endtime){
                    $postssql = $postssql.' AND created<'.$endtime;
                }
                $posts = $DB->get_records_sql($postssql);
                $dialoguedata->posts = count($posts);
                foreach($posts as $post){
                    //Word count
                    $dialoguedata->wordcount += count_words($post->message);
                    //Multimedia
                    if($multimediaobj = get_mulutimedia_num($post->message)){
                        $dialoguedata->multimedia += $multimediaobj->num;
                    }
                    $mediaattachments = report_discussion_metrics_countattachmentmultimedia($discussionmodcontextidlookup[$post->discussion], $post->id);
                    $dialoguedata->multimedia += $mediaattachments->num;
                    //Be replied
                    if($DB->get_records('forum_posts',array('parent'=>$post->id))){
                        $bereplied++;
                    }
                    //Depth
                    $parent = $post->parent;
                    if($parent == $discussion->firstpost) $threads++;

                    //TempTimes
                    //if($firstpost > $post->created) $firstpost = $post->created;
                    if($lastpost < $post->created) $lastpost = $post->created;
                    $replytimearr[] = $post->created;
                }

                $engagementresult = new engagementresult();
                foreach ($groupusers as $user) {
                    $engagementresult->add($engagementcalculators[$discussion->id]->calculate($user->id));
                }

                //if($dialoguedata->maxdepth) $dialoguedata->avedepth = $depthsum/$threads;
                $dialoguedata->threads = $threads;
                $dialoguedata->threadsperstudent = $threads/$groupusernum;
                $dialoguedata->threadspercountry = $threads/$countrynum;
                $dialoguedata->levels = $levels;
                $dialoguedata->l1 = $engagementresult->getl1();
                $dialoguedata->l2 = $engagementresult->getl2();
                $dialoguedata->l3 = $engagementresult->getl3();
                $dialoguedata->l4 = $engagementresult->getl4up();
                $dialoguedata->maxdepth = $engagementresult->getmax();
                $dialoguedata->avedepth = $engagementresult->getaverage();
                $dialoguedata->bereplied = $bereplied;
                $dialoguedata->groupname = $group->name;

                //Median replytime
                if($dialoguedata->posts ==1){
                    $dialoguedata->replytime = discussion_metrics_format_time($lastpost - $firstpost);
                }elseif($dialoguedata->posts > 1){
                    sort($replytimearr);
                    $middleval = floor(($dialoguedata->posts)/2);
                    if($dialoguedata->posts % 2){
                        $dialoguedata->replytime = discussion_metrics_format_time($replytimearr[$middleval-1] - $firstpost);
                    }else{
                        $dialoguedata->replytime = discussion_metrics_format_time(($replytimearr[$middleval-1] + $replytimearr[$middleval])/2 - $firstpost);
                    }
                }

                if($dialoguedata->posts){
                    //Density of discussion
                    $dialoguedata->density = discussion_metrics_format_time(($lastpost-$firstpost)/$dialoguedata->posts);
                    $this->data[] = $dialoguedata;
                }
            }
        }
    }
}

class dialoguedata{
    
    public $groupname;
    public $forumname;
    public $name;
    public $posts;
    public $replies = 0;
    public $maxdepth = 0;
    public $avedepth = 0;
    public $threads = 0;
    public $threadsperstudent = 0;
    public $threadspercountry = 0;
    public $levels = 0;
    public $views = 0;
    public $wordcount = 0;
    public $participants = 0;
    public $multinationals = 0;
    public $multimedia = 0;
    public $density = 0;
    public $replytime = 0;
    public $bereplied = 0;
    public $l1;
    public $l2;
    public $l3;
    public $l4;
}
