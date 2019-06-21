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


namespace OCP\Entities\Helper;


use OC\Entities\Exceptions\EntityAccountAlreadyExistsException;
use OC\Entities\Exceptions\EntityAccountCreationException;
use OC\Entities\Exceptions\EntityAccountNotFoundException;
use OC\Entities\Exceptions\EntityAlreadyExistsException;
use OC\Entities\Exceptions\EntityCreationException;
use OC\Entities\Exceptions\EntityMemberAlreadyExistsException;
use OCP\Entities\Model\IEntity;
use OCP\Entities\Model\IEntityAccount;
use OCP\Entities\Model\IEntityMember;
use OCP\Entities\Model\IEntityType;

/**
 * Interface IEntitiesMigrationHelper
 *
 * @since 17.0.0
 *
 * @package OCP\Entities
 */
interface IEntitiesHelper {


	/**
	 * @param string $userId
	 * @param string $displayName
	 *
	 * @return IEntity
	 * @throws EntityAccountCreationException
	 * @throws EntityCreationException
	 * @throws EntityAlreadyExistsException
	 */
	public function createLocalUser(string $userId, string $displayName = ''): IEntity;


	/**
	 * @param string $userId
	 *
	 * @return IEntityAccount
	 * @throws EntityAccountNotFoundException
	 */
	public function getLocalAccount(string $userId): IEntityAccount;


	/**
	 * @param string $entityId
	 * @param string $userId
	 * @param int $level
	 * @param string $status
	 *
	 * @return IEntityMember
	 * @throws EntityAccountCreationException
	 * @throws EntityCreationException
	 * @throws EntityAlreadyExistsException
	 * @throws EntityAccountAlreadyExistsException
	 * @throws EntityMemberAlreadyExistsException
	 */
	public function addLocalMember(
		string $entityId, string $userId, int $level = IEntityMember::LEVEL_MEMBER,
		string $status = ''
	): IEntityMember;

	public function inviteLocalMember(string $entityId, string $userId): IEntityMember;

	public function addVirtualMember(string $entityId, string $type, string $account): IEntityMember;

	/**
	 * @param string $interface
	 *
	 * @return IEntityType[]
	 */
	public function getEntityTypes(string $interface = ''): array;

	/**
	 * @param bool $admin
	 *
	 * @return IEntityAccount
	 */
	public function temporaryLocalAccount(bool $admin = false): IEntityAccount;

	public function refreshInstall();

}

