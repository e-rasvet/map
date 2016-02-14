<?php
require_once "../../config.php";

$leftbottom         = optional_param('LeftBottom', NULL, PARAM_CLEAN); 
$righttop           = optional_param('RightTop', NULL, PARAM_CLEAN); 
$courseid           = optional_param('courseid', NULL, PARAM_CLEAN); 
$id                 = optional_param('id', 0, PARAM_INT); 
$instanceid         = optional_param('instanceid', NULL, PARAM_CLEAN); 
    
if ($id) {
    if (! $course = $DB->get_record("course", array("id"=>$id))) {
       //error("Course is misconfigured");
    }
}
    
$instance = $DB->get_record("block_instances", array("id"=>$instanceid));
$context = block_instance('map', $instance);
$context->config = unserialize(base64_decode($context->instance->configdata));

$parse  = explode(",", $leftbottom);
$parse2 = explode(",", $righttop);
$left   = $parse[1];
$top    = $parse2[0];
$right  = $parse2[1];
$bottom = $parse[0];

if ($context->config->loadmarkdin == 1) {
  if ($context->config->sitemode)
    $items = $DB->get_records_sql("SELECT * FROM {map_items} WHERE geo_x > {$left} && geo_x < {$right} && geo_y < {$top} && geo_y > {$bottom} && active=1");
  else
    $items = $DB->get_records_sql("SELECT * FROM {map_items} WHERE geo_x > {$left} && geo_x < {$right} && geo_y < {$top} && geo_y > {$bottom} && active=1 && courseid={$courseid}");
} else {
  if ($context->config->sitemode)
    $items = $DB->get_records_sql("SELECT * FROM {map_items} WHERE active=1");
  else
    $items = $DB->get_records_sql("SELECT * FROM {map_items} WHERE active=1 && courseid={$courseid}");
}

header ("content-type: text/xml"); 
echo '<?xml version="1.0" encoding="UTF-8"?>
';
?><markers>
<?php
//if (is_array($items) && count($items) < 200) {
  foreach ((array)$items as $item) {
    $heightdiv = '';
    if ($item->image != "0" || strlen(strip_tags($item->descr)) > 300)
        $heightdiv = 'height:360px;';
    $template = '<![CDATA[<div style="margin:10px;"><div style="overflow:auto;'.$heightdiv.'width:320px;"><div style="width:300px"><strong>[name]</strong></div><div><small>'.get_string("map:addedby","block_map").'[photoautor]</small></div><div><small><i>Coordinates:</i> [koordinatides]</small></div>[image]<div style="width:300px"><div class="gdescr">[description]</div></div></div></div>]]>';
    
	if ($item->image == "0") {
		$template = str_replace("[image]","<br />",$template);
		//$template = str_replace("[addphoto]",'<a href="add_photo.php?id='.$item->id.'">Добавить фото</a> :: ',$template);
	} else {
		list($width, $height, $type, $attr) = getimagesize($CFG->dataroot.$item->image);
		$template = str_replace("[image]",'<div style="margin-top:8px;"><img src="'.$CFG->wwwroot.'/blocks/map/getimg.php?link='.$item->image.'" alt="" width="'.$width.'" height="'.$height.'" style="background: none repeat scroll 0 0 #F2E9E2;border: 1px solid #DDDDDD;display:inline;margin:0 10px 10px 0;padding: 5px;" /></div>',$template);
		//$template = str_replace("[addphoto]",'<a href="add_photo.php?id='.$item->id.'">Предложить лучшее фото</a> :: ',$template);
	}
	
	$template = str_replace("[name]",str_replace("\\", '', $item->name),$template);
	$template = str_replace("[description]",str_replace("\\", '', $item->descr),$template);
	$template = str_replace("[koordinatides]",$item->geo_x.",".$item->geo_y,$template);
	$template = str_replace("[errorreport]",'Comments',$template);
	
	$autor = $DB->get_record("user", array("id"=>$item->userid));
	$autor = '<a href="'.$CFG->wwwroot.'/user/view.php?id='.$item->userid.'&course='.$item->courseid.'">'.fullname($autor).'</a> Date: '.date("d.m.Y H:i", $item->time);
	
	if (!empty($context->config->editmarks)) 
	    $autor .= ' <a href="edit.php?id='.$id.'&instanceid='.$instanceid.'&ids='.$item->id.'">[edit]</a>';
	
	if ($USER->id == $item->userid)
	    $autor .= ' <a href="map.php?id='.$id.'&instanceid='.$instanceid.'&ids='.$item->id.'&act=delete" onclick="if(confirm(\'Delete?\')) return true; else return false;">[delete]</a>';
	
	if (!empty($item->updateduserid)) {
	    $updatedautor = $DB->get_record("user", array("id"=>$item->updateduserid));
	    $autor .= '<br />'.get_string("map:updatedby","block_map").'<a href="'.$CFG->wwwroot.'/user/view.php?id='.$item->userid.'&course='.$item->courseid.'">'.fullname($updatedautor).'</a> Date: '.date("d.m.Y H:i", $item->updatedtime);
	}
	
	$template = str_replace("[photoautor]",$autor,$template);
	
?><marker lat="<?php echo $item->geo_x; ?>" lng="<?php echo $item->geo_y; ?>" maxzoom="<?php echo $item->allowfrom; ?>" id="<?php echo $item->id; ?>">
<name><?php echo $item->name; ?></name>
<description><?php 
echo $template; 
//echo "test" 
?></description>
</marker>
<?php
  }
//}
?></markers>