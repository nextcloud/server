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
 * A permission with a fixed list of options, e.g. whose form results may be seen: none, own or all.
 *
 * @experimental 36.0.0
 */
#[Implementable(since: '36.0.0')]
abstract class AEnumPermission extends APermission {
	/**
	 * Options ordered from most restrictive to most permissive. Option values must be unique.
	 *
	 * @return non-empty-list<PermissionOption>
	 * @experimental 36.0.0
	 */
	abstract public function getOptions(): array;

	/**
	 * @experimental 36.0.0
	 */
	#[\Override]
	final public function getValueType(): PermissionValueType {
		return PermissionValueType::Enum;
	}

	/**
	 * @experimental 36.0.0
	 */
	#[\Override]
	final public function validateValue(string $value): true|string {
		$values = $this->getOptionValues();
		if (in_array($value, $values, true)) {
			return true;
		}

		return Server::get(IFactory::class)->get('core')->t('Only %1$s are valid values: %2$s', [implode(', ', $values), $value]);
	}

	/**
	 * @experimental 36.0.0
	 */
	#[\Override]
	final public function compare(string $a, string $b): int {
		$values = $this->getOptionValues();
		$positions = [];
		foreach ([$a, $b] as $value) {
			$position = array_search($value, $values, true);
			if ($position === false) {
				throw new \InvalidArgumentException('Invalid value for permission ' . $this->getId() . ': ' . $value);
			}
			$positions[] = $position;
		}

		return $positions[0] <=> $positions[1];
	}

	/**
	 * @experimental 36.0.0
	 */
	#[\Override]
	final public function getMostRestrictiveValue(): string {
		return $this->getOptions()[0]->value;
	}

	/**
	 * @experimental 36.0.0
	 */
	#[\Override]
	final public function getMostPermissiveValue(): string {
		$options = $this->getOptions();
		return $options[array_key_last($options)]->value;
	}

	/**
	 * @return list<non-empty-string>
	 */
	private function getOptionValues(): array {
		return array_map(static fn (PermissionOption $option): string => $option->value, $this->getOptions());
	}
}
