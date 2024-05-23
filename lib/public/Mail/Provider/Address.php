<?php
declare(strict_types=1);

/**
* @copyright Copyright (c) 2024 Sebastian Krupinski <krupinski01@gmail.com>
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
 * Mail Address Object
 * 
 * This object is used to define the address and label of a email address
 * 
 * @since 30.0.0
 */
class Address implements \OCP\Mail\Provider\IAddress {

    /**
	 * initialize the mail address object
	 * 
	 * @since 30.0.0
     * @param string|null $address    mail address (e.g test@example.com)
     * @param string|null $label      mail address label/name
	 */
    public function __construct(
        protected ?string $address = null, 
        protected ?string $label = null
    ) {
    }

    /**
	 * sets the mail address
	 * 
	 * @since 30.0.0
     * @param string $value     mail address (e.g. test@example.com)
	 * @return self		        returns the current object
	 */
    public function setAddress(string $value): self {
        $this->address = $value;
    }

    /**
	 * gets the mail address
	 * 
	 * @since 30.0.0
	 * @return string|null	    returns the mail address or null if one is not set
	 */
    public function getAddress(): string | null {
        return $this->address;        
    }

    /**
	 * sets the mail address label/name
	 * 
	 * @since 30.0.0
     * @param string $value     mail address label/name
	 * @return self			    returns the current object
	 */
    public function setLabel(string $value): self {
        $this->label = $value;
    }

    /**
	 * gets the mail address label/name
	 * 
	 * @since 30.0.0
	 * @return string|null      returns the mail address label/name or null if one is not set
	 */
    public function getLabel(): string | null {
        return $this->label;        
    }

}
