<?php

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace NCU\Permission;

use OCP\AppFramework\Attribute\Consumable;

/**
 * A permission describes one meaning, e.g. "may share the resource with others".
 *
 * The same permission ID always has the same meaning, regardless of the resource type it is bound to.
 * Resource types may present it with their own wording, see {@see PermissionBinding}.
 * The app owning a resource type enforces the permission, everybody else only needs the definition.
 *
 * Values are ordered from most restrictive to most permissive, so that values from multiple sources
 * can be combined without knowing what the permission does.
 *
 * Implement a permission by extending {@see ABooleanPermission} or {@see AEnumPermission}.
 *
 * @experimental 36.0.0
 */
#[Consumable(since: '36.0.0')]
interface IPermission {
	/**
	 * Stable ID in the format `appid:name`, e.g. `forms:results`.
	 *
	 * The ID is persisted by consumers and must never change.
	 *
	 * @return non-empty-string
	 * @experimental 36.0.0
	 */
	public function getId(): string;

	/**
	 * Generic, user friendly name, also used as the heading when grouping the permission across resource types.
	 *
	 * @return non-empty-string
	 * @experimental 36.0.0
	 */
	public function getDisplayName(): string;

	/**
	 * Optional, user friendly explanation.
	 *
	 * @return ?non-empty-string
	 * @experimental 36.0.0
	 */
	public function getHint(): ?string;

	/**
	 * A higher value means the permission is shown further up.
	 *
	 * @return int<1, 100>
	 * @experimental 36.0.0
	 */
	public function getPriority(): int;

	/**
	 * @experimental 36.0.0
	 */
	public function getValueType(): PermissionValueType;

	/**
	 * Value used when nothing was configured, may be overridden per resource type.
	 *
	 * @experimental 36.0.0
	 */
	public function getDefaultValue(): string;

	/**
	 * Returns true if the value is valid or a user friendly error message otherwise.
	 *
	 * @experimental 36.0.0
	 */
	public function validateValue(string $value): true|string;

	/**
	 * Compares two valid values by how permissive they are.
	 *
	 * @return int less than 0 if $a is more restrictive than $b, 0 if they are equal and greater than 0 if $a is more permissive than $b
	 * @throws \InvalidArgumentException if one of the values is not valid
	 * @experimental 36.0.0
	 */
	public function compare(string $a, string $b): int;

	/**
	 * @experimental 36.0.0
	 */
	public function getMostRestrictiveValue(): string;

	/**
	 * IDs of permissions this permission depends on, e.g. updating requires reading.
	 *
	 * If a required permission has its most restrictive value, this permission has its most restrictive value as well.
	 *
	 * @return list<non-empty-string>
	 * @experimental 36.0.0
	 */
	public function getRequiredPermissionIds(): array;
}
