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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU AFFERO GENERAL PUBLIC LICENSE for more details.
 *  
 * You should have received a copy of the GNU Lesser General Public 
 * License along with this library.  If not, see <http://www.gnu.org/licenses/>.
 * 
 */

class OC_External {

	public static function getSites() {
		if (($sites = json_decode(OCP\Config::getAppValue("external", "sites", ''))) != NULL) {
			return $sites;	
		}

		return array();
	}

}

?>
