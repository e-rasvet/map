<?php

    require_once "../../config.php";

    $id               = optional_param('id', 0, PARAM_INT); 
    $idc              = optional_param('idc', 0, PARAM_INT); 
    $instanceid       = optional_param('instanceid', NULL, PARAM_CLEAN); 
    
    $data = $DB->get_record("map_items", array("id"=>$idc));
    
    
?><form action="admin.php?id=<?php echo $id; ?>&a=admin&instanceid=<?php echo $instanceid; ?>&idc=<?php echo $idc; ?>" name="text-editor" method="post">
<?php
    print_textarea(false, 15, 60, 600, 300, 'text', stripslashes($data->descr));
    //use_html_editor();  
?>
<br />
<input type="submit" name="Update" value="Update" style="font-size: 16pt;" />
</form>