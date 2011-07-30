<?php

/**
* ownCloud
*
* @author Frank Karlitschek
* @copyright 2010 Frank Karlitschek karlitschek@kde.org
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
 * Class for connecting multiply ownCloud installations
 *
 */
class OC_Connect{
	static private $clouds=array();

	static function connect($path,$user,$password){
		$cloud=new OC_REMOTE_CLOUD($path,$user,$password);
		if($cloud->connected){
			self::$clouds[$path]=$cloud;
			return $cloud;
		}else{
			return false;
		}
	}
}
