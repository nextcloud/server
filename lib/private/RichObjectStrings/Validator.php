<?php
/**
 * @copyright Copyright (c) 2016 Joas Schilling <coding@schilljs.com>
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

namespace OC\RichObjectStrings;


use OCP\RichObjectStrings\InvalidObjectExeption;
use OCP\RichObjectStrings\IValidator;

/**
 * Class Validator
 *
 * @package OCP\RichObjectStrings
 * @since 9.2.0
 */
class Validator implements IValidator  {

	/** @var array[] */
	protected $definitions;

	/** @var array[] */
	protected $requiredParameters = [];

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->definitions = json_decode(file_get_contents(__DIR__ . '/../../public/RichObjectStrings/definitions.json'), true);
	}

	/**
	 * @param string $subject
	 * @param array[] $parameters
	 * @throws InvalidObjectExeption
	 * @since 9.2.0
	 */
	public function validate($subject, array $parameters) {
		$matches = [];
		$result = preg_match_all('/\{([a-z0-9]+)\}/i', $subject, $matches);

		if ($result === false) {
			throw new InvalidObjectExeption();
		}

		if (!empty($matches[1])) {
			foreach ($matches[1] as $parameter) {
				if (!isset($parameters[$parameter])) {
					throw new InvalidObjectExeption('Parameter is undefined');
				} else {
					$this->validateParameter($parameters[$parameter]);
				}
			}
		}
	}

	/**
	 * @param array $parameter
	 * @throws InvalidObjectExeption
	 */
	protected function validateParameter(array $parameter) {
		if (!isset($parameter['type']) || !isset($this->definitions[$parameter['type']])) {
			throw new InvalidObjectExeption('Object type is undefined');
		}

		$requiredParameters = $this->getRequiredParameters($parameter['type']);
		$missingKeys = array_diff($requiredParameters, array_keys($parameter));
		if (!empty($missingKeys)) {
			throw new InvalidObjectExeption('Object is invalid');
		}
	}

	/**
	 * @param string $type
	 * @return string[]
	 */
	protected function getRequiredParameters($type) {
		if (isset($this->requiredParameters[$type])) {
			return $this->requiredParameters[$type];
		}

		$this->requiredParameters[$type] = [];
		foreach ($this->definitions[$type]['parameters'] as $parameter => $data) {
			if ($data['required']) {
				$this->requiredParameters[$type][] = $parameter;
			}
		}

		return $this->requiredParameters[$type];
	}
}
