<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\Settings\Events;

use OCP\EventDispatcher\Event;
use OCP\Settings\IDeclarativeManager;
use OCP\Settings\IDeclarativeSettingsForm;

/**
 * @psalm-import-type DeclarativeSettingsFormSchemaWithoutValues from IDeclarativeSettingsForm
 *
 * @since 29.0.0
 */
class DeclarativeSettingsRegisterFormEvent extends Event {
	/**
	 * @since 29.0.0
	 */
	public function __construct(
		private IDeclarativeManager $manager,
	) {
		parent::__construct();
	}

	/**
	 * @param DeclarativeSettingsFormSchemaWithoutValues $schema
	 * @since 29.0.0
	 */
	public function registerSchema(string $app, array $schema): void {
		$this->manager->registerSchema($app, $schema);
	}
}
