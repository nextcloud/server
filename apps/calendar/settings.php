<?php
/************************************************
* ownCloud - Calendar Plugin                    *
*                                               *
* (c) Copyright 2011 Georg Ehrke                *
* author: Georg Ehrke                           *
* email: ownclouddev at georgswebsite dot de    *
* homepage: http://ownclouddev.georgswebsite.de *
* License: GPL                                  *
* <http://www.gnu.org/licenses/>.               *
************************************************/
require_once('../../lib/base.php');
if(!OC_USER::isLoggedIn()){
	header("Location: " . OC_HELPER::linkTo("index.php"));
	exit();
}
if(!file_exists("cfg/" . OC_USER::getUser() . ".cfg.php")){
	header("Location: install.php");
}
OC_UTIL::addScript("calendar", "calendar");
OC_UTIL::addScript("calendar", "calendar_init");
OC_UTIL::addStyle("calendar", "style");
require_once("template.php");
OC_APP::setActiveNavigationEntry("calendar_settings");
$output = new OC_TEMPLATE("calendar", "settings", "admin");
$output->printpage();
?>