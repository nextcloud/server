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
 * Interface IEntityMember
 *
 * @package OCP\Entities\Model
 */
interface IEntityMember {


	const LEVEL_MEMBER = 1;
	const LEVEL_MODERATOR = 5;
	const LEVEL_ADMIN = 8;
	const LEVEL_OWNER = 9;

	const CONVERT_LEVEL = [
		0 => 'none',
		1 => 'member',
		5 => 'moderator',
		8 => 'admin',
		9 => 'owner'
	];

	const STATUS_INVITED = 'invited';
	const STATUS_REQUESTING = 'requesting';
	const STATUS_MEMBER = 'member';


	public function getId(): string;

//	public function setId(string $id): IEntityMember;

	public function getEntityId(): string;

//	public function setEntityId(string $entityId): IEntityMember;

	public function getEntity(): IEntity;

	public function hasAccount(): bool;

	public function getAccount(): IEntityAccount;

//	public function setAccount(IEntityAccount $account);

	public function getAccountId(): string;

//	public function setAccountId(string $accountId): IEntityMember;

	public function getSlaveEntityId(): string;

//	public function setSlaveEntityId(string $slaveEntityId): IEntityMember;

	public function getStatus(): string;

//	public function setStatus(string $status): IEntityMember;

	public function getLevel(): int;

	public function getLevelString(): string;

//	public function setLevel(int $level): IEntityMember;

	public function getCreation(): int;

	public function setCreation(int $creation): IEntityMember;

}

