<?php

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace NCU\Permission;

use OCP\AppFramework\Attribute\Implementable;

/**
 * Defaults shared by all permissions. Extend {@see ABooleanPermission} or {@see AEnumPermission} instead.
 *
 * @experimental 36.0.0
 */
#[Implementable(since: '36.0.0')]
abstract class APermission implements IPermission {
	/**
	 * @experimental 36.0.0
	 */
	#[\Override]
	public function getHint(): ?string {
		return null;
	}

	/**
	 * @experimental 36.0.0
	 */
	#[\Override]
	public function getRequiredPermissionIds(): array {
		return [];
	}

	/**
	 * @experimental 36.0.0
	 */
	#[\Override]
	public function getParentPermissionId(): ?string {
		return null;
	}

	/**
	 * A parent value that is also valid for this permission is inherited as-is.
	 * Otherwise the most restrictive parent value restricts this permission completely,
	 * and any other parent value does not restrict this permission.
	 *
	 * @experimental 36.0.0
	 */
	#[\Override]
	public function inheritValue(IPermission $parent, string $parentValue): string {
		if ($parent->validateValue($parentValue) !== true) {
			throw new \InvalidArgumentException('Invalid value for permission ' . $parent->getId() . ': ' . $parentValue);
		}

		if ($this->validateValue($parentValue) === true) {
			return $parentValue;
		}

		if ($parentValue === $parent->getMostRestrictiveValue()) {
			return $this->getMostRestrictiveValue();
		}

		return $this->getMostPermissiveValue();
	}
}
