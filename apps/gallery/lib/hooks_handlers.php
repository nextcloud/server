<?php

/**
* ownCloud - gallery application
*
* @author Bartek Przybylski
* @copyright 2012 Bartek Przybylski bart.p.pl@gmail.com
* 
* This library is free software; you can redistribute it and/or
* modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
* License as published by the Free Software Foundation; either 
* version 3 of the License, or any later version.
* 
* This library is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.	See the
* GNU AFFERO GENERAL PUBLIC LICENSE for more details.
*	
* You should have received a copy of the GNU Lesser General Public 
* License along with this library.	If not, see <http://www.gnu.org/licenses/>.
* 
*/

OCP\Util::connectHook(OC_Filesystem::CLASSNAME, OC_Filesystem::signal_delete, "OC_Gallery_Hooks_Handlers", "removePhoto");
//OCP\Util::connectHook(OC_Filesystem::CLASSNAME, OC_Filesystem::signal_post_rename, "OC_Gallery_Hooks_Handlers", "renamePhoto");

require_once(OC::$CLASSPATH['Pictures_Managers']);

class OC_Gallery_Hooks_Handlers {

	public static function removePhoto($params) {
		\OC\Pictures\ThumbnailsManager::getInstance()->delete($params[OC_Filesystem::signal_param_path]);
	}

	public static function renamePhoto($params) {
		$oldpath = $params[OC_Filesystem::signal_param_oldpath];
		$newpath = $params[OC_Filesystem::signal_param_newpath];
		//TODO: implement this
	}
}

?>
