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
 * Menu profile field.
 *
 * @package    profilefield_multifileupload
 * @copyright  2007 onwards Shane Elliot {@link http://pukunui.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace metadatafieldtype_fileupload;
defined('MOODLE_INTERNAL') || die;
/**
 * Class local_metadata_field_multifileupload
 *
 * @copyright  2007 onwards Shane Elliot {@link http://pukunui.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class metadata extends \local_metadata\fieldtype\metadata {

    /** @var array $options */
    public $options;

    /** @var int $datakey */
    public $data_url;
    
    public $draftitem_id;

    /**
     * Constructor method.
     *
     * Pulls out the options for the fileupload from the database and sets the the corresponding key for the data if it exists.
     *
     * @param int $fieldid
     * @param int $instanceid
     */
    public function __construct($fieldid = 0, $instanceid = 0) {
        // First call parent constructor.
        parent::__construct($fieldid, $instanceid);
        
        $this->data_url = $this->data;
        
        $arr = explode('/', $this->data);
        $this->draftitem_id = $arr[count($arr)-2];
        
        // Set the name for display; will need to be a language string.
        $this->name = 'File Upload';
    }

    /**
     * Create the code snippet for this field instance
     * Overwrites the base class method
     * @param moodleform $mform Moodle form instance
     */
    public function edit_field_add($mform) {        
                
        $mform->addElement('filemanager', $this->inputname, format_string($this->field->name), null, array('maxbytes' => '0' , 'accepted_types' => '*'));  
                        
    }

    /**
     * Set the default value for this field instance
     * Overwrites the base class method.
     * @param moodleform $mform Moodle form instance
     */
    public function edit_field_set_default($mform) {

        // Set form data
        $mform->setDefaults(array($this->inputname => $this->draftitem_id));          

        //$mform->setDefault($this->inputname, $this->data_url);
    }
    
    public function edit_save_data($new) {
        global $DB, $CFG;

        if (!isset($new->{$this->inputname})) {
            // Field not present in form, probably locked and invisible - skip it.
            return;
        }

        $data = new \stdClass();
        
        $contextid = CONTEXT_MODULE; 
        
        $draftitemid = file_get_submitted_draft_itemid($this->inputname);
        file_prepare_draft_area($draftitemid, $contextid, 'local_metadata', 'image', 0);  
        file_save_draft_area_files($draftitemid, $contextid, 'local_metadata', 'image',  $draftitemid);        
        
        $record = $DB->get_record_sql('SELECT * FROM {files} WHERE contextid='.$contextid.' AND itemid='.$draftitemid.' AND filesize!=0 AND source IS NOT NULL');
        
        if(!empty($record->filename)){
            $url = $CFG->wwwroot.'/pluginfile.php/'.$contextid.'/local_metadata/image/'.$draftitemid.'/'.$record->filename;
        }else{
            $url='';    
        }
        
        $data->instanceid  = $new->id;
        $data->fieldid = $this->field->id;
        $data->data    = $url;

        if ($dataid = $DB->get_field('local_metadata', 'id', ['instanceid' => $data->instanceid, 'fieldid' => $data->fieldid])) {
            $data->id = $dataid;
            $DB->update_record('local_metadata', $data);
        } else {
            $DB->insert_record('local_metadata', $data);
        }
    }    


    /**
     * When passing the instance object to the form class for the edit page
     * we should load the key for the saved data
     *
     * Overwrites the base class method.
     *
     * @param stdClass $instance Instance object.
     */
    public function edit_load_instance_data($instance) {
        //$instance->{$this->inputname} = $this->datakey;
    }

    /**
     * HardFreeze the field if locked.
     * @param moodleform $mform instance of the moodleform class
     */
    public function edit_field_set_locked($mform) {
        if (!$mform->elementExists($this->inputname)) {
            return;
        }
        if ($this->is_locked() && !has_capability('moodle/user:update', context_system::instance())) {
            $mform->hardFreeze($this->inputname);
            $mform->setConstant($this->inputname, format_string($this->datakey));
        }
    }
    /**
     * Convert external data (csv file) from value to key for processing later by edit_save_data_preprocess
     *
     * @param string $value one of the values in fileupload options.
     * @return int options key for the fileupload
     */
    public function convert_external_data($value) {
        if (isset($this->options[$value])) {
            $retval = $value;
        } else {
            $retval = array_search($value, $this->options);
        }

        // If value is not found in options then return null, so that it can be handled
        // later by edit_save_data_preprocess.
        if ($retval === false) {
            $retval = null;
        }
        return $retval;
    }

    /**
     * Return the field type and null properties.
     * This will be used for validating the data submitted by a user.
     *
     * @return array the param type and null property
     * @since Moodle 3.2
     */
    public function get_field_properties() {
        return [PARAM_TEXT, NULL_NOT_ALLOWED];
    }
}


