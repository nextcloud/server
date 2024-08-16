<?php
/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\RichObjectStrings;

use OCP\RichObjectStrings\Definitions;
use OCP\RichObjectStrings\InvalidObjectExeption;
use OCP\RichObjectStrings\IValidator;

/**
 * Class Validator
 *
 * @package OCP\RichObjectStrings
 * @since 11.0.0
 */
class Validator implements IValidator {
	/** @var Definitions */
	protected $definitions;

	/** @var array[] */
	protected $requiredParameters = [];

	/**
	 * Constructor
	 *
	 * @param Definitions $definitions
	 */
	public function __construct(Definitions $definitions) {
		$this->definitions = $definitions;
	}

	/**
	 * @param string $subject
	 * @param array[] $parameters
	 * @throws InvalidObjectExeption
	 * @since 11.0.0
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
				}
			}
		}

		foreach ($parameters as $parameter) {
			if (!\is_array($parameter)) {
				throw new InvalidObjectExeption('Parameter is malformed');
			}

			$this->validateParameter($parameter);
		}
	}

	/**
	 * @param array $parameter
	 * @throws InvalidObjectExeption
	 */
	protected function validateParameter(array $parameter) {
		if (!isset($parameter['type'])) {
			throw new InvalidObjectExeption('Object type is undefined');
		}

		$definition = $this->definitions->getDefinition($parameter['type']);
		$requiredParameters = $this->getRequiredParameters($parameter['type'], $definition);

		$missingKeys = array_diff($requiredParameters, array_keys($parameter));
		if (!empty($missingKeys)) {
			throw new InvalidObjectExeption('Object is invalid, missing keys:'.json_encode($missingKeys));
		}
	}

	/**
	 * @param string $type
	 * @param array $definition
	 * @return string[]
	 */
	protected function getRequiredParameters($type, array $definition) {
		if (isset($this->requiredParameters[$type])) {
			return $this->requiredParameters[$type];
		}

		$this->requiredParameters[$type] = [];
		foreach ($definition['parameters'] as $parameter => $data) {
			if ($data['required']) {
				$this->requiredParameters[$type][] = $parameter;
			}
		}

		return $this->requiredParameters[$type];
	}
}
