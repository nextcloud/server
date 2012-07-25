<?php
/**
* ownCloud - News app
*
* @author Alessandro Cosentino
* Copyright (c) 2012 - Alessandro Cosentino <cosenal@gmail.com>
*
* This file is licensed under the Affero General Public License version 3 or later.
* See the COPYING-README file
*
*/

// Check if we are a user
OCP\JSON::checkLoggedIn();
OCP\JSON::checkAppEnabled('news');
OCP\JSON::callCheck();

$userid = OCP\USER::getUser();

$feedurl = trim($_POST['feedurl']);
$folderid = trim($_POST['folderid']);

$feed = OC_News_Utils::fetch($feedurl);
$feedmapper = new OC_News_FeedMapper();
$feedid = $feedmapper->save($feed, $folderid);

$l = OC_L10N::get('news');

if(!$feedid) {
	OCP\JSON::error(array('data' => array('message' => $l->t('Error adding folder.'))));
	OCP\Util::writeLog('news','ajax/createfeed.php: Error adding feed: '.$_POST['feedurl'], OCP\Util::ERROR);
	exit();
}

//TODO: replace the following with a real success case. see contact/ajax/createaddressbook.php for inspirations
OCP\JSON::success(array('data' => array('message' => $l->t('Feed added!'))));

