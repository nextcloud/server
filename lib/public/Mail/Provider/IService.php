<?php
declare(strict_types=1);

/**
* @copyright Copyright (c) 2023 Sebastian Krupinski <krupinski01@gmail.com>
*
* @author Sebastian Krupinski <krupinski01@gmail.com>
*
* @license AGPL-3.0-or-later
*
* This program is free software: you can redistribute it and/or modify
* it under the terms of the GNU Affero General Public License as
* published by the Free Software Foundation, either version 3 of the
* License, or (at your option) any later version.
*
* This program is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
* GNU Affero General Public License for more details.
*
* You should have received a copy of the GNU Affero General Public License
* along with this program.  If not, see <http://www.gnu.org/licenses/>.
*
*/
namespace OCP\Mail\Provider;

/**
 * Mail Service Interface
 * 
 * This interface is used for defining individual services that are configured for a provider
 * 
 * @since 30.0.0
 */
interface IService {

	/**
	 * An arbitrary unique text string identifying this service
	 * 
	 * @since 30.0.0
	 * @return string			id of this service (e.g. 1 or service1 or anything else)
	 */
	public function id(): string;

	/**
	 * gets the localized human frendly name of this service
	 * 
	 * @since 30.0.0
	 * @return string			label/name of service (e.g. ACME Company Mail Service)
	 */
	public function getLabel(): string;

	/**
	 * sets the localized human frendly name of this service
	 * 
	 * @since 30.0.0
	 * @param string $value		label/name of service (e.g. ACME Company Mail Service)
	 */
	public function setLabel(string $value);

	/**
	 * get service itentity
	 * 
	 * @since 30.0.0
	 * @return IServiceIdentity				service identity object
	 */
	public function getIdentity(): IServiceIdentity;

	/**
	 * set service identity
	 * 
	 * @since 30.0.0
	 * @param IServiceIdentity $identity	service identity object
	 */
	public function setIdentity(IServiceIdentity $identity);

	/**
	 * get service location
	 * 
	 * @since 30.0.0
	 * @return IServiceLocation				service location object
	 */
	public function getLocation(): IServiceLocation;

	/**
	 * set service location
	 * 
	 * @since 30.0.0
	 * @param IServiceLocation $location	service location object
	 */
	public function setLocation(IServiceLocation $location);

}
