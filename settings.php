<?php

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {
    $options = array();
    $options[1] = get_string('yes', 'block_map');
    $options[0] = get_string('no', 'block_map');
    
    $settings->add(new admin_setting_configselect('map_sitemode',
            get_string('settings:sitemode', 'block_map'), get_string('settings:sitemode', 'block_map'), 1, $options));

    $settings->add(new admin_setting_configselect('map_activate',
            get_string('settings:activate', 'block_map'), get_string('settings:activate', 'block_map'), 1, $options));
            
    $settings->add(new admin_setting_configselect('map_editmarks',
            get_string('settings:editmarks', 'block_map'), get_string('settings:editmarks', 'block_map'), 1, $options));
            
    $settings->add(new admin_setting_configselect('map_loadmarkdin',
            get_string('settings:loadmarkdin', 'block_map'), get_string('settings:loadmarkdin', 'block_map'), 1, $options));
            
    $settings->add(new admin_setting_configselect('map_publickmap',
            get_string('settings:publickmap', 'block_map'), get_string('settings:publickmap', 'block_map'), 1, $options));
            
    $settings->add(new admin_setting_configtext('map_googlekey',
            get_string('settings:googlekey', 'block_map'), get_string('settings:googlekey', 'block_map'), '', PARAM_TEXT));
            
    //$settings->add(new admin_setting_configtext('map_titlemap',
    //        get_string('settings:titlemap', 'block_map'), get_string('settings:titlemap', 'block_map'), '', PARAM_TEXT));
            
    $settings->add(new admin_setting_configtext('map_latitude',
            get_string('settings:latitude', 'block_map'), get_string('settings:latitude', 'block_map'), '', PARAM_TEXT));
            
    $settings->add(new admin_setting_configtext('map_longitude',
            get_string('settings:longitude', 'block_map'), get_string('settings:longitude', 'block_map'), '', PARAM_TEXT));
            
    //$settings->add(new admin_setting_configselect('map_shareid',
    //        get_string('settings:share', 'block_map'), get_string('settings:share', 'block_map'), 1, $options));
}
