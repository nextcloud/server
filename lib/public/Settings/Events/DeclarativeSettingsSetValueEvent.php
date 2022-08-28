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
use OCP\IUser;
use OCP\Settings\IDeclarativeSettingsForm;

/**
 * @psalm-import-type DeclarativeSettingsValueTypes from IDeclarativeSettingsForm
 *
 * @since 29.0.0
 */
class DeclarativeSettingsSetValueEvent extends Event {
	/**
	 * @param DeclarativeSettingsValueTypes $value
	 * @since 29.0.0
	 */
	public function __construct(
		private IUser $user,
		private string $app,
		private string $formId,
		private string $fieldId,
		private mixed $value,
	) {
		parent::__construct();
	}

	/**
	 * @since 29.0.0
	 */
	public function getUser(): IUser {
		return $this->user;
	}

	/**
	 * @since 29.0.0
	 */
	public function getApp(): string {
		return $this->app;
	}

	/**
	 * @since 29.0.0
	 */
	public function getFormId(): string {
		return $this->formId;
	}

	/**
	 * @since 29.0.0
	 */
	public function getFieldId(): string {
		return $this->fieldId;
	}

	/**
	 * @since 29.0.0
	 */
	public function getValue(): mixed {
		return $this->value;
	}
}
