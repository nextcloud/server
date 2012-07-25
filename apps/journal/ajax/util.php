<?php
/**
 * ownCloud - Addressbook
 *
 * @author Thomas Tanghus
 * @copyright 2012 Thomas Tanghus <thomas@tanghus.net>
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU AFFERO GENERAL PUBLIC LICENSE for more details.
 *
 * You should have received a copy of the GNU Affero General Public
 * License along with this library.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

function bailOut($msg, $tracelevel=1, $debuglevel=OCP\Util::ERROR) {
	OCP\JSON::error(array('data' => array('message' => $msg)));
	debug($msg, $tracelevel, $debuglevel);
	exit();
}

function debug($msg, $tracelevel=0, $debuglevel=OCP\Util::DEBUG) {
	if(PHP_VERSION >= "5.4") {
		$call = debug_backtrace(false, $tracelevel+1);
	} else {
		$call = debug_backtrace(false);
	}
	error_log('trace: '.print_r($call, true));
	$call = $call[$tracelevel];
	if($debuglevel !== false) {
		OCP\Util::writeLog('journal', $call['file'].'. Line: '.$call['line'].': '.$msg, $debuglevel);
	}
}
