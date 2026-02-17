<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Settings;

use JsonSerializable;
use OCP\AppFramework\Db\Entity;

/**
 * @method setGroupId(string $groupId)
 * @method setClass(string $class)
 * @method string getGroupId()
 * @method string getClass()
 */
class AuthorizedGroup extends Entity implements JsonSerializable {
	public $id;

	protected ?string $groupId = null;

	protected ?string $class = null;

	/**
	 * @return array<string, mixed>
	 */
	public function jsonSerialize(): array {
		return [
			'id' => $this->getId(),
			'group_id' => $this->groupId,
			'class' => $this->class
		];
	}
}
