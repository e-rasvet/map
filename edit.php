<?php
    require_once("../../config.php");
    require_once("lib.php");
    include_once "SimpleImage.php";

    $id               = optional_param('id', 0, PARAM_INT); 
    $ids              = optional_param('ids', NULL, PARAM_CLEAN); 
    $instanceid       = optional_param('instanceid', NULL, PARAM_CLEAN); 
    $descr            = optional_param('descr', NULL, PARAM_CLEAN); 
    $location         = optional_param('location', NULL, PARAM_CLEAN); 
    $name             = optional_param('name', NULL, PARAM_CLEAN); 
    
    if ($id) {
        if (! $course = $DB->get_record("course", array("id"=>$id))) {
            error("Course is misconfigured");
        }
    }
    
    $instance = $DB->get_record("block_instances", array("id"=>$instanceid));
    $context = block_instance('map', $instance);
    $context->config = unserialize(base64_decode($context->instance->configdata));

    require_login($course->id);
    
    add_to_log($course->id, "map", "edit map", "edit.php?id={$id}&instanceid={$instanceid}&ids={$ids}", $id);
    
    if ($descr && $ids) {
        if ($_FILES['image']['tmp_name']) {
          if ($_FILES['image']['size'] > 2*1024*1024) {
            die ("ERROR: Image more than 2 Mb");
          } 
          if (!getimagesize($_FILES['image']['tmp_name']) && !empty($_FILES['image']['tmp_name'])) { 
            die ("ERROR: Incorrect image type.");
          }
            
          list($width, $height, $type, $attr) = getimagesize($_FILES['image']['tmp_name']);
          
          $typeofphotos = array(
            1 => 'gif',
            2 => 'jpg',
            3 => 'png'
          );
          
          if (!$typeofphotos[$type] && !empty($_FILES['image']['tmp_name'])) die ("ERROR: Allow only jpg, png.");
          
          if ($_FILES['image']['tmp_name'])  {
            mkdir("{$CFG->dataroot}/map", 0777);
            mkdir("{$CFG->dataroot}/map/{$USER->id}", 0777);
            $imagefiledir = "/map/{$USER->id}/".date("Ymd_Hi", time()).".{$typeofphotos[$type]}";
            $imagefiledirsmall = "/map/{$USER->id}/th_".date("Ymd_Hi", time()).".{$typeofphotos[$type]}";
            move_uploaded_file ($_FILES['image']['tmp_name'], $CFG->dataroot.$imagefiledir);
            
            $image = new SimpleImage();
            $image->load($CFG->dataroot.$imagefiledir);
            if ($width > $height) 
              $image->resizeToWidth(180);
            else 
              $image->resizeToHeight(180);
            
            $image->save($CFG->dataroot.$imagefiledirsmall);
          }
          
          $img = $imagefiledirsmall;
        }
    
        $DB->set_field("map_items", "name", addslashes($name), array("id"=>$ids));
        $DB->set_field("map_items", "areas", addslashes($location), array("id"=>$ids));
        $DB->set_field("map_items", "descr", addslashes($descr), array("id"=>$ids));
        $DB->set_field("map_items", "updateduserid", $USER->id, array("id"=>$ids));
        $DB->set_field("map_items", "updatedtime", time(), array("id"=>$ids));
        
        if (!empty($img)) 
          $DB->set_field("map_items", "image", $img, array("id"=>$ids));
        
        redirect("map.php?id={$id}&instanceid={$instanceid}", "Thanks!");
    }
    
    
    $PAGE->set_url('/blocks/map/map.php', array('id' => $id, 'instanceid' => $instanceid));

    $title = $course->shortname . ': ' . format_string(get_string('modulename', 'block_map'));
    $PAGE->set_title($title);
    $PAGE->set_heading($course->fullname);

    echo $OUTPUT->header();
    
    echo $OUTPUT->box_start('generalbox');
    
    $data = $DB->get_record('map_items', array("id"=>$ids));

    echo '<div><form action="edit.php?id='.$id.'&instanceid='.$instanceid.'&ids='.$ids.'" enctype="multipart/form-data" method="post">';
    echo '<div style="margin:10px;">'.get_string("edit:name","block_map").' ';
    
    echo html_writer::empty_tag('input', array('type'=>'text', 'name'=>'name', 'alt'=>'', 'style'=>'', 'value'=>str_replace("\\", '', $data->name)));
    
    echo '</div>';
    echo '<div style="margin:10px;">'.get_string("edit:location","block_map").' ';

    echo html_writer::select($areas, 'location', $data->areas);
    
    echo '</div>';
    echo '<div style="margin:10px;">'.get_string("edit:image","block_map").' ';
    if ($data->image != "0") echo ' (Current: '.$data->image.') ';
    echo '<input type="file" name="image" id="file" /> ';
    echo '</div>';
    echo '<div style="margin:10px;">'.get_string("edit:description","block_map").' ';
    print_textarea(true, 15, 60, 400, 300, 'descr', str_replace("\\", '', $data->descr));
    echo '</div>';
    echo '<input type="submit" name="sub" value="Save" style="font-size: 16pt;" />';
    echo '</form></div>';
    
    echo $OUTPUT->box_end();

    echo $OUTPUT->footer();
 
