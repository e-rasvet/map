<?php
    require_once("../../config.php");
    require_once ($CFG->dirroot.'/blocks/map/lib.php');
    
    $id               = optional_param('id', 0, PARAM_INT); 
    $a                = optional_param('a', NULL, PARAM_TEXT); 
    $act              = optional_param('act', NULL, PARAM_TEXT); 
    $show             = optional_param('show', NULL, PARAM_CLEAN); 
    $showx            = optional_param('showx', NULL, PARAM_CLEAN); 
    $showy            = optional_param('showy', NULL, PARAM_CLEAN); 
    $ids              = optional_param('ids', NULL, PARAM_INT); 
    $instanceid       = optional_param('instanceid', NULL, PARAM_INT); 
    $searchtext       = optional_param('searchtext', NULL, PARAM_TEXT); 
    
    if ($id) {
        if (! $course = $DB->get_record("course", array("id"=>$id))) {
            error("Course is misconfigured");
        }
    }
    
    $instance = $DB->get_record("block_instances", array("id"=>$instanceid));
    $context = block_instance('map', $instance);
    
    $context->config = unserialize(base64_decode($context->instance->configdata));

    if (!$context->config->publickmap)
        require_login($course->id);
    else
        $PAGE->set_course($course); 

    add_to_log($course->id, "map", "View map", "map.php?id={$id}&instanceid={$instanceid}", $id);
    
    if (!$context->config->title) {
        $pagetitle = get_string("pagetitle","block_map");
    } else {
        $pagetitle = $context->config->title;
    }
    
    if ($act == "delete") {
        $DB->delete_records("map_items", array("id"=>$ids, "userid"=>$USER->id));
        $message = get_string("map:deleted","block_map");
    }
    
    if ($searchtext) {
        echo '<div style="margin:20px;"><a href="#" onclick="changelocation(\'0\')"><- Back</div>';
        if ($searchtext) {
            if (strstr($searchtext, '"')) {
                $searchtext = str_replace('\"', '"', $searchtext);
                $searchtext = explode('"', $searchtext);
            } else {
                $searchtext = explode(" ", $searchtext);
            }
            foreach ($searchtext as $searchtext_) {
              if ($searchtext_) {
                  $searchtext_ = strtolower($searchtext_);
                  $data = $DB->get_records_sql("SELECT * FROM {map_items} WHERE `name` LIKE '%{$searchtext_}%' OR descr LIKE '%{$searchtext_}%'");
                  if ($data) {
                      foreach ($data as $data_) {
                          $searchresult[$data_->id] = $data_;
                      }
                  }
              }
            }
            
            if ($searchresult) {
                foreach ($searchresult as $searchresult_) {
                    if ($searchresult_->active == 1) {
                        echo '<div style="margin:10px;"><a href="#'.$searchresult_->geo_x.':'.$searchresult_->geo_y.'" id="gotopointid">'.$searchresult_->name.'</a></div>';
                    }
                }
            } else {
                echo '<div style="margin:20px;">No result</div>';
            }
        }
        
        echo '<div style="margin:20px;"><a href="#" onclick="changelocation(\'0\')"><- Back</div>';
        die();
    }
    

    $PAGE->set_url('/blocks/map/map.php', array('id' => $id, 'instanceid' => $instanceid));

    $title = $course->shortname . ': ' . format_string(get_string('modulename', 'block_map'));
    $PAGE->set_title($title);
    $PAGE->set_heading($course->fullname);

    echo $OUTPUT->header();

    $areasselector = '<tr><td>'.get_string("edit:location","block_map").'</td><td><select name="areas">';
    foreach ((array)$areas as $key => $value) {
        $areasselector .= '<option value="'.$key.'">'.$value.'</option>';
    }
    $areasselector .= '</select></td></tr>';
    
?>
<script type="text/javascript" src="http://www.google.com/jsapi?key=<?php echo $CFG->map_googlekey; ?>" type="text/javascript"></script>
<script type="text/javascript" src="<?php echo $CFG->wwwroot; ?>/blocks/map/js/jquery-1.4.2.min.js"></script>
<script type="text/javascript" src="<?php echo $CFG->wwwroot; ?>/blocks/map/js/jquery.form.js"></script> 
<script type="text/javascript" charset="utf-8">
   google.load("maps", "2.x");
</script>
<style media="screen" type="text/css">
  #map { width:1000px; height:600px; font-size: 14px;}
