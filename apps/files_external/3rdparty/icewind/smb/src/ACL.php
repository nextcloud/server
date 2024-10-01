<?php declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2020 Robin Appelman <robin@icewind.nl>
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Icewind\SMB;

class ACL {
	const TYPE_ALLOW = 0;
	const TYPE_DENY = 1;

	const MASK_READ = 0x0001;
	const MASK_WRITE = 0x0002;
	const MASK_EXECUTE = 0x00020;
	const MASK_DELETE = 0x10000;

	const FLAG_OBJECT_INHERIT = 0x1;
	const FLAG_CONTAINER_INHERIT = 0x2;

	/** @var int */
	private $type;
	/** @var int */
	private $flags;
	/** @var int */
	private $mask;

	public function __construct(int $type, int $flags, int $mask) {
		$this->type = $type;
		$this->flags = $flags;
		$this->mask = $mask;
	}

	/**
	 * Check if the acl allows a specific permissions
	 *
	 * Note that this does not take inherited acls into account
	 *
	 * @param int $mask one of the ACL::MASK_* constants
	 * @return bool
	 */
	public function allows(int $mask): bool {
		return $this->type === self::TYPE_ALLOW && ($this->mask & $mask) === $mask;
	}

	/**
	 * Check if the acl allows a specific permissions
	 *
	 * Note that this does not take inherited acls into account
	 *
	 * @param int $mask one of the ACL::MASK_* constants
	 * @return bool
	 */
	public function denies(int $mask): bool {
		return $this->type === self::TYPE_DENY && ($this->mask & $mask) === $mask;
	}

	public function getType(): int {
		return $this->type;
	}

	public function getFlags(): int {
		return $this->flags;
	}

	public function getMask(): int {
		return $this->mask;
	}
}
