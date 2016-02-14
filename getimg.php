<?php

include_once("../../config.php");
header('Content-type: '.mime_content_type($CFG->dataroot.$_GET['link']));

readfile($CFG->dataroot.$_GET['link']);
