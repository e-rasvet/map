<?php

$areas = array(
    "ko"=>"Kochi",
    "ka"=>"Kagawa",
    "to"=>"Tokushima",
    "eh"=>"Ehime",
    "other"=>"Other"
);


function block_map_get_pages($table, $page, $perpage) {
    global $CFG, $COURSE;
    
    $totalcount = count ($table);
    $startrec  = $page * $perpage;
    $finishrec = $startrec + $perpage;
    
    foreach ($table as $key => $value) {
        if ($key >= $startrec && $key < $finishrec) {
            $viewtable[] = $value;
        }
    }
    
    return array($totalcount, $viewtable, $startrec, $finishrec, $page);
}

function block_map_make_table_headers ($titlesarray, $orderby, $sort, $link) {
    global $USER, $CFG;

    if ($orderby == "ASC") {
        $columndir    = "DESC";
        $columndirimg = "down";
    } else {
        $columndir    = "ASC";
        $columndirimg = "up";
    }

    foreach ($titlesarray as $titlesarraykey => $titlesarrayvalue) {
        if ($sort != $titlesarrayvalue) {
            $columnicon = "";
        } else {
            $iconlink   = new moodle_url("/theme/image.php", array("theme" => $CFG->theme, "image" => "t/{$columndirimg}", "rev" => $CFG->themerev));
            $columnicon = " <img src=\"{$iconlink}\" alt=\"\" />";
        }
        if (!empty($titlesarrayvalue)) {
            $table->head[] = "<a href=\"".$link."&sort=$titlesarrayvalue&orderby=$columndir\">$titlesarraykey</a>$columnicon";
        } else {
            $table->head[] = "$titlesarraykey";
        } 
    }
    
    return $table->head;
}


function block_map_sort_table_data ($data, $titlesarray, $orderby, $sort) {
    global $USER, $CFG;

    $j = 0;
    if ($sort) {
        foreach ($titlesarray as $titlesarray_) {
            if ($titlesarray_ == $sort) {
                $orderkey = $j;
            }
            $j++;
        }
    } else {
        $orderkey = 0;
    }

    $i = 0;

    foreach ($data as $datakey => $datavalue) {
        if (!is_array($datavalue[$orderkey])) {
            $key = $datavalue[$orderkey];
        } else {
            $key = $datavalue[$orderkey][1];
        }

        for ($j=0; $j < count($datavalue); $j++) {
            if (!is_array($datavalue[$j])) {
                $newarray[(string)$key][$i][$j] = $datavalue[$j];
            } else {
                $newarray[(string)$key][$i][$j] = $datavalue[$j][0];
            }
        }
        
        $i ++;
    }
    
    if (empty($orderby) || $orderby == "ASC") {
        ksort ($newarray); 
    } else {
        krsort ($newarray);
    }
    
    reset($newarray);
    
    foreach ($newarray as $newarray_) {
        foreach ($newarray_ as $newarray__) {
            $newarraynew = array ();
            foreach ($newarray__ as $newarray___) {
                $newarraynew[] = $newarray___;
            }
            $finaldata[] = $newarraynew;
        }
    }
    
    return $finaldata;
}


function block_map_user_link_t($userid) {
    global $CFG, $COURSE, $DB;

    $userdata = $DB->get_record("user", array("id"=>$userid));

    return array('<a href="'.$CFG->wwwroot.'/user/view.php?id='.$userdata->id.'&course='.$COURSE->id.'">'.$userdata->username.' ('.fullname($userdata).')</a>', $userdata->username);
}


function block_map_selector ($div, $name, $data, $def, $link, $text = '') {
    global $CFG, $COURSE;

    $str = '<script type="text/javascript">
    $(document).ready(function() {
        $(\'#'.$div.'\').change(function() {
      var selected = $("#'.$div.' option:selected");
      $(\'#'.$div.'\').html(\'<img width="16" height="16" src="'.$CFG->wwwroot.'/blocks/map/img/zoomloader.gif" alt="" />\');
      $(\'#'.$div.'\').load(\''.$link.'ids=\' + selected.val());
    });
  });</script>';
    
    $str .= '<div id="'.$div.'">'.$text.' <select name="'.$name.'">';
    foreach ($data as $key => $value) {
      $str .= '<option value="'.$key.'" ';
      if ($def == $key) $str .= 'selected="selected"';
      $str .= ' >'.$value.'</option>';
    }
    $str .= '</select></div>';
  
    return $str;
}


function block_map_checkshareid ($instanceid) {
    global $CFG, $COURSE, $DB;
    
    $instance = $DB->get_record("block_instances", array("id"=>$instanceid));
    $context = block_instance('map', $instance);
    
    $context->config = unserialize(base64_decode($context->instance->configdata));
    
    //print_r ($context->instance);
    
    $shareid = $context->config->courseid;
    
    if (!empty($context->config->sitemode)) 
        return 1;
    
    if (!empty($context->config->shareid))
        $shareid = $context->config->shareid;
    
    return $shareid;
}


function block_map_checkshareid_course ($courseid) {
    global $CFG, $COURSE, $DB;
    
    $shareid = $courseid;
    
    if ($inst = $DB->get_record_sql("SELECT * FROM {map_items} WHERE `courseid`={$courseid} OR `courseid`= 1 LIMIT 1")){
      $instance = $DB->get_record("block_instances", array("id"=>$inst->instanceid));
      $context = block_instance('map', $instance);

      if (!empty($context->config->sitemode)) 
          return 1;
      
      if (!empty($context->config->shareid))
          $shareid = $context->config->shareid;
    }
    
    return $shareid;
}


function block_map_checkshareid_getinstanse ($courseid) {
    global $CFG, $COURSE, $DB;

    if ($inst = $DB->get_record_sql("SELECT * FROM {map_items} WHERE `courseid`={$courseid} OR `courseid`= 1 LIMIT 1"))
      return $inst;
    else
      return false;
}



