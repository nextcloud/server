<?php
/**
 * ownCloud - VObject String Property
 *
 * This class adds escaping of simple string properties.
 *
 * @author Thomas Tanghus
 * @author Evert Pot (http://www.rooftopsolutions.nl/)
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

namespace OC\VObject;

/**
 * This class overrides \Sabre\VObject\Property::serialize() properly
 * escape commas and semi-colons in string properties.
*/
class StringProperty extends \Sabre\VObject\Property {

	/**
	* Turns the object back into a serialized blob.
	*
	* @return string
	*/
	public function serialize() {

		$str = $this->name;
		if ($this->group) {
			$str = $this->group . '.' . $this->name;
		}

		foreach($this->parameters as $param) {
			$str.=';' . $param->serialize();
		}

		$src = array(
			'\\',
			"\n",
			';',
			',',
		);
		$out = array(
			'\\\\',
			'\n',
			'\;',
			'\,',
		);
		$value = strtr($this->value, array('\,' => ',', '\;' => ';', '\\\\' => '\\'));
		$str.=':' . str_replace($src, $out, $value);

		$out = '';
		while(strlen($str) > 0) {
			if (strlen($str) > 75) {
				$out .= mb_strcut($str, 0, 75, 'utf-8') . "\r\n";
				$str = ' ' . mb_strcut($str, 75, strlen($str), 'utf-8');
			} else {
				$out .= $str . "\r\n";
				$str = '';
				break;
			}
		}

		return $out;

	}

}
