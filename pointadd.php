<?php

    require_once "../../config.php";
    include_once "SimpleImage.php";

    $id               = optional_param('id', 0, PARAM_INT); 
    $pcoord           = optional_param('pcoord', NULL, PARAM_CLEAN); 
    $name             = optional_param('name', NULL, PARAM_CLEAN); 
    $description      = optional_param('description', NULL, PARAM_CLEAN); 
    $instanceid       = optional_param('instanceid', NULL, PARAM_CLEAN); 
    $areas            = optional_param('areas', NULL, PARAM_CLEAN); 
    
    if ($id) {
        if (! $course = $DB->get_record("course", array("id"=>$id))) {
            error("Course is misconfigured");
        }
    }
    
    $instance = $DB->get_record("block_instances", array("id"=>$instanceid));
    $context = block_instance('map', $instance);
    $context->config = unserialize(base64_decode($context->instance->configdata));
    
    require_login($id);
    
    add_to_log($course->id, "map", "map added new mark", "map.php?id={$id}&instanceid{$instanceid}", $id);
    
    list($geo_x, $geo_y) = explode(",", str_replace(" ", "", $pcoord));
    
    $geo_x = str_replace("(","",$geo_x);
    $geo_y = str_replace(")","",$geo_y);
    
    if (!$DB->get_record("map_items", array("geo_x"=>$geo_x, "geo_y"=>$geo_y))) {
        if (!$name && !$description) 
            die ("ERROR: Please fill all the fields");
    
        if ($_FILES['image']['size'] > 2*1024*1024) {
          die ("ERROR: Image more than 2 Mb");
        } 
    
        if (!@getimagesize($_FILES['image']['tmp_name']) && !empty($_FILES['image']['tmp_name'])) { 
          die ("ERROR: Incorrect image type.");
        }
    
        list($width, $height, $type, $attr) = @getimagesize($_FILES['image']['tmp_name']);
        
        $typeofphotos = array(
          1 => 'gif',
          2 => 'jpg',
          3 => 'png'
        );
        
        if (!empty($type))
          if (!$typeofphotos[$type] && !empty($_FILES['image']['tmp_name'])) die ("ERROR: Allow only jpg, png.");
        
        if ($_FILES['image']['tmp_name'])  {
            /*
            if ($context->config->sitemode) {
              mkdir("{$CFG->dataroot}/map", 0777);
              mkdir("{$CFG->dataroot}/map/{$USER->id}", 0777);
              $imagefiledir = "/map/{$USER->id}/".date("Ymd_Hi", time()).".{$typeofphotos[$type]}";
              $imagefiledirsmall = "/map/{$USER->id}/th_".date("Ymd_Hi", time()).".{$typeofphotos[$type]}";
              move_uploaded_file ($_FILES['image']['tmp_name'], $CFG->dataroot.$imagefiledir);
            } else {
            */
              @mkdir("{$CFG->dataroot}/map", 0777);
              //@mkdir("{$CFG->dataroot}/map/{$course->id}", 0777);
              @mkdir("{$CFG->dataroot}/map/{$USER->id}", 0777);
              $imagefiledir = "/map/{$USER->id}/".date("Ymd_Hi", time()).".{$typeofphotos[$type]}";
              $imagefiledirsmall = "/map/{$USER->id}/th_".date("Ymd_Hi", time()).".{$typeofphotos[$type]}";
              move_uploaded_file ($_FILES['image']['tmp_name'], $CFG->dataroot.$imagefiledir);
            //}
            
            $image = new SimpleImage();
            $image->load($CFG->dataroot.$imagefiledir);
            if ($width > $height) {
                $image->resizeToWidth(180);
            } else {
              $image->resizeToHeight(180);
            }
            $image->save($CFG->dataroot.$imagefiledirsmall);
        }
        
        if ($_FILES['image']['tmp_name']) 
            $img = $imagefiledirsmall; 
        else 
            $img = 0; 
        
        
        $data = new stdClass();
        $data->userid = $USER->id;
        $data->courseid = (int)$course->id;
        $data->name = $name;
        $data->descr = $description;
        $data->allowfrom = 7;
        $data->image = $img;
        $data->geo_x = $geo_x;
        $data->geo_y = $geo_y;
        $data->areas = $areas;
        $data->active = $context->config->activate;
        $data->time = time();
        $data->instanceid = (int)$instanceid;
        
                
        $ids = $DB->insert_record("map_items", $data);
        
        @$DB->set_field("map_items", "instanceid", $instanceid, array("id"=>$ids));
        
        print_string("map:thanks","block_map");
    }
 
?>