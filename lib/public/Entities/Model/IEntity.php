<?php
declare(strict_types=1);


/**
 * Entities - Entity & Groups of Entities
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Maxence Lange <maxence@artificial-owl.com>
 * @copyright 2019, Maxence Lange <maxence@artificial-owl.com>
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


namespace OCP\Entities\Model;


/**
 * Interface IEntity
 *
 * @package OCP\Entities\Model
 */
interface IEntity {


	const VISIBILITY_ALL = 0;
	const VISIBILITY_MEMBERS = 1;
	const VISIBILITY_MODERATORS = 5;
	const VISIBILITY_ADMIN = 8;
	const VISIBILITY_NONE = 9;

	const CONVERT_VISIBILITY = [
		0 => 'all',
		1 => 'members',
		5 => 'moderators',
		8 => 'admin',
		9 => 'none'
	];

	const ACCESS_LIMITED = 0;
	const ACCESS_INVITE_ONLY = 3;
	const ACCESS_REQUEST_NEEDED = 6;
	const ACCESS_FREE = 9;

	const CONVERT_ACCESS = [
		0 => 'limited',
		3 => 'invite_only',
		6 => 'request_needed',
		9 => 'free'
	];


	public function getId(): string;

	public function setId(string $id): IEntity;

	public function getType(): string;

	public function getOwnerId(): string;

	public function getVisibility(): int;

	public function getAccess(): int;

	public function getVisibilityString(): string;

	public function getAccessString(): string;

	public function getName(): string;

	public function setCreation(int $creation): IEntity;

	public function getCreation(): int;

//	public function getMembers(): array;
//
//	public function setMembers(array $members): IEntity;
//
//	public function addMembers(array $members): IEntity;
//
//	public function addMember(IEntityMember $members): IEntity;

	public function hasOwner(): bool;

	public function setOwner(IEntityAccount $owner): IEntity;

	public function getOwner(): IEntityAccount;

	public function hasViewer(): bool;

	public function setViewer(IEntityMember $viewer): IEntity;

	public function getViewer(): IEntityMember;


	/**
	 * @return IEntity[]
	 */
	public function belongsTo(): array;

	/**
	 * @return IEntityMember[]
	 */
	public function getMembers(): array;

	/**
	 * @return bool
	 */
	public function hasAdminRights(): bool;

}

