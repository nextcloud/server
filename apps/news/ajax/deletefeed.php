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

$feedid = $_POST['feedid'];

$feedmapper = new OC_News_FeedMapper();
$success = $feedmapper->deleteById($feedid);

$l = OC_L10N::get('news');

if(!$success) {
	OCP\JSON::error(array('data' => array('message' => $l->t('Error removing feed.'))));
	OCP\Util::writeLog('news','ajax/deletefeed.php: Error removing feed: '.$_POST['feedid'], OCP\Util::ERROR);
	exit();
}

OCP\JSON::success(array('data' => array( 'feedid' => $feedid )));
