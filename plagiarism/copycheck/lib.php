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
 * The library of copycheck
 *
 * @package    plagiarism_copycheck
 * @copyright  2014 Solin
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot.'/plagiarism/lib.php');

/**
 * Class plagiarism_plugin_copycheck for functions copycheck
 *
 * @copyright  2014 Solin
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class plagiarism_plugin_copycheck extends plagiarism_plugin {

    /**
     * Return the list of form element names.
     *
     * @return array contains the form element names.
     */
    public function get_configs() {
        return array("copycheck_use", "clientcode");
    }

    /**
     * hook to allow plagiarism specific information to be displayed beside a submission
     *
     * @param array  $linkarray contains all relevant information for the plugin to generate a link
     * @return string
     *
     */
    public function get_links($linkarray) {
        global $DB, $CFG;

        $params = array(
            'cmid' => $linkarray['cmid'],
            'userid' => $linkarray['userid'],
        );

		if (isset($linkarray['assignment']) || isset($linkarray['file'])) {

			$context = context_module::instance($linkarray['cmid']);
			if (has_capability('mod/assign:grade', $context)) {
				$sql  = "SELECT id ";
				$sql .= "FROM {plagiarism_copycheck} ";
				$sql .= "WHERE userid = :userid ";

				if (isset($linkarray['file'])) {
					$params['fileid' ] =  $linkarray['file']->get_id();
					$sql .= "AND filetype = 'file' ";
					$sql .= "AND fileid = :fileid ";
					$sql .= "AND reporturl IS NOT NULL ";
				} else if (isset($linkarray['content'])) {
					if (trim($linkarray['content']) == "") return;
					$params['assignment'] =  $linkarray['assignment'];

					$sql .= "AND filetype = 'onlinetext' ";
					$sql .= "AND assignid = :assignment ";
					$sql .= "AND reporturl IS NOT NULL ";
					$sql .= "ORDER BY timecreated DESC ";
					$sql .= "LIMIT 1 ";
				}

				$reportid = $DB->get_field_sql($sql, $params);

				if ($reportid) {
					$returnstring = "<br />";
					// The a link tag with url.
					$returnstring .= "<a href='";
					$returnstring .= $CFG->wwwroot . "/plagiarism/copycheck/report.php";
					$returnstring .= "?id=" . $reportid;
					$returnstring .= "&cmid=" . $linkarray['cmid'];
					$returnstring .= "'>";
					// The string.
					$returnstring .= "[ ";
					$returnstring .= get_string('view_report', 'plagiarism_copycheck');
					$returnstring .= " ]";
					// Close the link
					$returnstring .= "</a>";
					return $returnstring;
				}
			}
		}
		
		return "";
    }

    /**
     * hook to add plagiarism specific settings to a module settings page
     *
     * @param object $mform  - Moodle form
     * @param object $context - current context
     * @param string $modulename - Name of the module
     */
    public function get_form_elements_module($mform, $context, $modulename = "") {
        global $DB;

        // Only with the assign module.
        if ($modulename != 'mod_assign') return;

        $configsettings = get_config('plagiarism_copycheck');

        if (!isset($configsettings->copycheck_use)) return;
        if (!isset($configsettings->clientcode)) return;

        if ($configsettings->copycheck_use && trim($configsettings->clientcode) != "") {
            $checked = 0;
            $cmid = optional_param('update', 0, PARAM_INT);

            if ($cmid) {
                $sql  = "SELECT pca.enabled ";
                $sql .= "FROM {course_modules} cm ";
                $sql .= "JOIN {plagiarism_copycheck_assign} pca ON cm.instance = pca.assignid ";
                $sql .= "WHERE cm.id = :cmid ";
                $checked = $DB->get_field_sql($sql, array('cmid' => $cmid));
            }

            $mform->addElement('header', 'copycheck', get_string('pluginname', 'plagiarism_copycheck'));
            $mform->addElement('checkbox', 'copycheck_use', get_string('copycheck_use', 'plagiarism_copycheck'));
            $mform->setDefault('copycheck_use', $checked);
        }
    }


    /**
     * hook to save plagiarism specific settings on a module settings page
     *
     * @param object $data - data from an mform submission.
     */
    public function save_form_elements($data) {
        global $DB;

        $configsettings = get_config('plagiarism_copycheck');
		
		if ($configsettings->copycheck_use) {
			$currentassignmentconfig = $DB->get_record('plagiarism_copycheck_assign', array('assignid' => $data->instance));

			$record = new stdClass();
			$record->assignid = $data->instance;

			if (isset($data->copycheck_use)) $record->enabled = $data->copycheck_use;
			else                             $record->enabled = 0;

			if ($currentassignmentconfig) {
				$record->id = $currentassignmentconfig->id;
				$DB->update_record('plagiarism_copycheck_assign', $record);
			} else {
				$DB->insert_record('plagiarism_copycheck_assign', $record);
			}
		}
    }


    /**
     * cron function
     */
    public function cron() {
    }

}
