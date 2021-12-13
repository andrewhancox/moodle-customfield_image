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
 * @package customfield_image
 * @author Andrew Hancox <andrewdchancox@googlemail.com>
 * @author Open Source Learning <enquiries@opensourcelearning.co.uk>
 * @link https://opensourcelearning.co.uk
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright 2021, Andrew Hancox
 */

namespace customfield_image;

defined('MOODLE_INTERNAL') || die;

/**
 * Class field
 *
 * @package customfield_image
 * @copyright 2018 David Matamoros <davidmc@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class field_controller  extends \core_customfield\field_controller {
    /**
     * Plugin type
     */
    const TYPE = 'file';
    const MAXFILES = 20;
    /**
     * Add fields for editing a file field.
     *
     * @param \MoodleQuickForm $mform
     */
    public function config_form_definition(\MoodleQuickForm $mform) {
        global $CFG;

        $mform->addElement('header', 'header_specificsettings', get_string('specificsettings', 'customfield_image'));
        $mform->setExpanded('header_specificsettings', true);

        $choices = get_max_upload_sizes($CFG->maxbytes);
        $mform->addElement('select', 'configdata[maximumbytes]', get_string('maximumbytes', 'customfield_image'), $choices);
        $mform->setDefault('configdata[maximumbytes]', $CFG->maxbytes);
        $mform->setType('configdata[maximumbytes]', PARAM_INT);
    }

    /**
     * Before delete bulk actions
     */
    public function delete() : bool {
        global $DB;
        $fs = get_file_storage();

        // Delete files in the defaultvalue.
        $fs->delete_area_files($this->get_handler()->get_configuration_context()->id, 'customfield_image',
            'defaultvalue', $this->get('id'));

        // Delete files in the data. We can not use $fs->delete_area_files_select() because context may be different.
        $params = ['component' => 'customfield_image', 'filearea' => 'value', 'fieldid' => $this->get('id')];
        $where = "component = :component AND filearea = :filearea
                AND itemid IN (SELECT cfd.id FROM {customfield_data} cfd WHERE cfd.fieldid = :fieldid)";
        $filerecords = $DB->get_recordset_select('files', $where, $params);
        foreach ($filerecords as $filerecord) {
            $fs->get_file_instance($filerecord)->delete();
        }
        $filerecords->close();

        // Delete data and field.
        return parent::delete();
    }
}
