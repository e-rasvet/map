<?php
    require_once("../../config.php");
    require_once("lib.php");
    require_once("{$CFG->dirroot}/lib/grouplib.php");


    $id               = optional_param('id', 0, PARAM_INT); 
    $a                = optional_param('a', NULL, PARAM_CLEAN); 
    $instanceid       = optional_param('instanceid', NULL, PARAM_CLEAN); 
    $orderby          = optional_param('orderby', NULL, PARAM_CLEAN); 
    $sort             = optional_param('sort', NULL, PARAM_CLEAN); 
    $page             = optional_param('page', 0, PARAM_INT); 
    $ids              = optional_param('ids', NULL, PARAM_CLEAN); 
    $idc              = optional_param('idc', NULL, PARAM_CLEAN); 
    $act              = optional_param('act', NULL, PARAM_CLEAN); 
    $text             = optional_param('text', NULL, PARAM_CLEAN); 
    
    if ($id) {
        if (! $course = $DB->get_record("course", array("id"=>$id))) {
            error("Course is misconfigured");
        }
    }
    
    $instance = $DB->get_record("block_instances", array("id"=>$instanceid));
    $context = block_instance('map', $instance);
    $context->config = unserialize(base64_decode($context->instance->configdata));

    require_login($course->id);

    add_to_log($course->id, "map", "admin map", "admin.php?id={$id}&instanceid={$instanceid}", $id);
    
    if (has_capability('block/map:teacher', get_context_instance(CONTEXT_COURSE, $course->id))) {
        if ($act == 'allowfrom' || $act == 'active') {
            $DB->set_field('map_items', $act, $ids, array('id'=>$idc));
            echo $ids;
            die();
        }
        
        if ($act == 'delete') {
            $data = $DB->get_record("map_items", array("id"=>$idc));
            if (is_file($CFG->dataroot.$data->image))
                unlink($CFG->dataroot.$data->image);
            if (is_file($CFG->dataroot.str_replace("th_", "", $data->image)))
                unlink($CFG->dataroot.str_replace("th_", "", $data->image));
            $DB->delete_records("map_items", array("id"=>$idc));
        }
        
        if ($text && $idc) {
            $DB->set_field('map_items', 'descr', $text, array("id"=>$idc));
        }
    }
    
    if (empty($context->config->titlemap)) {
        $pagetitle = get_string("pagetitle","block_map");
    } else {
        $pagetitle = $context->config->titlemap;
    }

    $PAGE->set_url('/blocks/map/map.php', array('id' => $id, 'instanceid' => $instanceid));

    $title = $course->shortname . ': ' . format_string(get_string('modulename', 'block_map'));
    $PAGE->set_title($title);
    $PAGE->set_heading($course->fullname);
    
    echo $OUTPUT->header();

    echo '<script type="text/javascript" src="'.$CFG->wwwroot.'/blocks/map/js/jquery-1.4.2.min.js"></script>
