<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCP\AppFramework\Utility;

/**
 * Interface ControllerMethodReflector
 *
 * Reads and parses annotations from doc comments
 *
 * @since 8.0.0
 * @deprecated 22.0.0 will be obsolete with native attributes in PHP8
 * @see https://help.nextcloud.com/t/how-should-we-use-php8-attributes/104278
 */
interface IControllerMethodReflector {
	/**
	 * @param object $object an object or classname
	 * @param string $method the method which we want to inspect
	 * @return void
	 * @since 8.0.0
	 * @deprecated 17.0.0 Reflect should not be called multiple times and only be used internally. This will be removed in Nextcloud 18
	 */
	public function reflect($object, string $method);

	/**
	 * Inspects the PHPDoc parameters for types
	 *
	 * @param string $parameter the parameter whose type comments should be
	 *                          parsed
	 * @return string|null type in the type parameters (@param int $something)
	 *                     would return int or null if not existing
	 * @since 8.0.0
	 * @deprecated 22.0.0 this method is only used internally
	 */
	public function getType(string $parameter);

	/**
	 * @return array the arguments of the method with key => default value
	 * @since 8.0.0
	 * @deprecated 22.0.0 this method is only used internally
	 */
	public function getParameters(): array;

	/**
	 * Check if a method contains an annotation
	 *
	 * @param string $name the name of the annotation
	 * @return bool true if the annotation is found
	 * @since 8.0.0
	 * @deprecated 22.0.0 will be obsolete with native attributes in PHP8
	 * @see https://help.nextcloud.com/t/how-should-we-use-php8-attributes/104278
	 */
	public function hasAnnotation(string $name): bool;
}
