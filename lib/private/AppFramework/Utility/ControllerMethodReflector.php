<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\AppFramework\Utility;

use OCP\AppFramework\Utility\IControllerMethodReflector;

/**
 * Reads and parses annotations from doc comments
 */
class ControllerMethodReflector implements IControllerMethodReflector {
	public $annotations = [];
	private $types = [];
	private $parameters = [];
	private array $ranges = [];

	/**
	 * @param object $object an object or classname
	 * @param string $method the method which we want to inspect
	 */
	public function reflect($object, string $method) {
		$reflection = new \ReflectionMethod($object, $method);
		$docs = $reflection->getDocComment();

		if ($docs !== false) {
			// extract everything prefixed by @ and first letter uppercase
			preg_match_all('/^\h+\*\h+@(?P<annotation>[A-Z]\w+)((?P<parameter>.*))?$/m', $docs, $matches);
			foreach ($matches['annotation'] as $key => $annotation) {
				$annotation = strtolower($annotation);
				$annotationValue = $matches['parameter'][$key];
				if (str_starts_with($annotationValue, '(') && str_ends_with($annotationValue, ')')) {
					$cutString = substr($annotationValue, 1, -1);
					$cutString = str_replace(' ', '', $cutString);
					$splitArray = explode(',', $cutString);
					foreach ($splitArray as $annotationValues) {
						[$key, $value] = explode('=', $annotationValues);
						$this->annotations[$annotation][$key] = $value;
					}
					continue;
				}

				$this->annotations[$annotation] = [$annotationValue];
			}

			// extract type parameter information
			preg_match_all('/@param\h+(?P<type>\w+)\h+\$(?P<var>\w+)/', $docs, $matches);
			$this->types = array_combine($matches['var'], $matches['type']);
			preg_match_all('/@psalm-param\h+(?P<type>\w+)<(?P<rangeMin>(-?\d+|min)),\h*(?P<rangeMax>(-?\d+|max))>\h+\$(?P<var>\w+)/', $docs, $matches);
			foreach ($matches['var'] as $index => $varName) {
				if ($matches['type'][$index] !== 'int') {
					// only int ranges are possible at the moment
					// @see https://psalm.dev/docs/annotating_code/type_syntax/scalar_types
					continue;
				}
				$this->ranges[$varName] = [
					'min' => $matches['rangeMin'][$index] === 'min' ? PHP_INT_MIN : (int)$matches['rangeMin'][$index],
					'max' => $matches['rangeMax'][$index] === 'max' ? PHP_INT_MAX : (int)$matches['rangeMax'][$index],
				];
			}
		}

		foreach ($reflection->getParameters() as $param) {
			// extract type information from PHP 7 scalar types and prefer them over phpdoc annotations
			$type = $param->getType();
			if ($type instanceof \ReflectionNamedType) {
				$this->types[$param->getName()] = $type->getName();
			}

			$default = null;
			if ($param->isOptional()) {
				$default = $param->getDefaultValue();
			}
			$this->parameters[$param->name] = $default;
		}
	}

	/**
	 * Inspects the PHPDoc parameters for types
	 * @param string $parameter the parameter whose type comments should be
	 *                          parsed
	 * @return string|null type in the type parameters (@param int $something)
	 *                     would return int or null if not existing
	 */
	public function getType(string $parameter) {
		if (array_key_exists($parameter, $this->types)) {
			return $this->types[$parameter];
		}

		return null;
	}

	public function getRange(string $parameter): ?array {
		if (array_key_exists($parameter, $this->ranges)) {
			return $this->ranges[$parameter];
		}

		return null;
	}

	/**
	 * @return array the arguments of the method with key => default value
	 */
	public function getParameters(): array {
		return $this->parameters;
	}

	/**
	 * Check if a method contains an annotation
	 * @param string $name the name of the annotation
	 * @return bool true if the annotation is found
	 */
	public function hasAnnotation(string $name): bool {
		$name = strtolower($name);
		return array_key_exists($name, $this->annotations);
	}

	/**
	 * Get optional annotation parameter by key
	 *
	 * @param string $name the name of the annotation
	 * @param string $key the string of the annotation
	 * @return string
	 */
	public function getAnnotationParameter(string $name, string $key): string {
		$name = strtolower($name);
		if (isset($this->annotations[$name][$key])) {
			return $this->annotations[$name][$key];
		}

		return '';
	}
}
