<?php

class block_map_edit_form extends block_edit_form {
    protected function specific_definition($mform) {
        global $CFG, $DB, $COURSE;
        
        $options = array();
        $options[1] = get_string('yes', 'block_map');
        $options[0] = get_string('no', 'block_map');
        
        // Fields for editing HTML block title and contents.
        $mform->addElement('header', 'configheader', get_string('blocksettings', 'block'));

        $mform->addElement('text', 'config_title', get_string('settings:titlemap', 'block_map'));
        $mform->setType('config_title', PARAM_TEXT);
        $mform->setDefault('config_title', get_string('pluginname', 'block_map'));

        $mform->addElement('select', 'config_sitemode', get_string('settings:sitemode', 'block_map'), $options);
        $mform->setDefault('config_sitemode', $CFG->map_sitemode);
        
        $mform->addElement('select', 'config_activate', get_string('settings:activate', 'block_map'), $options);
        $mform->setDefault('config_activate', $CFG->map_activate);
        
        $mform->addElement('select', 'config_editmarks', get_string('settings:editmarks', 'block_map'), $options);
        $mform->setDefault('config_editmarks', $CFG->map_editmarks);
        
        $mform->addElement('select', 'config_loadmarkdin', get_string('settings:loadmarkdin', 'block_map'), $options);
        $mform->setDefault('config_loadmarkdin', $CFG->map_loadmarkdin);
        
        $mform->addElement('select', 'config_publickmap', get_string('settings:publickmap', 'block_map'), $options);
        $mform->setDefault('config_publickmap', $CFG->map_publickmap);
        
        
        $mform->addElement('text', 'config_latitude', get_string('settings:latitude', 'block_map'));
        $mform->setType('config_latitude', PARAM_TEXT);
        $mform->setDefault('config_latitude', $CFG->map_latitude);
        
        $mform->addElement('text', 'config_longitude', get_string('settings:longitude', 'block_map'));
        $mform->setType('config_longitude', PARAM_TEXT);
        $mform->setDefault('config_longitude', $CFG->map_longitude);

        $mform->addElement('hidden', 'config_coursename', $COURSE->fullname);
        
        $mform->addElement('hidden', 'config_courseid', $COURSE->id);
        
        
        $data      = $DB->get_records("block_instances", array("blockname"=>"map"));
        $datares   = array();
        $cm        = array();
        
        $datares[0] = get_string("settings:shareown","block_map");
        
        foreach ($data as $data_) {
            $cm = block_instance('map', $data_);
            $cm->config = unserialize(base64_decode($cm->instance->configdata));
            $datares[$cm->config->courseid] = $cm->config->coursename;
        }

        $mform->addElement('select', 'config_shareid', get_string('settings:share', 'block_map'), $datares);
        //$mform->setDefault('config_shareid', '');
        
    }
}
