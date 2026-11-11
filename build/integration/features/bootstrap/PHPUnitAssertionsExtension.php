<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

use Behat\Behat\EventDispatcher\ServiceContainer\EventDispatcherExtension;
use Behat\PHPUnitAssertionsExtension\PHPUnitBootstrappingListener;
use Behat\PHPUnitAssertionsExtension\PHPUnitExceptionStringer;
use Behat\Testwork\Exception\ServiceContainer\ExceptionExtension;
use Behat\Testwork\ServiceContainer\Extension;
use Behat\Testwork\ServiceContainer\ExtensionManager;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

/**
 * Registers the services of behat/phpunit-assertions-extension so that PHPUnit
 * assertions work in step definitions and failures are rendered with details.
 *
 * The extension class shipped with that package (v1.0.0) imports the stringer
 * from a namespace that no longer exists in Behat 4, so Behat fails to start
 * when using it directly, see https://github.com/Behat/PHPUnitAssertionsExtension/issues/11
 */
final class PHPUnitAssertionsExtension implements Extension {
	public function getConfigKey(): string {
		return 'phpunit_assertions';
	}

	public function initialize(ExtensionManager $extensionManager): void {
	}

	public function configure(ArrayNodeDefinition $builder): void {
	}

	public function load(ContainerBuilder $container, array $config): void {
		$stringer = new Definition(PHPUnitExceptionStringer::class);
		$stringer->addTag(ExceptionExtension::STRINGER_TAG, ['priority' => 50]);
		$container->setDefinition(ExceptionExtension::STRINGER_TAG . '.phpunit_assertions', $stringer);

		$listener = new Definition(PHPUnitBootstrappingListener::class);
		$listener->addTag(EventDispatcherExtension::SUBSCRIBER_TAG, ['priority' => -10]);
		$container->setDefinition('phpunit_assertions.bootstrapping_listener', $listener);
	}

	public function process(ContainerBuilder $container): void {
	}
}
