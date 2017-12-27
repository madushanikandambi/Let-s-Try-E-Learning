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
 * This is a class which handles the submissions and send them to copycheck
 *
 * @package    plagiarism_copycheck
 * @copyright  2014 Solin
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Class plagiarism_plugin_copycheck_submissions for functions copycheck
 *
 * @copyright  2014 Solin
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class plagiarism_plugin_copycheck_submissions {

    /**
     * Function to check and send the submissions to copycheck
     *
     * @param      object $event the full event object
     */
    public static function check_and_send_submission_file_to_copycheck($event) {
        global $DB, $CFG;

        $copycheckconfig = get_config('plagiarism_copycheck');

        if (isset($copycheckconfig->copycheck_use) && $copycheckconfig->copycheck_use) {
            $submissionid = $event->objectid;
            $userid = $event->userid;
            $contextid = $event->contextid;

            if ($assignid = $DB->get_field('assignsubmission_file', 'assignment', array('id' => $submissionid))) {
                if ($assignmentcopycheck = $DB->get_field('plagiarism_copycheck_assign', 'enabled', array('assignid' => $assignid))) {
                    require_once($CFG->dirroot . '/mod/assign/locallib.php');

                    $params = array('contextid' => $contextid, 'userid' => $userid);
                    $sql  = "SELECT * FROM {files} ";
                    $sql .= "WHERE filename != '.' ";
                    $sql .= "AND contextid= :contextid ";
                    $sql .= "AND userid = :userid ";
                    $fileinfos = $DB->get_records_sql($sql, $params);

                    foreach ($fileinfos as $fileinfo) {
                        $currentcopycheckrecord = $DB->get_record('plagiarism_copycheck', array('assignid' => $assignid,
                                                                                                  'userid' => $userid,
                                                                                                  'fileid' => $fileinfo->id,
                                                                                                  'filetype' => 'file'));

                        if (!$currentcopycheckrecord) {
                            // Set the soapclient.
                            if (!isset($soapclient)) $soapclient = self::get_soap_client();

                            $fileext = "." . pathinfo($fileinfo->filename, PATHINFO_EXTENSION);

                            if (self::check_file_extension($soapclient, $fileext)) {
                                $guid = self::NewGuid();

                                // Get the content of the file.
                                $content = "";
                                $fs = get_file_storage();
                                $file = $fs->get_file($fileinfo->contextid,
                                                      $fileinfo->component,
                                                      $fileinfo->filearea,
                                                      $fileinfo->itemid,
                                                      $fileinfo->filepath,
                                                      $fileinfo->filename);

                                if ($file) $content = $file->get_content();

                                $xml = self::get_copycheck_xml_template($guid, $fileinfo->filename, $assignid);

                                $clientcode = $copycheckconfig->clientcode;

                                $parameters = array("guidStr" => $guid, "docFileBytes" => $content, "xmlFileBytes" => $xml, "klantcode" => $clientcode);

                                // Make the soap call.
                                $soaprequest = $soapclient->submitDocumentMoodle($parameters);

                                // Insert information in copycheck database.
                                $record = new stdClass();
                                $record->assignid = $assignid;
                                $record->userid = $userid;
                                $record->filetype = "file";
                                $record->fileid = $fileinfo->id;
                                $record->guid = $guid;
                                $record->timecreated = time();

                                $newrecordid = $DB->insert_record('plagiarism_copycheck', $record);

                                // Get the report url.
                                $reporturl = $soaprequest->submitDocumentMoodleResult;

                                // Update report url in copycheck database.
                                $updaterecord = new stdClass();
                                $updaterecord->id = $newrecordid;
                                $updaterecord->reporturl = $reporturl;

                                $DB->update_record('plagiarism_copycheck', $updaterecord);
                            }
                        }
                    }
                }
            }
        }
    }


    /**
     * Function to check and send a text submission to copycheck
     *
     * @param      object $event the full event object
     */
    public static function check_and_send_submission_text_to_copycheck($event) {
        global $DB, $CFG;

        $copycheckconfig = get_config('plagiarism_copycheck');

        if (isset($copycheckconfig->copycheck_use) && $copycheckconfig->copycheck_use) {
            $submissionid = $event->objectid;
            $userid = $event->userid;

            if ($assignsubmission = $DB->get_record('assignsubmission_onlinetext', array('id' => $submissionid))) {
                if ($assignmentcopycheck = $DB->get_field('plagiarism_copycheck_assign', 'enabled',
                                                               array('assignid' => $assignsubmission->assignment))) {
                    // Set the soapclient.
                    $soapclient = self::get_soap_client();

                    $guid = self::NewGuid();

                    $filename = $userid . "_" . $submissionid . ".html";

                    $xml = self::get_copycheck_xml_template($guid, $filename, $assignsubmission->assignment);

                    $clientcode = $copycheckconfig->clientcode;

                    $parameters = array("guidStr" => $guid,
                                        "docFileBytes" => $assignsubmission->onlinetext,
                                        "xmlFileBytes" => $xml,
                                        "klantcode" => $clientcode);

                    // Make the soap call.
                    $soaprequest = $soapclient->submitDocumentMoodle($parameters);

                    // Insert information in copycheck database.
                    $record = new stdClass();
                    $record->assignid = $assignsubmission->assignment;
                    $record->userid = $userid;
                    $record->filetype = "onlinetext";
                    $record->fileid = $assignsubmission->id;
                    $record->guid = $guid;
                    $record->timecreated = time();

                    $newrecordid = $DB->insert_record('plagiarism_copycheck', $record);

                    // Get the report url.
                    $reporturl = $soaprequest->submitDocumentMoodleResult;

                    // Update report url in copycheck database.
                    $updaterecord = new stdClass();
                    $updaterecord->id = $newrecordid;
                    $updaterecord->reporturl = $reporturl;

                    $DB->update_record('plagiarism_copycheck', $updaterecord);
                }
            }
        }
    }


    /**
     * Function to create a new guid for copycheck
     *
     * @return     string $guidText the guid string
     */
    public static function NewGuid() {
        $s = strtolower(md5(uniqid(rand(), true)));
        $guidtext = substr($s, 0, 8) . '-' . substr($s, 8, 4) . '-' . substr($s, 12, 4). '-' . substr($s, 16, 4). '-' . substr($s, 20);

        return $guidtext;
    }


    /**
     * Function to create an xml which is send to copycheck
     *
     * @param      string $guid the guid string
     * @param      string $filename the filename
     * @param      int $assignid the assign id in the database
     * @return     string $xml the full xml string
     */
    public static function get_copycheck_xml_template($guid, $filename, $assignid) {
        global $DB, $USER;

        $copycheckconfig = get_config('plagiarism_copycheck');
        $clientcode = $copycheckconfig->clientcode;

        $admin = current(get_admins());

        $assignment = $DB->get_record('assign', array('id' => $assignid), 'id, name, duedate');

        $duedate = "";
        if ($assignment->duedate > 0) $duedate = date("YmdHi", $assignment->duedate);

        $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>
        <CopyCheck>
          <didptr></didptr>
          <computername></computername>
          <client>moodle</client>
          <taal></taal>
          <managername></managername>
          <servername>3.4</servername>
          <erroremailadres></erroremailadres>
          <klantcode>" . $clientcode . "</klantcode>
          <wachtwoord></wachtwoord>
          <naaminstelling></naaminstelling>
          <projectcode></projectcode>
          <guid>" . $guid . "</guid>
          <documenttitle>" . $filename . "</documenttitle>
          <hl></hl>
          <lastWriteTicks></lastWriteTicks>
          <lengte></lengte>
          <dirdocument></dirdocument>
          <orgdirdocument></orgdirdocument>
          <fullname></fullname>
          <suffix></suffix>
          <language></language>
          <subject>" . $assignment->name . "</subject>
          <woordenopslaan></woordenopslaan>
          <maakimage></maakimage>
          <kijkincopycheckdb></kijkincopycheckdb>
          <kijkophetinternet></kijkophetinternet>
          <maakrapportage></maakrapportage>
          <documentopslaan></documentopslaan>
          <orgperc></orgperc>
          <maxrapsize></maxrapsize>
          <stuuremail></stuuremail>
          <emailadres>" . $admin->email . "</emailadres>
          <emailgrens></emailgrens>
          <submitdatum></submitdatum>
          <submittijd></submittijd>
          <submitted></submitted>
          <negeer></negeer>
          <reporturl></reporturl>
          <klas></klas>
          <studentnummer></studentnummer>
          <studentnaam></studentnaam>
          <studentemailadres>" . $USER->email . "</studentemailadres>
          <orgperc></orgperc>
          <statuscode></statuscode>
          <statusdescription></statusdescription>
          <reportformat></reportformat>
          <skipauthortitle></skipauthortitle>
          <skipsametitle></skipsametitle>
          <reportlanguage></reportlanguage>
          <closing>" . $duedate . "</closing>
        </CopyCheck>";

        return utf8_encode($xml);
    }


    /**
     * Function to instantiate the soap client for the call
     *
     * @return     object $soapclient the soap client
     */
    public static function get_soap_client() {
        $soapclient = new SoapClient("https://copycheck.nl/CCservices.asmx?wsdl", array("trace" => 1, "exceptions" => 0));

        return $soapclient;
    }


    /**
     * Function to check if the file extension is supported
     *
     * @param      object $soapclient the current active soap client
     * @param      string $ext the extension of the file
     * @return     bool $validfile wheter the extension is valid or not
     */
    public static function check_file_extension($soapclient, $ext) {
        $resultset = $soapclient->getSupportedFileExtensions();
        $supportedextensions = $resultset->getSupportedFileExtensionsResult;
        $fileextenstions = explode(";", $supportedextensions);

        $validfile = false;
        foreach ($fileextenstions as $fileextenstion)
        {
            if (trim($fileextenstion) == $ext)
            {
                $validfile = true;
                break;
            }
        }

        return $validfile;
    }

}
?>
