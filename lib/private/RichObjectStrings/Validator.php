<?php

declare(strict_types=1);
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
 * @psalm-import-type RichObjectParameter from IValidator
 * @package OCP\RichObjectStrings
 * @since 11.0.0
 */
class Validator implements IValidator {
	protected array $requiredParameters = [];

	public function __construct(
		protected Definitions $definitions,
	) {
	}

	/**
	 * @param string $subject
	 * @param array<non-empty-string, RichObjectParameter> $parameters
	 * @throws InvalidObjectExeption
	 * @since 11.0.0
	 */
	public function validate(string $subject, array $parameters): void {
		$matches = [];
		$result = preg_match_all('/\{(' . self::PLACEHOLDER_REGEX . ')\}/', $subject, $matches);

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

		foreach ($parameters as $placeholder => $parameter) {
			if (!\is_string($placeholder) || !preg_match('/^(' . self::PLACEHOLDER_REGEX . ')$/i', $placeholder)) {
				throw new InvalidObjectExeption('Parameter key is invalid');
			}
			if (!\is_array($parameter)) {
				throw new InvalidObjectExeption('Parameter is malformed');
			}

			$this->validateParameter($placeholder, $parameter);
		}
	}

	/**
	 * @param array $parameter
	 * @throws InvalidObjectExeption
	 */
	protected function validateParameter(string $placeholder, array $parameter): void {
		if (!isset($parameter['type'])) {
			throw new InvalidObjectExeption('Object type is undefined');
		}

		$definition = $this->definitions->getDefinition($parameter['type']);
		$requiredParameters = $this->getRequiredParameters($parameter['type'], $definition);

		$missingKeys = array_diff($requiredParameters, array_keys($parameter));
		if (!empty($missingKeys)) {
			throw new InvalidObjectExeption('Object for placeholder ' . $placeholder . ' is invalid, missing keys:' . json_encode($missingKeys));
		}

		foreach ($parameter as $key => $value) {
			if (!is_string($key)) {
				throw new InvalidObjectExeption('Object for placeholder ' . $placeholder . ' is invalid, key ' . $key . ' is not a string');
			}
			if (!is_string($value)) {
				throw new InvalidObjectExeption('Object for placeholder ' . $placeholder . ' is invalid, value ' . $value . ' for key ' . $key . ' is not a string');
			}
		}
	}

	/**
	 * @param string $type
	 * @param array $definition
	 * @return string[]
	 */
	protected function getRequiredParameters(string $type, array $definition): array {
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
