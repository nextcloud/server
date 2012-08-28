<?php
/**
 * ownCloud - OC_Connector_Sabre_CalDAV_CalendarRoot
 *
 * @author Thomas Tanghus
 * @copyright 2012 Thomas Tanghus (thomas@tanghus.net)
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
 * This class overrides Sabre_CalDAV_CalendarRootNode::getChildForPrincipal()
 * to instantiate OC_Connector_Sabre_CalDAV_UserCalendars.
*/
class OC_Connector_Sabre_CalDAV_CalendarRoot extends Sabre_CalDAV_CalendarRootNode {

	/**
	* This method returns a node for a principal.
	*
	* The passed array contains principal information, and is guaranteed to
	* at least contain a uri item. Other properties may or may not be
	* supplied by the authentication backend.
	*
	* @param array $principal
	* @return Sabre_DAV_INode
	*/
	public function getChildForPrincipal(array $principal) {

		return new OC_Connector_Sabre_CalDAV_UserCalendars($this->principalBackend, $this->caldavBackend, $principal['uri']);

	}

}