</style>
<script type="text/javascript" charset="utf-8">
var geoXml;
var edit = 0;
var currentprogressmark = 0;
var mypoints = new Array();
var container = document.createElement("div");
var zoomInDiv = document.createElement("div");
var textDiv = document.createElement("div");
var textDiv2 = document.createElement("div");

$(document).ready(function(){
    var mapOptions = {
        googleBarOptions : {
          style : "new"
        }
    }
    var map = new GMap2($("#map").get(0));
    map.setCenter(new GLatLng(<?php echo $context->config->latitude; ?>,<?php echo $context->config->longitude; ?>), 7);
    map.setUIToDefault();
    map.addControl(new TextualZoomControl());
    map.enableGoogleBar();

    mgr = new GMarkerManager(map);
    
    <?php if ($context->config->loadmarkdin == 1) { ?>
    GEvent.addListener(map, "dragend", function() {
      updatemap();
    });
    
    GEvent.addListener(map, "zoomend", function() {
      updatemap();
    });
    <?php } ?>
    
    GEvent.addListener(map, "click", function(overlay,point) {
        if (point && window.edit == 1) {
            var marker = new GMarker(point, {draggable: true});
            var myHtml = '<div id="addpoint" style="width:400px;"><font color="green"><?php echo get_string("map:movemark","block_map"); ?></font><form id="formadd" name="formadd_point" enctype="multipart/form-data" method="post" action="<?php echo $CFG->wwwroot; ?>/blocks/map/pointadd.php" onsubmit="return false;" ><table><tr><td><?php echo get_string("map:position","block_map"); ?></td><td><small>' + point + '</small></td></tr><tr><td><?php echo get_string("map:title","block_map"); ?></td><td><input name="name" type="text" size="32" maxlength="200" /></td></tr><?php echo $areasselector; ?><tr><td><?php echo get_string("map:text","block_map"); ?></td><td><textarea name="description" cols="24" rows="3"></textarea></td></tr><tr><td><small><?php echo get_string("map:image","block_map"); ?></small></td><td><input type="file" name="image" size="20" id="photo" /></td></tr><tr><td></td><td><input name="pcoord" type="hidden" value="'+point+'" /><input name="zoom" type="hidden" value="'+map.getZoom()+'" /><input type="hidden" name="MAX_FILE_SIZE" value="2000000" /><input type="hidden" name="id" value="<?php echo block_map_checkshareid ($instanceid); ?>" /><input type="hidden" name="instanceid" value="<?php echo $instanceid; ?>" /><div style="float:left;padding-right:10px;"><input name="subpoint" type="submit" value="<?php echo get_string("map:add","block_map"); ?>" onclick="marksformsubmit();" /></div><div id="loader-mark" style="margin:4px;"></div></td></tr></table></form></div>';
            GEvent.addListener(marker, "dragstart", function() {
              map.closeInfoWindow();
            });
            
            GEvent.addListener(marker, "dragend", function(point) {
              var myHtml = '<div id="addpoint" style="width:400px;"><font color="green"><?php echo get_string("map:movemark","block_map"); ?></font><form id="formadd" name="formadd_point" enctype="multipart/form-data" method="post" action="<?php echo $CFG->wwwroot; ?>/blocks/map/pointadd.php" onsubmit="return false;" ><table><tr><td><?php echo get_string("map:position","block_map"); ?></td><td><small>' + point + '</small></td></tr><tr><td><?php echo get_string("map:title","block_map"); ?></td><td><input name="name" type="text" size="32" maxlength="200" /></td></tr><?php echo $areasselector; ?><tr><td><?php echo get_string("map:text","block_map"); ?></td><td><textarea name="description" cols="24" rows="3"></textarea></td></tr><tr><td><small><?php echo get_string("map:image","block_map"); ?></small></td><td><input type="file" name="image" size="20" id="photo" /></td></tr><tr><td></td><td><input name="pcoord" type="hidden" value="'+point+'" /><input name="zoom" type="hidden" value="'+map.getZoom()+'" /><input type="hidden" name="MAX_FILE_SIZE" value="2000000" /><input type="hidden" name="id" value="<?php echo block_map_checkshareid ($instanceid); ?>" /><input type="hidden" name="instanceid" value="<?php echo $instanceid; ?>" /><div style="float:left;padding-right:10px;"><input name="subpoint" type="submit" value="<?php echo get_string("map:add","block_map"); ?>" onclick="marksformsubmit();" /></div><div id="loader-mark" style="margin:4px;"></div></td></tr></table></form></div>';
              marker.openInfoWindowHtml(myHtml);
            });
            map.addOverlay(marker);
            marker.openInfoWindowHtml(myHtml);
            GEvent.addListener(marker, "infowindowclose", function(){
                if (window.edit == 0) {
                    map.removeOverlay(marker); 
                    updatemap();
                }
            });
        }
    });
        
    function updatemap() {
        GDownloadUrl("<?php echo $CFG->wwwroot; ?>/blocks/map/mapmarks.php?LeftBottom="+map.getBounds().getSouthWest().lng()+","+map.getBounds().getSouthWest().lat()+"&RightTop="+map.getBounds().getNorthEast().lng()+","+map.getBounds().getNorthEast().lat()+"&zoom="+map.getZoom()+"&courseid=<?php echo block_map_checkshareid ($instanceid); ?>&id=<?php echo $id; ?>&instanceid=<?php echo $instanceid; ?>", function(data) {
            var xml = GXml.parse(data);
            var markers = xml.documentElement.getElementsByTagName("marker");
            
            for (var i = 0; i < markers.length; i++) {
                var name = markers[i].getElementsByTagName("name")[0].childNodes[0].nodeValue;
                var address = markers[i].getElementsByTagName("description")[0].childNodes[0].nodeValue;
                var point = new GLatLng(parseFloat(markers[i].getAttribute("lat")),
                             parseFloat(markers[i].getAttribute("lng")));
                if (!mypoints[markers[i].getAttribute("id")]) {
                    mgr.addMarker(createMarker(point, name, address), markers[i].getAttribute("maxzoom"));
                    mypoints[markers[i].getAttribute("id")] = markers[i].getAttribute("id");
                }
            }
            mgr.refresh();
        });
    }
    function createMarker(point, name, address) {
        var marker = new GMarker(point);
        var html = address;
        GEvent.addListener(marker, 'click', function() {
            marker.openInfoWindowHtml(html);
        });
        return marker;
    }
    
    $('#gotopointid').live("click", function(){
        var brokenstring=this.href.split("#"); 
        var latlong=brokenstring[1].split(":"); 
        map.panTo(new GLatLng(latlong[0], latlong[1]), map.getZoom());
        $.post('<?php echo $CFG->wwwroot?>/blocks/map/dataareas.php?id=<?php echo $id;?>&instanceid=<?php echo $instanceid;?>&info=1&geo_x='+latlong[0]+'&geo_y='+latlong[1], function(data){
            map.openInfoWindowHtml(new GLatLng(latlong[0], latlong[1]), data);
        });
        
    });
    updatemap();
    
    <?php 
    if ($show == "auto") {
    ?>
        $.post('<?php echo $CFG->wwwroot?>/blocks/map/dataareas.php?id=<?php echo $id;?>&instanceid=<?php echo $instanceid;?>&info=1&geo_x=<?php echo $showx; ?>&geo_y=<?php echo $showy; ?>', function(data){
            map.openInfoWindowHtml(new GLatLng(<?php echo $showx; ?>, <?php echo $showy; ?>), data);
        });
    <?php
    }
    ?>
    
    var pos = window.location.href.indexOf("#");
    
    if (pos != -1) {
        var brokenstring=window.location.href.split("#"); 
        if (brokenstring) {
          var latlong=brokenstring[1].split(":"); 
          map.panTo(new GLatLng(latlong[0], latlong[1]), map.getZoom());
          $.post('<?php echo $CFG->wwwroot?>/blocks/map/dataareas.php?id=<?php echo $id;?>&instanceid=<?php echo $instanceid;?>&info=1&geo_x='+latlong[0]+'&geo_y='+latlong[1], function(data){
            map.openInfoWindowHtml(new GLatLng(latlong[0], latlong[1]), data);
          });
        }
    }
    
});
</script>
<script type="text/javascript" charset="utf-8">
function TextualZoomControl() {
}
TextualZoomControl.prototype = new GControl();
TextualZoomControl.prototype.initialize = function(map) {
    this.setButtonStyle_(zoomInDiv);
    container.appendChild(zoomInDiv);
    zoomInDiv.appendChild(textDiv);
    <?php if ($USER->id) { ?>
    textDiv.appendChild(document.createTextNode("<?php echo get_string("map:addmark","block_map"); ?>"));
    textDiv2.appendChild(document.createTextNode("<?php echo get_string("map:marklocation","block_map"); ?>"));
    GEvent.addDomListener(zoomInDiv, "click", function() {
        if (window.edit == 0) {
            zoomInDiv.removeChild(textDiv);
            zoomInDiv.appendChild(textDiv2);
            window.edit = 1;
        }
        else
        {
            zoomInDiv.removeChild(textDiv2);
            zoomInDiv.appendChild(textDiv);
            window.edit = 0;
        }
        
    });
    <?php } else { ?>
    textDiv.appendChild(document.createTextNode("<?php echo get_string("map:pleaselogoinaddmark","block_map"); ?>"));
    zoomInDiv.appendChild(textDiv);
    <?php } ?>
    map.getContainer().appendChild(container);
    return container;
}
TextualZoomControl.prototype.getDefaultPosition = function() {
    return new GControlPosition(G_ANCHOR_TOP_RIGHT, new GSize(200, 7));
}
TextualZoomControl.prototype.setButtonStyle_ = function(button) {
  button.style.textDecoration = "underline"
  button.style.color = "#669900"
  button.style.backgroundColor = "#fff"
  button.style.font = "small Arial"
  button.style.fontWeight = "bolder"
  button.style.border = "1px solid black"
  button.style.padding = "2px"
  button.style.marginBottom = "3px"
  button.style.marginRight = "510px"
  button.style.textAlign = "center"
  button.style.width = "210px"
  button.style.height = "15px"
  button.style.cursor = "pointer"
}
function validate(formData, jqForm, options) { 
    var form = document.getElementById("formadd"); 
    if (!form.name.value || !form.description.value) { 
        alert('<?php echo get_string("map:fillall","block_map"); ?>'); 
        return false; 
    } 
    $('#loader-mark').html('<img width="16" height="16" src="<?php echo $CFG->wwwroot; ?>/blocks/map/img/zoomloader.gif" alt="" />');
    return true;
}
function marksformsubmit() {
    $('#formadd').ajaxForm({ beforeSubmit: validate, target:'#addpoint' });
    zoomInDiv.removeChild(textDiv2);
    zoomInDiv.appendChild(textDiv);
    window.edit = 0;
    return false;
}
</script>

