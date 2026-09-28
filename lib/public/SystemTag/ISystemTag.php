<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCP\SystemTag;

/**
 * Public interface for a system-wide tag.
 *
 * @since 9.0.0
 */
interface ISystemTag {
	/**
	 * @since 22.0.0
	 */
	public const ACCESS_LEVEL_PUBLIC = 0;
	/**
	 * @since 22.0.0
	 */
	public const ACCESS_LEVEL_RESTRICTED = 1;
	/**
	 * @since 22.0.0
	 */
	public const ACCESS_LEVEL_INVISIBLE = 2;

	/**
	 * @since 22.0.0
	 */
	public const ACCESS_LEVEL_LOOKUP = [
		ISystemTag::ACCESS_LEVEL_PUBLIC => 'public',
		ISystemTag::ACCESS_LEVEL_RESTRICTED => 'restricted',
		ISystemTag::ACCESS_LEVEL_INVISIBLE => 'invisible',
	];

	/**
	 * Returns the tag id
	 *
	 * @return string id
	 *
	 * @since 9.0.0
	 */
	public function getId(): string;

	/**
	 * Returns the tag display name
	 *
	 * @return string tag display name
	 *
	 * @since 9.0.0
	 */
	public function getName(): string;

	/**
	 * Returns whether the tag is visible for regular users
	 *
	 * @return bool true if visible, false otherwise
	 *
	 * @since 9.0.0
	 */
	public function isUserVisible(): bool;

	/**
	 * Returns whether the tag can be assigned to objects by regular users
	 *
	 * @return bool true if assignable, false otherwise
	 *
	 * @since 9.0.0
	 */
	public function isUserAssignable(): bool;

	/**
	 * Returns a term summarizing the access control flags
	 *
	 * @return int the level of access control
	 *
	 * @since 22.0.0
	 */
	public function getAccessLevel(): int;

	/**
	 * Returns the ETag of the tag
	 * The ETag is a unique identifier for the tag and should change whenever the tag changes
	 * or whenever elements gets added or removed from the tag.
	 *
	 * @since 31.0.0
	 */
	public function getETag(): ?string;

	/**
	 * Returns the color of the tag
	 *
	 * @since 31.0.0
	 */
	public function getColor(): ?string;
}
