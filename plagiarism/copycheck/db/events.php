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
 * Events for copycheck
 *
 * @package    plagiarism_copycheck
 * @copyright  2014 Solin
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$observers = array(
    array(
        'eventname'   => 'assignsubmission_file\event\submission_created',
        'callback'    => 'plagiarism_plugin_copycheck_submissions::check_and_send_submission_file_to_copycheck',
        'includefile' => 'plagiarism/copycheck/copycheck_submissions.php',
        'priority'    => 9999
    ),
    array(
        'eventname'   => 'assignsubmission_file\event\submission_updated',
        'callback'    => 'plagiarism_plugin_copycheck_submissions::check_and_send_submission_file_to_copycheck',
        'includefile' => 'plagiarism/copycheck/copycheck_submissions.php',
        'priority'    => 9999
    ),
    array(
        'eventname'   => 'assignsubmission_onlinetext\event\submission_created',
        'callback'    => 'plagiarism_plugin_copycheck_submissions::check_and_send_submission_text_to_copycheck',
        'includefile' => 'plagiarism/copycheck/copycheck_submissions.php',
        'priority'    => 9999
    ),
    array(
        'eventname'   => 'assignsubmission_onlinetext\event\submission_updated',
        'callback'    => 'plagiarism_plugin_copycheck_submissions::check_and_send_submission_text_to_copycheck',
        'includefile' => 'plagiarism/copycheck/copycheck_submissions.php',
        'priority'    => 9999
    )
);
