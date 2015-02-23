<?php
/**
 * @author Christopher Schäpers <kondou@ts.unde.re>
 * @author Thomas Tanghus <thomas@tanghus.net>
 *
 * @copyright Copyright (c) 2015, ownCloud, Inc.
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */
namespace OC\VObject;

/**
 * This class overrides \Sabre\VObject\Property::serialize() to not
 * double escape commas and semi-colons in compound properties.
*/
class CompoundProperty extends \Sabre\VObject\Property\Compound {

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
			"\n",
		);
		$out = array(
			'\n',
		);
		$str.=':' . str_replace($src, $out, $this->value);

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
