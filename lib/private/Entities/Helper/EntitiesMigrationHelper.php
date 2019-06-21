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


namespace OC\Entities\Helper;


use daita\NcSmallPhpTools\Model\EmptyMockup;
use Exception;
use OC;
use OC\Entities\Classes\IEntities\AdminGroup;
use OC\Entities\Classes\IEntities\GlobalGroup;
use OC\Entities\Db\EntitiesRequest;
use OC\Entities\Exceptions\EntityNotFoundException;
use OC\Entities\Exceptions\EntityTypeNotFoundException;
use OC\Entities\Model\Entity;
use OCP\Entities\Helper\IEntitiesHelper;
use OCP\Entities\Helper\IEntitiesMigrationHelper;
use OCP\Entities\IEntitiesManager;
use OCP\Entities\Model\IEntity;
use OCP\IGroup;
use OCP\IGroupManager;
use OCP\IUserManager;
use Symfony\Component\Console\Output\ConsoleOutput;


/**
 * Class EntitiesManager
 *
 * @package OCP\Entities\Helper
 */
class EntitiesMigrationHelper implements IEntitiesMigrationHelper {


	/** @var bool */
	private $isCLI;

	/** @var IEntitiesManager */
	private $entitiesManager;

	/** @var IEntitiesHelper */
	private $entitiesHelper;

	/** @var EntitiesRequest */
	private $entitiesRequest;

	/** @var IUserManager */
	private $userManager;

	/** @var IGroupManager */
	private $groupManager;


	/** @var ConsoleOutput */
	private $output = null;


	/**
	 * EntitiesMigrationHelper constructor.
	 *
	 * @param bool $isCLI
	 * @param IEntitiesManager $entitiesManager
	 * @param IEntitiesHelper $entitiesHelper
	 * @param EntitiesRequest $entitiesRequest
	 * @param IUserManager $userManager
	 * @param IGroupManager $groupManager
	 */
	public function __construct(
		$isCLI, IEntitiesManager $entitiesManager, IEntitiesHelper $entitiesHelper,
		EntitiesRequest $entitiesRequest, IUserManager $userManager, IGroupManager $groupManager
	) {

		$this->isCLI = $isCLI;
		if ($isCLI === true) {
			$this->output = new ConsoleOutput();
		} else {
			$this->output = new EmptyMockup();
		}

		$this->entitiesManager = $entitiesManager;
		$this->entitiesHelper = $entitiesHelper;
		$this->entitiesRequest = $entitiesRequest;

		$this->userManager = $userManager;
		$this->groupManager = $groupManager;
	}


	/**
	 *
	 */
	public function migrateUsers(): void {
		$this->output->writeln('### Migrating users');

		$users = $this->userManager->search('');
		foreach ($users as $user) {
			$this->output->write('- ' . $user->getUID() . ': ', false);

			try {
				$entity = $this->entitiesHelper->createLocalUser(
					$user->getUID(), $user->getDisplayName()
				);
				$this->output->write(
					'account <info>' . $entity->getOwner()
											  ->getId() . '</info>, entity '
					. '<info>' . $entity->getId() . '</info>', true
				);
			} catch (Exception $e) {
				$this->output->write('<comment>' . $e->getMessage() . '</comment>', true);
				continue;
			}

		}

		$this->output->writeln('');
	}


	/**
	 *
	 * @throws Exception
	 */
	public function migrateGroups(): void {
		$this->output->writeln('### Migrating groups');

		$groups = $this->groupManager->search('');
		foreach ($groups as $group) {
			$this->output->write('- ' . $group->getGID() . ': ', false);

			$entity = $this->createGroupEntity($group->getGID(), ($group->getGID() === 'admin'));

			try {
				$knownEntity = $this->entitiesManager->searchDuplicateEntity($entity);

				$entity->setId($knownEntity->getId());
				$this->output->write(
					'<comment>Entity already exists (' . $knownEntity->getId() . ')</comment>', true
				);

			} catch (EntityTypeNotFoundException $e) {
				$this->output->write('<comment>Unknown Entity Type</comment>', true);
				continue;
			} catch (EntityNotFoundException $e) {
				$this->entitiesRequest->create($entity);
				$this->output->write('<info>' . $entity->getId() . '</info>', true);
			}


//
//
//
//			} catch (EntityAlreadyExistsException $e) {
////				$this->output->write('<comment>' . $e->getMessage() . '</comment>', true);
//			} catch (EntityCreationException $e) {
////				$this->output->write('<comment>' . $e->getMessage() . '</comment>', true);
//				continue;
//			}

			$this->manageGroupMembers($entity, $group);
		}

	}


	public function migrateCircles(): void {
	}


	public function migrateRooms(): void {
	}


	/**
	 * @param string $groupId
	 * @param bool $isAdmin
	 *
	 * @return IEntity
	 */
	private function createGroupEntity(string $groupId, bool $isAdmin = false): IEntity {
		$entity = new Entity();
		$entity->setVisibility(IEntity::VISIBILITY_MEMBERS);
		$entity->setAccess(IEntity::ACCESS_LIMITED);
		$entity->setName($groupId);
		if ($isAdmin) {
			$entity->setType(AdminGroup::TYPE);
		} else {
			$entity->setType(GlobalGroup::TYPE);
		}

		return $entity;
	}

//
//	/**
//	 * @param string $groupId
//	 *
//	 * @return IEntity
//	 * @throws EntityNotFoundExceptionAlias
//	 * @throws EntityTypeNotFoundException
//	 */
//	public function getGroupByName(string $groupId): IEntity {
//		$entity = new Entity();
//		$entity->setVisibility(IEntity::VISIBILITY_MEMBERS);
//		$entity->setAccess(IEntity::ACCESS_LIMITED);
//		$entity->setName($groupId);
//		$entity->setType(Group::TYPE);
//
//		return $this->entitiesManager->searchDuplicateEntity($entity);
//	}


	/**
	 * @param IEntity $entity
	 * @param IGroup $group
	 */
	private function manageGroupMembers(IEntity $entity, IGroup $group): void {
		//$this->output->writeln('  - Adding members to <info>' . $entity->getId() . '</info>');

		foreach ($group->getUsers() as $user) {
			$this->output->write('   * Adding ' . $user->getUID() . ': ', false);

			try {
				$member =
					$this->entitiesHelper->addLocalMember($entity->getId(), $user->getUID());

				$this->output->write(
					'account <info>' . $member->getAccountId() . '</info>, member '
					. '<info>' . $member->getId() . '</info>', true
				);
			} catch (Exception $e) {
				$this->output->write('<comment>' . $e->getMessage() . '</comment>', true);

				continue;
			}
		}
	}

}

