<?php
/**
 * ownCloud
 *
 * @author Tom Needham
 * @copyright 2012 Tom Needham tom@owncloud.com
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
 * provides an interface to all search providers
 */
class OC_Migrate{
	static private $providers=array();
	
	/**
	 * register a new migration provider
	 * @param OC_Migrate_Provider $provider
	 */
	public static function registerProvider($provider){
		self::$providers[]=$provider;
	}
	
	/**
	 * export app data for a user
	 * @param string userid
	 * @return string xml of app data
	 */
	public static function export($uid){
		
		// Only export database users, otherwise we get chaos
		if(OC_User_Database::userExists($uid)){
				
			$data = array();
			$data['userid'] = OC_User::getUser();
			
			$query = OC_DB::prepare( "SELECT uid, password FROM *PREFIX*users WHERE uid LIKE ?" );
			$result = $query->execute( array( $uid));
	
			$row = $result->fetchRow();
			if($row){
				$data['hash'] = $row['password'];
			} else {
				return false;
				exit();	
			}
			
			foreach(self::$providers as $provider){
				
				$data['apps'][$prodider->appid]['info'] = OC_App::getAppInfo($provider->appid);
				$data['apps'][$provider->appid]['data'] = $provider->export($uid);
	
			}
	
			return self::indent(json_encode($data));
		
		} else {
			return false;	
		}
		
	}
	
	/**
	 * @breif imports a new user
	 * @param $data json data for the user
	 * @param $uid optional uid to use
	 * @return json reply
	 */
	 public function import($data,$uid=null){
	 	
	 	// Import the data
	 	$data = json_decode($data);
	 	if(is_null($data)){
	 		// TODO LOG
	 		return false;
	 		exit();	
	 	}
	 	
	 	// Specified user or use original
	 	$uid = !is_null($uid) ? $uid : $data['userid'];
	 	
	 	// Check if userid exists
	 	if(OC_User::userExists($uid)){
	 		// TODO LOG
	 		return false;
	 		exit();	
	 	}
	 	
	 	// Create the user
	 	$query = OC_DB::prepare( "INSERT INTO `*PREFIX*users` ( `uid`, `password` ) VALUES( ?, ? )" );
		$result = $query->execute( array( $uid, $data['hash']));
		if(!$result){
			// TODO LOG
			return false;
			exit();	
		}
	 	
	 	foreach($data['app'] as $app){
	 		// Check if supports migration and is enabled
	 		if(in_array($app, self::$providers)){
	 			if(OC_App::isEnabled($app)){
	 				$provider->import($data['app'][$app],$uid);	
	 			}
	 		}
	 			
	 	}
	 		
	 }
	 
	 private static function indent($json){

		$result      = '';
		$pos         = 0;
		$strLen      = strlen($json);
		$indentStr   = '  ';
		$newLine     = "\n";
		$prevChar    = '';
		$outOfQuotes = true;
		
		for ($i=0; $i<=$strLen; $i++) {
		
		    // Grab the next character in the string.
		    $char = substr($json, $i, 1);
		
		    // Are we inside a quoted string?
		    if ($char == '"' && $prevChar != '\\') {
		        $outOfQuotes = !$outOfQuotes;
		    
		    // If this character is the end of an element, 
		    // output a new line and indent the next line.
		    } else if(($char == '}' || $char == ']') && $outOfQuotes) {
		        $result .= $newLine;
		        $pos --;
		        for ($j=0; $j<$pos; $j++) {
		            $result .= $indentStr;
		        }
		    }
		    
		    // Add the character to the result string.
		    $result .= $char;
		
		    // If the last character was the beginning of an element, 
		    // output a new line and indent the next line.
		    if (($char == ',' || $char == '{' || $char == '[') && $outOfQuotes) {
		        $result .= $newLine;
		        if ($char == '{' || $char == '[') {
		            $pos ++;
		        }
		        
		        for ($j = 0; $j < $pos; $j++) {
		            $result .= $indentStr;
		        }
		    }
		    
		    $prevChar = $char;
		}
		
		return $result;	
	 }
	
}
