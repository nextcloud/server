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
namespace OC\Mail\Provider;

class Address implements \OCP\Mail\Provider\IAddress {

    protected string $address;
    protected string $name;

    public function __construct(string $address = '', string $name = '') {
        $this->address = $address;
        $this->name = $name;
    }

    public function setAddress(string $value): self {
        $this->address = $value;
    }

    public function getAddress() : string {
        return $this->address;        
    }

    public function setName(string $value): self {
        $this->name = $value;
    }

    public function getName() : string {
        return $this->name;        
    }

}
