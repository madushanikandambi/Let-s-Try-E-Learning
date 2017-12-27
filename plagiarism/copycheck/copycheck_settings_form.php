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
 * This is a class which creates the form for copycheck
 *
 * @package    plagiarism_copycheck
 * @copyright  2014 Solin
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/lib/formslib.php');

/**
 * Class copycheck_settings_form extended from moodleform for form
 *
 * @copyright  2014 Solin
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class copycheck_settings_form extends moodleform {

    /**
     * Function for form definition
     */
    public function definition() {
        $mform =& $this->_form;

        // Use of the plugin.
        $mform->addElement('checkbox', 'copycheck_use', get_string('copycheck_use', 'plagiarism_copycheck'));
        $mform->addHelpButton('copycheck_use', 'copycheck_use', 'plagiarism_copycheck');

        $mform->addElement('text', 'clientcode', get_string('clientcode', 'plagiarism_copycheck'));
        $mform->setType('clientcode', PARAM_RAW);
		$mform->addRule('clientcode', get_string('error_clientcode', 'plagiarism_copycheck'), 'regex', '/[A-Za-z]{2,10}-[0-9]{4}/');
		$mform->addRule('clientcode', get_string('error_clientcode', 'plagiarism_copycheck'), 'maxlength', '15');

        $this->add_action_buttons(true);
    }
}