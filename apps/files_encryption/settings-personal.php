<?php
/**
 * Copyright (c) 2012 Bjoern Schiessle <schiessle@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

$sysEncMode = \OC_Appconfig::getValue('files_encryption', 'mode', 'none');

if ($sysEncMode == 'user') {

	$tmpl = new OCP\Template( 'files_encryption', 'settings-personal');

	$query = \OC_DB::prepare( "SELECT mode FROM *PREFIX*encryption WHERE uid = ?" );
	$result = $query->execute(array(\OCP\User::getUser()));
	
	if ($row = $result->fetchRow()){
		$mode = $row['mode'];
	} else {
		$mode = 'none';
	}
	
	OCP\Util::addscript('files_encryption','settings-personal');
	$tmpl->assign('encryption_mode', $mode);
	return $tmpl->fetchPage();
}

return null;
