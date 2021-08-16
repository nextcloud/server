<?php

declare(strict_types=1);


/**
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Maxence Lange <maxence@artificial-owl.com>
 * @copyright 2021
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */


namespace OCP\Circles\Model;


interface ICircle extends IEntity {


	public const FLAGS_SHORT = 1;
	public const FLAGS_LONG = 2;


	// specific value
	public const CFG_CIRCLE = 0;        // only for code readability. Circle is locked by default.
	public const CFG_SINGLE = 1;        // Circle with only one single member.
	public const CFG_PERSONAL = 2;      // Personal circle, only the owner can see it.

	// bitwise
	public const CFG_SYSTEM = 4;            // System Circle (not managed by the official front-end). Meaning some config are limited
	public const CFG_VISIBLE = 8;           // Visible to everyone, if not visible, people have to know its name to be able to find it
	public const CFG_OPEN = 16;             // Circle is open, people can join
	public const CFG_INVITE = 32;           // Adding a member generate an invitation that needs to be accepted
	public const CFG_REQUEST = 64;          // Request to join Circles needs to be confirmed by a moderator
	public const CFG_FRIEND = 128;          // Members of the circle can invite their friends
	public const CFG_PROTECTED = 256;       // Password protected to join/request
	public const CFG_NO_OWNER = 512;        // no owner, only members
	public const CFG_HIDDEN = 1024;         // hidden from listing, but available as a share entity
	public const CFG_BACKEND = 2048;            // Fully hidden, only backend Circles
	public const CFG_LOCAL = 4096;              // Local even on GlobalScale
	public const CFG_ROOT = 8192;               // Circle cannot be inside another Circle
	public const CFG_CIRCLE_INVITE = 16384;     // Circle must confirm when invited in another circle
	public const CFG_FEDERATED = 32768;         // Federated
	public const CFG_MOUNTPOINT = 65536;        // Generate a Files folder for this Circle

