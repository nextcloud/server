<?php
/**
 * Copyright (c) 2012 Thomas Tanghus <thomas@tanghus.net>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

 
OCP\JSON::checkLoggedIn();
OCP\JSON::checkAppEnabled('journal');

$categories = OC_Journal_App::getCategories();

OCP\JSON::success(array('data' => array('categories'=>$categories)));

?>
