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
 * You can inject this interface in your Middleware, and it will be prefilled with information related to the called controller method
 *
 * Reads and parses annotations from doc comments (deprecated) and PHP attributes
 *
 * @since 8.0.0
 */
interface IControllerMethodReflector {
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

	/**
	 * @template T
	 *
	 * Check if a method contains an annotation or an attribute.
	 * Log a debug line if the annotation is used.
	 *
	 * @param class-string<T> $attributeClass
	 * @since 34.0.0
	 */
	public function hasAnnotationOrAttribute(?string $annotationName, string $attributeClass): bool;
}
