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

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../engagement.php');

/**
 * The viewed event class.
 *
 * @package    coursereport_discussion_metrics
 * @copyright  2020 Takahiro Nakahara <nakahara@3strings.co.jp>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class get_student_data
{

    public $data = array();

    public function __construct($students, $courseid, $forumid = NULL, $discussions, $discussionarray, $firstposts, $starttime = 0, $endtime = 0, $stale_reply_days, $engagementmethod)
    {
        global $DB;

        $countries = get_string_manager()->get_list_of_countries();

        $engagementcalculators = [];
        foreach ($discussions as $discussion) {
            $engagementcalculators[] = \report_discussion_metrics\engagement::getinstancefrommethod($engagementmethod, $discussion->id, $starttime, $endtime);
        }

        /*
        foreach($discussions as $discussion){
            $firstposts[] = $discussion->firstpost;
        }
        */
        $time_created = array();
        $all_reply = $DB->get_records_sql("SELECT * FROM {forum_posts} WHERE `parent`>0");
        $userids = array();
        foreach ($all_reply as $all_replies) {
            $userids[$all_replies->id] = $all_replies->userid;
            $time_created[$all_replies->id] = $all_replies->created;
        }
        $discussionmodcontextidlookup = report_discussion_metrics_getdiscussionmodcontextidlookup($courseid);
        foreach ($students as $student) {
            $studentdata = new studentdata();

            //Group
            $studentgroups = groups_get_all_groups($courseid, $student->id);
            $tempgroups = array();
            $studentdata->group = "";
            foreach ($studentgroups as $studentgroup) {
                $tempgroups[] = $studentgroup->name;
            }
            if ($tempgroups) $studentdata->group = implode(',', $tempgroups);
            /*
            $ingroups = array_keys($studentgroups);
            if($groupfilter){
                //echo $groupfilter;
                if(!in_array($groupfilter,$ingroups)){
                    continue;
                }
            }
            */

            $studentdata->id = $student->id;

            //Name
            //            $studentdata->fullname = fullname($student);
            $studentdata->firstname = (!isset($student->firstname) ? ',' : $student->firstname);
            $studentdata->surname = (!isset($student->lastname) ? ',' : $student->lastname);

            //Countryfullname($student);
            @$studentdata->country = $countries[$student->country];

            //Instituion
            $studentdata->institution = $student->institution;

            //Discussion
            $posteddiscussions = array();
            $studentdata->posts = 0;
            $studentdata->replies = 0;
            $studentdata->discussion = 0;
            // $studentdata->replytime = '-';
            $studentdata->maxdepth = 0;
            $studentdata->avedepth = 0;
            $studentdata->repliestoseed = 0;
            $studentdata->imagenum = 0;
            $studentdata->audionum = 0;
            $studentdata->videonum = 0;
            $studentdata->linknum = 0;
            // $sumtime = 0;
            $depthsum = 0;
            $depths = array();
            $studentdata->wordcount = 0;
            $multimedianum = 0;
            $imgnum = 0;
            $videonum = 0;
            $audionum = 0;
            $linknum = 0;
            $levels = array(0, 0, 0, 0);
            $studentdata->participants = 0;
            $studentdata->multinationals = 0;
            // $replytimearr = array();
            $foravedepth = array();
            $studentdata->self_reply = 0;
            $studentdata->stale_reply = 0;
            $allpostssql = 'SELECT * FROM {forum_posts} WHERE userid=' . $student->id . ' AND discussion IN ' . $discussionarray;
            if ($starttime) {
                $allpostssql = $allpostssql . ' AND created>' . $starttime;
            }
            if ($endtime) {
                $allpostssql = $allpostssql . ' AND created<' . $endtime;
            }
            if ($allposts = $DB->get_records_sql($allpostssql)) {
                foreach ($allposts as $post) {
                    @$posteddiscussions[$post->discussion]++; //どのディスカッションに何回返信したかを使う時が来るか？
                    if ($post->parent == 0) {
                        $studentdata->posts++;
                    } elseif ($post->parent > 0) {
                        if (array_key_exists($post->parent, $time_created)) {
                            if ($userids[$post->parent] == $student->id) {
                                $studentdata->self_reply++;
                            } else if (strtotime('-' . $stale_reply_days . 'days', $post->created) > ($time_created[$post->parent])) {
                                $studentdata->stale_reply++;
                            }
                        }
                        // if (isset($userids[$post->parent])) {

                        // }
                        if (in_array($post->parent, $firstposts)) {
                            $studentdata->repliestoseed++;
                        }
                        // if ($parentdata = $DB->get_record('forum_posts', array('id' => $post->parent))) {
                        //     $sumtime = $sumtime + ($post->created - $parentdata->created); //for average
                        //     $replytimearr[] = $post->created - $parentdata->created; //for median
                        // }
                        $studentdata->replies++;

                        //Depth

                    }

                    $wordnum = count_words($post->message);
                    $studentdata->wordcount += $wordnum;
                    if ($multimediaobj = get_mulutimedia_num($post->message)) {
                        $multimedianum += $multimediaobj->num;
                        $imgnum += $multimediaobj->img;
                        $videonum += $multimediaobj->video;
                        $audionum += $multimediaobj->audio;
                        $linknum += $multimediaobj->link;
                    }
                    $mediaattachments = report_discussion_metrics_countattachmentmultimedia($discussionmodcontextidlookup[$post->discussion], $post->id);
                    $multimedianum += $mediaattachments->num;
                    $imgnum += $mediaattachments->img;
                    $videonum += $mediaattachments->video;
                    $audionum += $mediaattachments->audio;
                    $linknum += $mediaattachments->link;
                }
                $direct_replies = $studentdata->repliestoseed;
                if($studentdata->maxdepth == 0 && $direct_replies != 0)
                {
                    $studentdata->maxdepth = 1;
                }

                if ($foravedepth || $direct_replies)
                { 
                    $studentdata->avedepth = round(((array_sum($foravedepth)+$direct_replies)/ (count($foravedepth)+$direct_replies)), 3);
                }
                $studentdata->discussion = count($posteddiscussions);
                    /*
                    if($sumtime){
                        $dif = ceil($sumtime/$studentdata->replies);
                        $dif_time = gmdate("H:i:s", $dif);
                        $dif_days = (strtotime(date("Y-m-d", $dif)) - strtotime("1970-01-01")) / 86400;
                        $studentdata->replytime =  "{$dif_days}days<br>$dif_time";
                    }
                    */
                    // if ($studentdata->replies == 1) {
                    //     $studentdata->replytime = discussion_metrics_format_time($replytimearr[0]);
                    // } elseif ($studentdata->replies == 2) {
                    //     $studentdata->replytime = discussion_metrics_format_time(($replytimearr[1] + $replytimearr[0]) / 2);
                    // } elseif ($studentdata->replies > 2) {
                    //     sort($replytimearr);
                    //     $middleval = floor(($studentdata->posts) / 2);
                    //     if ($studentdata->replies % 2) {
                    //         $studentdata->replytime = discussion_metrics_format_time($replytimearr[$middleval]);
                    //     } else {
                    //         $studentdata->replytime = discussion_metrics_format_time(($replytimearr[$middleval] + $replytimearr[$middleval + 1]) / 2);
                    //     }
                    // }
                    //if($studentdata->maxdepth) $studentdata->avedepth = $depthsum/$threads;
                    //$studentdata->threads = $threads;
                    //対話した相手の人数と国籍
                    list($discsin, $discsparam) = $DB->get_in_or_equal(array_keys($posteddiscussions));
                    $discswhere = "userid <> ? AND discussion {$discsin}";
                    $dparam = ['studentid' => $student->id];
                    $dparam += $discsparam;
                    if ($participants = $DB->get_fieldset_select('forum_posts', 'DISTINCT userid', $discswhere, $dparam)) {
                        $studentdata->participants = count($participants);
                        list($partin, $partparam) = $DB->get_in_or_equal($participants);
                        $countrywhere = "id {$partin}";
                        $countryids = $DB->get_fieldset_select('user', 'DISTINCT country', $countrywhere, $partparam);
                        $studentdata->multinationals = count($countryids);
                    }
            } else {
                $studentdata->discussion = 0;
            }

            $engagement = new \report_discussion_metrics\engagementresult();
            foreach ($engagementcalculators as $engagementcalculator) {
                $engagement->add($engagementcalculator->calculate($studentdata->id));
            }

            //View
            $logtable = 'logstore_standard_log';
            $eventname = '\\\\mod_forum\\\\event\\\\discussion_viewed';
            if ($forumid) {
                $cm = get_coursemodule_from_instance('forum', $forumid, $courseid, false, MUST_EXIST);
                $viewsql = "SELECT * FROM {logstore_standard_log} WHERE userid=$student->id AND contextinstanceid=$cm->id AND contextlevel=" . CONTEXT_MODULE . " AND eventname='$eventname'";
            } else {
                $views = $DB->get_records($logtable, array('userid' => $student->id, 'courseid' => $courseid, 'eventname' => $eventname));
                $viewsql = "SELECT * FROM {logstore_standard_log} WHERE userid=$student->id AND courseid=$courseid AND eventname='$eventname'";
            }
            if ($starttime) {
                $viewsql = $viewsql . ' AND timecreated>' . $starttime;
            }
            if ($endtime) {
                $viewsql = $viewsql . ' AND timecreated<' . $endtime;
            }
            $views = $DB->get_records_sql($viewsql);
            $studentdata->views = count($views);

            $studentdata->multimedia = $multimedianum;
            $studentdata->imagenum = $imgnum;
            $studentdata->audionum = $audionum;
            $studentdata->videonum = $videonum;
            $studentdata->linknum = $linknum;
            $studentdata->levels = $engagement->levels;
            $studentdata->l1 = $engagement->getl1();;
            $studentdata->l2 = $engagement->getl2();;
            $studentdata->l3 = $engagement->getl3();;
            $studentdata->l4 = $engagement->getl4up();;
            $studentdata->maxdepth = $engagement->getmax();
            $studentdata->avedepth = $engagement->getaverage();

            //First post & Last post
            $firstpostsql = 'SELECT MIN(created) FROM {forum_posts} WHERE userid=' . $student->id . ' AND discussion IN ' . $discussionarray;
            if ($allposts) {

                $firstpostsql = 'SELECT MIN(created) FROM {forum_posts} WHERE userid=' . $student->id . ' AND discussion IN ' . $discussionarray;
                if ($starttime) {
                    $firstpostsql = $firstpostsql . ' AND created>' . $starttime;
                }
                if ($endtime) {
                    $firstpostsql = $firstpostsql . ' AND created<' . $endtime;
                }
                $firstpost = $DB->get_record_sql($firstpostsql);
                $minstr = 'min(created)'; //
                $firstpostdate = userdate($firstpost->$minstr);
                $studentdata->firstpost = $firstpostdate;


                $lastpostsql = 'SELECT MAX(created) FROM {forum_posts} WHERE userid=' . $student->id . ' AND discussion IN ' . $discussionarray;
                if ($starttime) {
                    $lastpostsql = $lastpostsql . ' AND created>' . $starttime;
                }
                if ($endtime) {
                    $lastpostsql = $lastpostsql . ' AND created<' . $endtime;
                }
                $lastpost = $DB->get_record_sql($lastpostsql);
                $maxstr = 'max(created)'; //
                $lastpostdate = userdate($lastpost->$maxstr);
                $studentdata->lastpost = $lastpostdate;
            } else {
                $studentdata->firstpost = '-';
                $studentdata->lastpost = '-';
            }
            $this->data[] = $studentdata;
        }
    }
}

class studentdata
{
    public $id;
    public $fullname;
    public $firstname;
    public $surname;
    public $country;
    public $institution;
    public $group;
    public $posts;
    public $replies = 0;
    public $discussion = 0;
    public $maxdepth = 0;
    public $avedepth = 0;
    public $threads = 0;
    public $views = 0;
    public $wordcount = 0;
    public $participants = 0;
    public $multinationals = 0;
    public $self_reply = 0;
    public $stale_reply = 0;
    public $multimedia = 0;
    public $imagenum = 0;
    public $videonum = 0;
    public $linknum = 0;
    public $audionum = 0;
    public $density = 0;
    // public $replytime = 0;
    public $repliestoseed = 0;
    public $firstpost;
    public $lastpost;
    public $levels;
    public $l1;
    public $l2;
    public $l3;
    public $l4;
}
