<?php

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace NCU\Permission;

use OCP\AppFramework\Attribute\Implementable;
use OCP\L10N\IFactory;
use OCP\Server;

/**
 * A permission that is either denied or allowed.
 *
 * Name the permission affirmatively ("may download"), never as a denial, so that `'true'` is the permissive value.
 *
 * @experimental 36.0.0
 */
#[Implementable(since: '36.0.0')]
abstract class ABooleanPermission implements IPermission {
	/**
	 * @experimental 36.0.0
	 */
	final public const string VALUE_TRUE = 'true';

	/**
	 * @experimental 36.0.0
	 */
	final public const string VALUE_FALSE = 'false';

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
	final public function getValueType(): PermissionValueType {
		return PermissionValueType::Boolean;
	}

	/**
	 * @experimental 36.0.0
	 */
	#[\Override]
	final public function validateValue(string $value): true|string {
		if ($value === self::VALUE_TRUE || $value === self::VALUE_FALSE) {
			return true;
		}

		return Server::get(IFactory::class)->get('core')->t('Only true and false are valid values: %s', [$value]);
	}

	/**
	 * @experimental 36.0.0
	 */
	#[\Override]
	final public function compare(string $a, string $b): int {
		foreach ([$a, $b] as $value) {
			if ($value !== self::VALUE_TRUE && $value !== self::VALUE_FALSE) {
				throw new \InvalidArgumentException('Invalid value for permission ' . $this->getId() . ': ' . $value);
			}
		}

		return ($a === self::VALUE_TRUE) <=> ($b === self::VALUE_TRUE);
	}

	/**
	 * @experimental 36.0.0
	 */
	#[\Override]
	final public function getMostRestrictiveValue(): string {
		return self::VALUE_FALSE;
	}
}
