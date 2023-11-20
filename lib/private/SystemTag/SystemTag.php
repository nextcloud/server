<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Joas Schilling <coding@schilljs.com>
 * @author Johannes Leuker <j.leuker@hosting.de>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Vincent Petry <vincent@nextcloud.com>
 *
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 */
namespace OC\SystemTag;

use OCP\SystemTag\ISystemTag;

class SystemTag implements ISystemTag {
	public function __construct(
		private string $id,
		private string $name,
		private bool $userVisible,
		private bool $userAssignable,
	) {
	}

	/**
	 * {@inheritdoc}
	 */
	public function getId(): string {
		return $this->id;
	}

	/**
	 * {@inheritdoc}
	 */
	public function getName(): string {
		return $this->name;
	}

	/**
	 * {@inheritdoc}
	 */
	public function isUserVisible(): bool {
		return $this->userVisible;
	}

	/**
	 * {@inheritdoc}
	 */
	public function isUserAssignable(): bool {
		return $this->userAssignable;
	}

	/**
	 * {@inheritdoc}
	 */
	public function getAccessLevel(): int {
		if (!$this->userVisible) {
			return self::ACCESS_LEVEL_INVISIBLE;
		}

		if (!$this->userAssignable) {
			return self::ACCESS_LEVEL_RESTRICTED;
		}

		return self::ACCESS_LEVEL_PUBLIC;
	}
}
