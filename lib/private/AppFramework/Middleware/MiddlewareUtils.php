<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH
 * SPDX-FileContributor: Carl Schwan
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\AppFramework\Middleware;

use OC\AppFramework\Utility\ControllerMethodReflector;
use OCP\AppFramework\Http\Attribute\AuthorizedAdminSetting;
use Psr\Log\LoggerInterface;
use ReflectionMethod;

/**
 * Temporary helper to abstract IControllerMethodReflector and ReflectionMethod
 */
class MiddlewareUtils {
	public function __construct(
		private readonly ControllerMethodReflector $reflector,
		private readonly LoggerInterface $logger,
	) {
	}

	/**
	 * @template T
	 *
	 * @param ReflectionMethod $reflectionMethod
	 * @param ?string $annotationName
	 * @param class-string<T> $attributeClass
	 * @return boolean
	 */
	public function hasAnnotationOrAttribute(ReflectionMethod $reflectionMethod, ?string $annotationName, string $attributeClass): bool {
		if (!empty($reflectionMethod->getAttributes($attributeClass))) {
			return true;
		}

		if ($annotationName && $this->reflector->hasAnnotation($annotationName)) {
			$this->logger->debug($reflectionMethod->getDeclaringClass()->getName() . '::' . $reflectionMethod->getName() . ' uses the @' . $annotationName . ' annotation and should use the #[' . $attributeClass . '] attribute instead');
			return true;
		}

		return false;
	}

	/**
	 * @param ReflectionMethod $reflectionMethod
	 * @return string[]
	 */
	public function getAuthorizedAdminSettingClasses(ReflectionMethod $reflectionMethod): array {
		$classes = [];
		if ($this->reflector->hasAnnotation('AuthorizedAdminSetting')) {
			$classes = explode(';', $this->reflector->getAnnotationParameter('AuthorizedAdminSetting', 'settings'));
		}

		$attributes = $reflectionMethod->getAttributes(AuthorizedAdminSetting::class);
		if (!empty($attributes)) {
			foreach ($attributes as $attribute) {
				/** @var AuthorizedAdminSetting $setting */
				$setting = $attribute->newInstance();
				$classes[] = $setting->getSettings();
			}
		}

		return $classes;
	}
}
