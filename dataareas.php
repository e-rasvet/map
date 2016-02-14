<?php

require_once "../../config.php";
require_once "lib.php";

    $id               = optional_param('id', 0, PARAM_INT); 
    $instanceid       = optional_param('instanceid', NULL, PARAM_CLEAN); 
    $area             = optional_param('area', NULL, PARAM_CLEAN); 
    $info             = optional_param('info', NULL, PARAM_CLEAN); 
    $geo_x            = optional_param('geo_x', NULL, PARAM_CLEAN); 
    $geo_y            = optional_param('geo_y', NULL, PARAM_CLEAN); 
    
    if ($id) {
        if (! $course = $DB->get_record("course", array("id"=>$id))) {
            error("Course is misconfigured");
        }
    }
    
    $instance = $DB->get_record("block_instances", array("id"=>$instanceid));
    $context = block_instance('map', $instance);
    $context->config = unserialize(base64_decode($context->instance->configdata));
    
    //require_login($course->id);
    
    add_to_log($course->id, "map", "Locations", "dataareas.php?id={$id}&instanceid{$instanceid}", $id);
    
    
    if (!empty($info)) {
        $item = $DB->get_record("map_items", array("geo_x"=>$geo_x, "geo_y"=>$geo_y));
        $heightdiv = '';
        if ($item->image != "0" || strlen(strip_tags($item->descr)) > 300)
            $heightdiv = 'height:360px;';
        $template = '<div style="overflow:auto;'.$heightdiv.'width:320px;"><div style="width:300px"><strong>[name]</strong></div><div><small>'.get_string("map:addedby","block_map").'[photoautor]</small></div><div><small><i>Coordinates:</i> [koordinatides]</small></div>[image]<div style="width:300px"><div class="gdescr">[description]</div></div></div>';
        
        if ($item->image == "0") {
            $template = str_replace("[image]","<br />",$template);
            //$template = str_replace("[addphoto]",'<a href="add_photo.php?id='.$item->id.'">Добавить фото</a> :: ',$template);
        } else {
            list($width, $height, $type, $attr) = getimagesize($CFG->dataroot.$item->image);
            $template = str_replace("[image]",'<div style="margin-top:8px;"><img src="'.$CFG->wwwroot.'/blocks/map/getimg.php?link='.$item->image.'" alt="" width="'.$width.'" height="'.$height.'" style="background: none repeat scroll 0 0 #F2E9E2;border: 1px solid #DDDDDD;display:inline;margin:0 10px 10px 0;padding: 5px;" /></div>',$template);
            //$template = str_replace("[addphoto]",'<a href="add_photo.php?id='.$item->id.'">Предложить лучшее фото</a> :: ',$template);
        }
        
        $template = str_replace("[name]",stripslashes($item->name),$template);
        $template = str_replace("[description]",stripslashes($item->descr),$template);
        $template = str_replace("[koordinatides]",$item->geo_x.",".$item->geo_y,$template);
        $template = str_replace("[errorreport]",'Comments',$template);
        
        $autor = $DB->get_record("user", array("id"=>$item->userid));
        $autor = '<a href="'.$CFG->wwwroot.'/user/view.php?id='.$item->userid.'&course='.$item->courseid.'">'.fullname($autor).'</a>';
        
        if ($context->config->editmarks) 
            $autor .= ' <a href="edit.php?id='.$id.'&instanceid='.$instanceid.'&ids='.$item->id.'">[edit]</a>';
        
        if ($item->updateduserid) {
            $updatedautor = $DB->get_record("user", array("id"=>$item->updateduserid));
            $autor .= '<br />'.get_string("map:updatedby","block_map").'<a href="'.$CFG->wwwroot.'/user/view.php?id='.$item->userid.'&course='.$item->courseid.'">'.fullname($updatedautor).'</a> Date: '.date("d.m.Y H:i", $item->updatedtime);
        }
        
        $template = str_replace("[photoautor]",$autor,$template);
        
        echo $template;
        die();
    }
    
   
    
    echo '<h3>'.get_string("map:areas","block_map").'</h3>';
    
    if (empty($area)) {
      foreach ((array)$areas as $key => $value) {
        //$count = count_records('map_items', 'areas', $key);
        if ($context->config->sitemode) 
          $count = $DB->count_records_sql("SELECT COUNT(*) FROM {map_items} WHERE areas='{$key}' and `active` = 1");
        else
          $count = $DB->count_records_sql("SELECT COUNT(*) FROM {map_items} WHERE areas='{$key}' and courseid='".block_map_checkshareid ($course->id)."' and `active` = 1");
        echo '<a href="#" onclick="changelocation(\''.$key.'\')">'.$value.' ('.$count.')</a><br />';
      }
    } else {
        echo '<a href="#" onclick="changelocation(\'0\')"><- Back</a><br /><br />';
        //$data = get_records("map_items", "areas", $area);
        if ($context->config->sitemode) 
          $data = $DB->get_records_sql("SELECT * FROM {map_items} WHERE areas='{$area}' and `active` = 1 ORDER BY name");
        else
          $data = $DB->get_records_sql("SELECT * FROM {map_items} WHERE areas='{$area}' and courseid='".block_map_checkshareid ($course->id)."' and `active` = 1 ORDER BY name");
        foreach ($data as $data_) {
            if ($data_->active == 1) {
                echo '<small><a href="#'.$data_->geo_x.':'.$data_->geo_y.'" id="gotopointid">'.str_replace("\\", '', $data_->name).'</a></small><br />';
            }
        }
    }
 
?>