	public const CFG_MAX = 131071;

//	/**
//	 * @param string $singleId
//	 *
//	 * @return self
//	 */
//	public function setSingleId(string $singleId): self;
//
//	/**
//	 * @return string
//	 */
//	public function getSingleId(): string;
//
//	/**
//	 * @return string
//	 * @deprecated - removed in NC24
//	 */
//	public function getUniqueId(): string;
//
//
//	/**
//	 * @param int $config
//	 *
//	 * @return self
//	 */
//	public function setConfig(int $config): self;
//
//	/**
//	 * @return int
//	 */
//	public function getConfig(): int;
//
//
//	/**
//	 * @param int $flag
//	 * @param int $test
//	 *
//	 * @return bool
//	 */
//	public function isConfig(int $flag, int $test = 0): bool;
//
//	/**
//	 * @param int $flag
//	 */
//	public function addConfig(int $flag): void;
//
//
//	/**
//	 * @param int $flag
//	 */
//	public function remConfig(int $flag): void;
//
//
//	/**
//	 * @param string $name
//	 *
//	 * @return self
//	 */
//	public function setName(string $name): self;
//
//	/**
//	 * @return string
//	 */
//	public function getName(): string;
//
//	/**
//	 * @param string $displayName
//	 *
//	 * @return self
//	 */
//	public function setDisplayName(string $displayName): self;
//
//	/**
//	 * @return string
//	 */
//	public function getDisplayName(): string;
//
//	/**
//	 * @param string $sanitizedName
//	 *
//	 * @return Circle
//	 */
//	public function setSanitizedName(string $sanitizedName): self;
//
//	/**
//	 * @return string
//	 */
//	public function getSanitizedName(): string;
//
//
//	/**
//	 * @param int $source
//	 *
//	 * @return Circle
//	 */
//	public function setSource(int $source): self;
//
//	/**
//	 * @return int
//	 */
//	public function getSource(): int;
//
//
//	/**
//	 * @param ?Member $owner
//	 *
//	 * @return self
//	 */
//	public function setOwner(?IMember $owner): self;
//
//	/**
//	 * @return Member
//	 */
//	public function getOwner(): IMember;
//	/**
//	 * @return bool
//	 */
//	public function hasOwner(): bool;
//
//
//	/**
//	 * @return bool
//	 */
//	public function hasMembers(): bool;
//
//	/**
//	 * @param array $members
//	 *
//	 * @return self
//	 */
//	public function setMembers(array $members): self;
//
//	/**
//	 * @return array
//	 */
//	public function getMembers(): array;
//
//
//	/**
//	 * @param array $members
//	 * @param bool $detailed
//	 *
//	 * @return self
//	 */
//	public function setInheritedMembers(array $members, bool $detailed): IMemberships;
//
//	/**
//	 * @param array $members
//	 *
//	 * @return IMemberships
//	 */
//	public function addInheritedMembers(array $members): IMemberships;
//
//
//	/**
//	 * if $remote is true, it will returns also details on inherited members from remote+locals Circles.
//	 * This should be used only if extra details are required (mail address ?) as it will send a request to
//	 * the remote instance if the circleId is not locally known.
//	 * because of the resource needed to retrieve this data, $remote=true should not be used on main process !
//	 *
//	 * @param bool $detailed
//	 * @param bool $remote
//	 * @return IMember[]
//	 */
//	public function getInheritedMembers(bool $detailed = false, bool $remote = false): array;
//
//
//	/**
//	 * @return bool
//	 */
//	public function hasMemberships(): bool;
//
//	/**
//	 * @param array $memberships
//	 *
//	 * @return self
//	 */
//	public function setMemberships(array $memberships): IMemberships;
//
//	/**
//	 * @return IMembership[]
//	 */
//	public function getMemberships(): array;
//
//
//	/**
//	 * @param Member|null $initiator
//	 *
//	 * @return Circle
//	 */
//	public function setInitiator(?IMember $initiator): self;
//
//	/**
//	 * @return Member
//	 */
//	public function getInitiator(): Member;
//
//	/**
//	 * @return bool
//	 */
//	public function hasInitiator(): bool;
//
//	/**
//	 * @param Member|null $directInitiator
//	 *
//	 * @return $this
//	 */
//	public function setDirectInitiator(?Member $directInitiator): self;
//
//
//	/**
//	 * @param string $instance
//	 *
//	 * @return Circle
//	 */
//	public function setInstance(string $instance): self;
//
//	/**
//	 * @return string
//	 */
//	public function getInstance(): string;
//
//
//	/**
//	 * @param int $population
//	 *
//	 * @return Circle
//	 */
//	public function setPopulation(int $population): self;
//
//	/**
//	 * @return int
//	 */
//	public function getPopulation(): int;
//
//
//	/**
//	 * @param array $settings
//	 *
//	 * @return self
//	 */
//	public function setSettings(array $settings): self;
//
//	/**
//	 * @return array
//	 */
//	public function getSettings(): array;
//
//
//	/**
//	 * @param string $description
//	 *
//	 * @return self
//	 */
//	public function setDescription(string $description): self;
//
//	/**
//	 * @return string
//	 */
//	public function getDescription(): string;
//
//
//	/**
//	 * @return string
//	 */
//	public function getUrl(): string;
//
//
//	/**
//	 * @param int $contactAddressBook
//	 *
//	 * @return self
//	 */
//	public function setContactAddressBook(int $contactAddressBook): self;
//
//	/**
//	 * @return int
//	 */
//	public function getContactAddressBook(): int;
//
//	/**
//	 * @param string $contactGroupName
//	 *
//	 * @return self
//	 */
//	public function setContactGroupName(string $contactGroupName): self;
//
//	/**
//	 * @return string
//	 */
//	public function getContactGroupName(): string;
//
//
//	/**
//	 * @param int $creation
//	 *
//	 * @return self
//	 */
//	public function setCreation(int $creation): self;
//
//	/**
//	 * @return int
//	 */
//	public function getCreation(): int;
//
//
//	/**
//	 * @param Circle $circle
//	 *
//	 * @return bool
//	 */
//	public function compareWith(Circle $circle): bool;
//
//
//	/**
//	 * @param Circle $circle
//	 * @param int $display
//	 *
//	 * @return array
//	 */
//	public static function getCircleFlags(Circle $circle, int $display = self::FLAGS_LONG): array;
}