<?php

if (has_capability('block/map:teacher', get_context_instance(CONTEXT_COURSE, $course->id))) {
    $currenttab = $a;
    if (!isset($currenttab)) {
        $currenttab = 'map';
    }
    
    if (!empty($idh)) {
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

if ($context->config->publickmap)
    echo '<div><a href="map.php?id='.$id.'&instanceid='.$instanceid.'">'.get_string("map:public","block_map").'</a></div>';
    
if (!empty($message))
    echo '<div style="width:100%;text-align:center;"><h3>'.$message.'</h3></div>';
?>
<div style="width:1300px;"> 
<div style="padding-left:10px;"><?php echo get_string("map:search","block_map"); ?> <form id="searchform" method="post" action="map.php?id=<?php echo $id; ?>&instanceid=<?php echo $instanceid; ?>"><input type="text" style="width: 120px;" value="" name="searchtext"> <input type="submit" value="Search" name="submit"></form></div>
<div id="leftblock" style="float:left;width:250px;padding-left:10pt;margin:0;background:#ffffff;overflow:auto;height:500px;"><img width="16" height="16" src="<?php echo $CFG->wwwroot; ?>/blocks/map/img/zoomloader.gif" alt="" /></div>
<div id="map" style="padding:0;margin:0;float:left;"></div>
<script type="text/javascript">
$(document).ready(function(){
    $("#leftblock").load("<?php echo $CFG->wwwroot; ?>/blocks/map/dataareas.php?id=<?php echo $id;?>&instanceid=<?php echo $instanceid;?>");
    $('#searchform').ajaxForm({ beforeSubmit: validatesearch, target:'#leftblock' });
});
function changelocation(area) {
    $('#leftblock').html('<img width="16" height="16" src="<?php echo $CFG->wwwroot; ?>/blocks/map/img/zoomloader.gif" alt="" />');
    $("#leftblock").load("<?php echo $CFG->wwwroot; ?>/blocks/map/dataareas.php?id=<?php echo $id;?>&instanceid=<?php echo $instanceid;?>&area=" + area);
}
function validatesearch(formData, jqForm, options) { 
    var form = document.getElementById("searchform"); 
    if (!form.searchtext.value) { 
        alert('Please fill search field'); 
        return false; 
    } 
    $('#leftblock').html('<img width="16" height="16" src="<?php echo $CFG->wwwroot; ?>/blocks/map/img/zoomloader.gif" alt="" />');
    return true;
}
</script>
</div>
<div style="clear:both;"></div>
<?php
    echo $OUTPUT->footer();
?>