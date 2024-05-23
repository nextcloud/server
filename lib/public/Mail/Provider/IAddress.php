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
 * Mail Address Interface
 * 
 * This interface is a base requirement of methods and functionality used to construct a mail address object
 * 
 * @since 30.0.0
 */
interface IAddress {
	
	/**
	 * sets the mail address
	 * 
	 * @since 30.0.0
	 * @param string $value     mail address (test@example.com)
	 * @return self				returns the current object
	 */
	public function setAddress(string $value): self;

	/**
	 * gets the mail address
	 * 
	 * @since 30.0.0
	 * @return string			returns the mail address
	 */
	public function getAddress(): string | null;

	/**
	 * sets the mail address label/name
	 * 
	 * @since 30.0.0
	 * @param string $value     mail address label/name
	 * @return self				returns the current object
	 */
	public function setLabel(string $value): self;

	/**
	 * gets the mail address label/name
	 * 
	 * @since 30.0.0
	 * @return string			returns the mail address label/name
	 */
	public function getLabel(): string | null;

}
