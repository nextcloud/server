<?php
/**
 * ownCloud
 *
 * @author Georg Ehrke
 * @copyright 2012 georg@owncloud.com
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
class OC_Exception extends Exception{
	
	function __construct($message = null, $code = 0, $file = null, $line = null){
		parent::__construct($message, $code);
		if(!is_null($file)){
			$this->file = $file;
		}
		if(!is_null($line)){
			$this->line = $line;
		}
		$this->writelog();
	}
	
	private function writelog(){
		@OC_Log::write(OC_App::getCurrentApp(), $this->getMessage() . '-' . $this->getFile() . '-' . $this->getLine(), OC_Log::FATAL);
	}
	
	private function generatesysinfo(){
		return array('phpversion' => PHP_VERSION,
					 'os' => php_uname('s'),
					 'osrelease' => php_uname('r'),
					 'osarchitecture' => php_uname('m'),
					 'phpserverinterface' => php_sapi_name(),
					 'serverprotocol' => $_SERVER['SERVER_PROTOCOL'],
					 'requestmethod' => $_SERVER['REQUEST_METHOD'],
					 'https' => ($_SERVER['HTTPS']==''?'false':'true'),
					 'database'=>(@OC_Config::getValue('dbtype')!=''?@OC_Config::getValue('dbtype'):'')
					);
	}
	
	function __toString(){
		$tmpl = new OC_Template('core', 'exception', 'guest');
		$tmpl->assign('showsysinfo', true);
		$tmpl->assign('message', $this->getMessage());
		$tmpl->assign('code', $this->getCode());
		$tmpl->assign('file', $this->getFile());
		$tmpl->assign('line', $this->getLine());
		$tmpl->assign('sysinfo', $this->generatesysinfo());
		$tmpl->printPage();
	}
}

function oc_exceptionhandler($exception){
	throw new OC_Exception($exception->getMessage(), $exception->getCode(), $exception->getFile(), $exception->getLine());
	return true;
}

function oc_errorhandler(){
	
}
set_exception_handler('oc_exceptionhandler');
set_error_handler('oc_errorhandler');
error_reporting(E_ERROR | E_WARNING | E_PARSE);