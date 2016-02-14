<?php


class block_map extends block_base {
    public function init() {
        $this->title = get_string('pluginname', 'block_map');
    }

    public function instance_allow_multiple() {
        return true;
    }

    public function has_config() {
        return true;
    }

    public function applicable_formats() {
        return array('all' => true);
    }

    public function instance_allow_config() {
        return true;
    }

    public function specialization() {

        // Load userdefined title and make sure it's never empty.
        if (empty($this->config->title)) {
            $this->title = get_string('pluginname', 'block_map');
        } else {
            $this->title = $this->config->title;
        }
    }

    public function get_content() {

        global $CFG, $COURSE, $USER, $SCRIPT, $OUTPUT;

        if (!isset($this->config)) {
            $this->config = new stdClass();
        }
        
        $this->content = new stdClass();
        
        if (empty($this->config->title)) {
            $this->content->text = get_string('disabledmap', 'block_map');
        } else {
            $this->content->text  = '<a href="'.$CFG->wwwroot.'/blocks/map/map.php?id='.$COURSE->id.'&instanceid='.$this->instance->id.'">'.get_string("showmap","block_map").'</a>';
            $this->content->footer = 'search';
        }

        return $this->content;
    }
}
