<?php
/**
 * Copyright (c) 2013 Georg Ehrke georg@ownCloud.com
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */
if(shell_exec('libreoffice') || shell_exec('openoffice')) {
	require_once('libreoffice-cl.php');
}else{
	require_once('msoffice.php');
	require_once('opendocument.php');
}