<script type="text/javascript" src="'.$CFG->wwwroot.'/blocks/map/js/jquery.form.js"></script> 
<script type="text/javascript" src="'.$CFG->wwwroot.'/blocks/map/js/jquery.simplemodal-1.3.3.min.js"></script> ';

    echo '<script type="text/javascript">
    function showImage (id) {
        $.modal(\'<div id="showevaldata" class="image-modal"><img src="'.$CFG->wwwroot.'/blocks/map/getimg.php?link=\'+id+\'" alt="" /></div>\');
    }
    function showTextEditor (id) {
        $.modal(\'<div id="showevaldata" class="text-modal"><img width="16" height="16" src="'.$CFG->wwwroot.'/blocks/map/img/zoomloader.gif" alt="" /></div>\');
        $(\'#showevaldata\').load(\'text_editor.php?idc=\' + id + \'&id='.$id.'&instanceid='.$instanceid.'\');
    }
    </script>';
    
    echo '<style media="screen" type="text/css">
.text-modal {
background-color:#ffffff;
border:4px solid #444444;
height:350px;
padding:12px;
width:500px;
}
.image-modal {
background-color:#ffffff;
border:4px solid #444444;
height:200px;
padding:12px;
width:200px;
}
#simplemodal-overlay {
background-color:#000000;
cursor:wait;
}

a.modalCloseImg {
    background:url('.$CFG->wwwroot.'/blocks/map/img/x.png) no-repeat; /* adjust url as required */
    width:25px;
    height:29px;
    display:inline;
    z-index:3200;
    position:absolute;
    top:-15px;
    right:-18px;
    cursor:pointer;
}
<!--[if lt IE 7]>
<style type=\'text/css\'>
    a.modalCloseImg {
        background:none;
        right:-14px;
        width:22px;
        height:26px;
        filter: progid:DXImageTransform.Microsoft.AlphaImageLoader(
            src=\''.$CFG->wwwroot.'/blocks/map/img/x.png\', sizingMethod=\'scale\'
        );
    }
</style>
<![endif]-->
</style>';

    if (has_capability('block/map:teacher', get_context_instance(CONTEXT_COURSE, $course->id))) {
        $currenttab = $a;
        if (!isset($currenttab)) {
            $currenttab = 'map';
        }
        
        if (empty($idh)) {
            $idh = $id;
        }
        $tabs = array();
        $row  = array();
        $inactive = array();
        $activated = array();
        $row[] = new tabobject('map', "map.php?id={$id}&instanceid={$instanceid}", "Map");
        $row[] = new tabobject('admin', "admin.php?id={$id}&instanceid={$instanceid}&a=admin", "Admin Area");
        
        $tabs[] = $row;
        print_tabs($tabs, $currenttab, $inactive, $activated);
    }


    $titlesarray = Array (''=>'','Title'=>'title', 'Description'=>'', 'User'=>'user',  'Group'=>'group', 'Map zoom level'=>'', 'Image'=>'', 'Latitude'=>'', 'Longitude'=>'', 'Active'=>'active', 'Date'=>'date');
        
    $table = new html_table();
    
    $table->head = block_map_make_table_headers ($titlesarray, $orderby, $sort, '?id='.$id.'&instanceid='.$instanceid.'&a='.$a.'&page='.$page);
    $table->align = array ("center","left", "center", "left", "left", "center", "center", "center", "center", "center", "center");
    $table->width = "100%";
    
    $data = $DB->get_records("map_items");
    foreach ((array)$data as $data_) {
        if ($data_->image != "0") {
            $image = '<img src="'.$CFG->wwwroot.'/blocks/map/img/image.png" alt="" width="32" height="32" />';
        } else {
            $image = "";
        }
        for ($i=0;$i<=15;$i++) {
            $allowsfromarray[$i] = $i;
        }
        $studentgroups = groups_get_user_groups($course->id, $data_->userid);
        $usergroup = "";
        foreach ((array)$studentgroups[0] as $key => $value) {
            $usergroup .= groups_get_group_name($value).", ";
        }
        if (empty($usergroup))
          $usergroup = "-";
        else
          $usergroup = substr($usergroup, 0, -2);
        
        $u = $DB->get_record("user", array("id"=>$data_->userid));
        
        $table->data[] = array ($OUTPUT->user_picture($u, array('courseid'=>$course->id, true, 0, true)),
                               '<a href="'.$CFG->wwwroot.'/blocks/map/map.php?id='.$id.'&instanceid='.$instanceid.'&show=auto&showx='.$data_->geo_x.'&showy='.$data_->geo_y.'">'.$data_->name.'</a>',
                               '<a href="#" onclick="showTextEditor(\''.$data_->id.'\');return false;"><img src="'.$CFG->wwwroot.'/blocks/map/img/text.png" alt="" width="32" height="32" /></a>',
                               block_map_user_link_t($data_->userid),
                               $usergroup,
                               block_map_selector ('allow-'.$data_->id, 'allowfrom', $allowsfromarray, $data_->allowfrom, 'admin.php?id='.$id.'&instanceid='.$instanceid.'&a='.$a.'&page='.$page.'&act=allowfrom&idc='.$data_->id.'&'),
                               '<a href="#" onclick="showImage(\''.$data_->image.'\');return false;">'.$image.'</a>',
                               $data_->geo_x,
                               $data_->geo_y,
                               block_map_selector ('active-'.$data_->id, 'active', array(1=>"Yes", 0=>"No"), $data_->active, 'admin.php?id='.$id.'&instanceid='.$instanceid.'&a='.$a.'&page='.$page.'&act=active&idc='.$data_->id.'&'),
                               array(date("Y-m-d H:i", $data_->time).' <a href="admin.php?id='.$id.'&instanceid='.$instanceid.'&a='.$a.'&page='.$page.'&act=delete&idc='.$data_->id.'"  onclick="if(confirm(\'Delete this mark?\')) return true; else return false;">[delete]</a>', $data_->time));
    }
    
    $table->data = block_map_sort_table_data ($table->data, $titlesarray, $orderby, $sort);

    $alinkpadding = 'admin.php?id='.$id.'&instanceid='.$instanceid.'&a='.$a.'&';
    
    list($totalcount, $table->data, $startrec, $finishrec, $options["page"]) = block_map_get_pages($table->data, $page, 30);
            
    echo $OUTPUT->render(new paging_bar($totalcount, $page, 30, $alinkpadding)); 
            
    if ($table) {
        echo html_writer::table($table);
    }
    
    echo $OUTPUT->render(new paging_bar($totalcount, $page, 30, $alinkpadding)); 

    echo $OUTPUT->footer();
 