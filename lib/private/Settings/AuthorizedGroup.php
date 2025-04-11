<?php

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Settings;

use OCP\AppFramework\Db\Entity;

/**
 * @method setGroupId(string $groupId)
 * @method setClass(string $class)
 * @method getGroupId(): string
 * @method getClass(): string
 */
class AuthorizedGroup extends Entity implements \JsonSerializable {
	/** @var string $group_id */
	protected $groupId;

	/** @var string $class */
	protected $class;

	public function jsonSerialize(): array {
		return [
			'id' => $this->id,
			'group_id' => $this->groupId,
			'class' => $this->class
		];
	}
}
