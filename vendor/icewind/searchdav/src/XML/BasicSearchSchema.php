<?php declare(strict_types=1);
/**
 * @copyright Copyright (c) 2017 Robin Appelman <robin@icewind.nl>
 *
 * @license GNU AGPL version 3 or any later version
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

namespace SearchDAV\XML;

use Sabre\Xml\Writer;
use Sabre\Xml\XmlSerializable;

class BasicSearchSchema implements XmlSerializable {
	/** @var PropDesc[] */
	public $properties;

	/**
	 * BasicSearchSchema constructor.
	 *
	 * @param PropDesc[] $properties
	 */
	public function __construct(array $properties) {
		$this->properties = $properties;
	}

	public function xmlSerialize(Writer $writer): void {
		$childs = array_map(function (PropDesc $propDesc) {
			return [
				'name'  => '{DAV:}propdesc',
				'value' => $propDesc
			];
		}, $this->properties);
		$writer->writeElement('{DAV:}properties', $childs);
	}
}
