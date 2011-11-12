<?php
/**
 * ownCloud
 *
 * @author Robin Appelman
 * @copyright 2011 Robin Appelman icewind1991@gmail.com
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

/**
 *logging utilities
 *
 * Log is saved at data/owncloud.log (on default)
 */

class OC_Log{
	const DEBUG=0;
	const INFO=1;
	const WARN=2;
	const ERROR=3;
	const FATAL=4;

	/**
	 * write a message in the log
	 * @param string $app
	 * @param string $message
	 * @param int level
	 */
	public static function write($app,$message,$level){
		$minLevel=OC_Config::getValue( "loglevel", 2 );
		if($level>=$minLevel){
			$datadir=OC_Config::getValue( "datadirectory", OC::$SERVERROOT.'/data' );
			$logFile=OC_Config::getValue( "logfile", $datadir.'/owncloud.log' );
			$entry=array('app'=>$app,'message'=>$message,'level'=>$level,'time'=>time());
			$fh=fopen($logFile,'a');
			fwrite($fh,json_encode($entry)."\n");
			fclose($fh);
		}
	}
	
	public static function getEntries(){
		$datadir=OC_Config::getValue( "datadirectory", OC::$SERVERROOT.'/data' );
		$logFile=OC_Config::getValue( "logfile", $datadir.'/owncloud.log' );
		$entries=array();
		if(!file_exists($logFile)){
			return array();
		}
		$fh=fopen($logFile,'r');
		if($fh === false){ // Unable to read log file!
			return array();
		}
		while(!feof($fh)){
			$line=fgets($fh);
			if($line){
				$entries[]=json_decode($line);
			}
		}
		fclose($fh);
		return $entries;
	}
}
