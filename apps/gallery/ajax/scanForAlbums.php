<?php

require_once('../../../lib/base.php');
OC_JSON::checkLoggedIn();
OC_JSON::checkAppEnabled('gallery');
require_once('../lib_scanner.php');

OC_JSON::success(array('albums' => OC_GALLERY_SCANNER::scan('')));
//OC_JSON::success(array('albums' => array(array('name' => 'test', 'imagesCount' => 1, 'images' => array('dupa')))));

?>
