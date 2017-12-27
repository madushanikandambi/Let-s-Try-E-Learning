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
 * Report to display the copycheck report
 *
 * @package    plagiarism_copycheck
 * @copyright  2014 Solin
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');

$reportid = required_param('id', PARAM_INT);
$cmid = required_param('cmid', PARAM_INT);
$ispreviousreport = optional_param('previousreport', 0, PARAM_INT);

$cm = get_coursemodule_from_id('assign', $cmid, 0, false, MUST_EXIST);
$course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);

require_login($course, true, $cm);

$urlparameters = array('id' => $reportid, 'cmid' => $cmid);
if($ispreviousreport) $urlparameters['previousreport'] = $ispreviousreport;
$url = new moodle_url('/plagiarism/copycheck/report.php', $urlparameters);
$PAGE->set_url($url);

$PAGE->set_title(get_string('pluginname', 'plagiarism_copycheck'));
$PAGE->set_heading(get_string('copycheck_report', 'plagiarism_copycheck'));
$PAGE->navbar->add(get_string('copycheck_report', 'plagiarism_copycheck'), "");

$context = context_module::instance($cmid);
require_capability('mod/assign:grade', $context);

$copycheck = $DB->get_record('plagiarism_copycheck', array('id' => $reportid));
$user = $DB->get_record('user', array('id' => $copycheck->userid));

echo $OUTPUT->header();

echo "<h2>" . get_string('copycheck_report_title', 'plagiarism_copycheck') . fullname($user) . "</h2>\n";
echo "<iframe src='" . $copycheck->reporturl . "' height='800' width='900'></iframe>\n";
echo "<p>&nbsp;</p>\n";

if (!$ispreviousreport) {
    $params = array(
        'assignid' => $copycheck->assignid,
        'userid' => $copycheck->userid,
        'timecreated' => $copycheck->timecreated
    );
    $sql  = "SELECT * ";
    $sql .= "FROM {plagiarism_copycheck} ";
    $sql .= "WHERE assignid = :assignid ";
    $sql .= "AND userid = :userid ";
    $sql .= "AND timecreated < :timecreated ";
    $previousreports = $DB->get_records_sql($sql, $params);

    if (count($previousreports)) {
        echo "<p>" . get_string('view_previous_reports', 'plagiarism_copycheck') . "\n";

        echo "<ul>\n";
        foreach ($previousreports as $previousreport) {
            echo "<li>";
            echo date("d-m-Y H:i:s", $previousreport->timecreated) . " - ";
            echo "<a href='" . $CFG->wwwroot . "/plagiarism/copycheck/report.php?id=" . $previousreport->id . "&cmid=" . $cmid . "&previous_report=" . $copycheck->id . "'>";
            echo "[ " . get_string('view_report', 'plagiarism_copycheck') . " ]";
            echo "</a>";
            echo "</li>\n";
        }
        echo "</ul>\n";
        echo "</p>\n";
    }
} else {
    echo "<p>";
    echo "<< ";
    echo "<a href='" . $CFG->wwwroot . "/plagiarism/copycheck/report.php?id=" . $ispreviousreport . "&cmid=" . $cmid . "'>";
    echo get_string('back_current_report', 'plagiarism_copycheck');
    echo "</a>";
    echo "</p>\n";
}

echo $OUTPUT->footer();
