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
 * Plugin strings are defined here.
 *
 * @package     coursereport_discussion_metrics
 * @category    string
 * @copyright   2020 Takahiro Nakahara <nakahara@3strings.co.jp>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['discussion_metrics:view'] = 'view';
$string['pluginname'] = 'Discussion Metrics';
$string['selectforum'] = 'Select forum';
$string['alloncourse'] = 'All on this course';
$string['replies'] = 'Total replies';
$string['firstpost'] = 'First Post';
$string['lastpost'] = 'Last Post';
$string['sendreminder'] = 'Send reminder';
$string['completereport'] = 'Complete report';
$string['views'] = 'Views';
$string['wordcount'] = 'Word count';
$string['reportfilter'] = 'Report filter';
$string['showreport'] = 'Show report';
$string['multimedia'] = 'Multimedia';
$string['reportstart'] = 'Start';
$string['reportend'] = 'End';
$string['reportfilter'] = 'Report filter';
$string['onlygroupworks'] = 'Specific to this group';
$string['international'] = 'Total international replies';
$string['domestic'] = "Total domestic replies";
$string['self'] = "Total replies to self";
$string['stale'] = "Total stale replies";
$string['stale_days'] = "Days for stale reply";
$string['audio'] = "#audio";
$string['image'] = "#image";
$string['link'] = "#link";
$string['video'] = "#video";
$string['total_reply'] = "Topic Replies";
$string['direct_reply'] = "Direct replies to a new discussion post";
//Reminder mail
$string['remindsubject'] = 'Reminder to participate in the international exchange';
$string['remindmessage'] = 'We\'ve noticed you haven\'t been participating in the international online exchange. Please log in and reply to others using the forums. Good luck!';
$string['sentreminder'] = 'Sent a reminder.';

//log
$string['eventreportviewed'] = 'Discussion Metrics viewed';

$string['engagement_persontoperson'] = 'Person-to-Person Engagement';
$string['engagement_persontoperson_description'] = 'The engagement level increases each time a user replies to the same user in the same thread.';
$string['engagement_threadtotalcount'] = 'Thread Total Count Engagement';
$string['engagement_threadtotalcount_description'] = 'The engagement level increases each time a user participate in the same thread.';
$string['engagement_threadengagement'] = 'Thread Engagement';
$string['engagement_threadengagement_description'] = 'The engagement level increases each time a user participates in a reply where they already participated in the parent posts.';
