<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2023 Kate Döen <kate.doeen@nextcloud.com>
 *
 * @author Kate Döen <kate.doeen@nextcloud.com>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
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
	public function __construct(private IDeclarativeManager $manager) {
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
