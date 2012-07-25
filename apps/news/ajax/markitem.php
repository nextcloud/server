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

$itemid = $_POST['itemid'];

$itemmapper = new OC_News_ItemMapper();
$item = $itemmapper->find($itemid);
$item->setRead();
$success = $itemmapper->update($item);

$l = OC_L10N::get('news');

if(!$success) {
	OCP\JSON::error(array('data' => array('message' => $l->t('Error marking item as read.'))));
	OCP\Util::writeLog('news','ajax/markitem.php: Error marking item as read: '.$_POST['itemid'], OCP\Util::ERROR);
	exit();
}

//TODO: replace the following with a real success case. see contact/ajax/createaddressbook.php for inspirations
OCP\JSON::success(array('data' => array('itemid' => $itemid )));

