<?php
/**
 * Copyright (c) 2012, Tom Needham <tom@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or later.
 * See the COPYING-README file.
 */

require_once('../lib/base.php');

// Logic
$operation = isset($_GET['operation']) ? $_GET['operation'] : '';
switch($operation){
	
	case 'register':
		
	break;
	
	case 'request_token':
	break;
	
	case 'authorise';
		// Example
		$consumer = array(
			'name' => 'Firefox Bookmark Sync',
			'scopes' => array('bookmarks'),
		);
		
		$t = new OC_Template('settings', 'oauth', 'guest');
		$t->assign('consumer', $consumer);
		$t->printPage();
	break;
	
	case 'access_token';
	break;
	
	default:
		// Something went wrong
		header('Location: /');
	break;
	
}
