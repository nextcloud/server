<?php

require_once('../../../lib/base.php');
OC_JSON::checkLoggedIn();
OC_JSON::checkAppEnabled('gallery');

OC_JSON::success(array('albums' => OC_Gallery_Scanner::scan('')));
//OC_JSON::success(array('albums' => array(array('name' => 'test', 'imagesCount' => 1, 'images' => array('dupa')))));

?>
