<?php
declare(strict_types=1);


/**
 * Nextcloud - Social Support
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Maxence Lange <maxence@artificial-owl.com>
 * @copyright 2018, Maxence Lange <maxence@artificial-owl.com>
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


namespace OC\Entities\Command;


use Exception;
use OC\Entities\Exceptions\EntityAccountNotFoundException;
use OC\Entities\Exceptions\EntityMemberAlreadyExistsException;
use OC\Entities\Exceptions\EntityNotFoundException;
use OC\Entities\Model\Entity;
use OC\Entities\Model\EntityAccount;
use OC\Entities\Model\EntityMember;
use OCP\Entities\Helper\IEntitiesHelper;
use OCP\Entities\IEntitiesManager;
use OCP\Entities\Implementation\IEntities\IEntities;
use OCP\Entities\Implementation\IEntitiesAccounts\IEntitiesAccounts;
use OCP\Entities\Model\IEntity;
use OCP\Entities\Model\IEntityMember;
use OCP\Entities\Model\IEntityType;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;


/**
 * Class Create
 *
 * @package OC\Entities\Command
 */
class Create extends ExtendedBase {


	/** @var IEntitiesManager */
	private $entitiesManager;

	/** @var IEntitiesHelper */
	private $entitiesHelper;


	/**
	 * Create constructor.
	 *
	 * @param IEntitiesManager $entitiesManager
	 * @param IEntitiesHelper $entitiesHelper
	 */
	public function __construct(IEntitiesManager $entitiesManager, IEntitiesHelper $entitiesHelper
	) {
		parent::__construct();

		$this->entitiesManager = $entitiesManager;
		$this->entitiesHelper = $entitiesHelper;
	}


	/**
	 *
	 */
	protected function configure() {
		parent::configure();
		$this->setName('entities:manage:create')
			 ->addArgument(
				 'item', InputArgument::REQUIRED, 'item to create (entity, account, member)'
			 )
			 ->addArgument('type', InputArgument::OPTIONAL, 'type/status of the item', '')
			 ->addOption('name', '', InputOption::VALUE_REQUIRED, 'name', '')
			 ->addOption('access', 'x', InputOption::VALUE_REQUIRED, 'access level', '')
			 ->addOption('visibility', 'b', InputOption::VALUE_REQUIRED, 'visibility level', '')
			 ->addOption('entity', 'm', InputOption::VALUE_REQUIRED, 'entity Id (master)', '')
			 ->addOption('slave', 's', InputOption::VALUE_REQUIRED, 'entity Id (slave)', '')
			 ->addOption('account', 'c', InputOption::VALUE_REQUIRED, 'account Id', '')
			 ->addOption('level', 'l', InputOption::VALUE_REQUIRED, 'level', '')
			 ->addOption('owner', 'o', InputOption::VALUE_REQUIRED, 'account id of owner', '')
			 ->setDescription('Create a new entity/account/member');
	}


	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 *
	 * @throws Exception
	 */
	protected function execute(InputInterface $input, OutputInterface $output) {
		$this->output = $output;
		$this->input = $input;

		$item = $input->getArgument('item');
		$type = $input->getArgument('type');

		$this->verifyType($item, $type);

		switch ($item) {
			case 'entity':
				$this->createEntity(
					$type,
					$input->getOption('name'),
					$input->getOption('access'),
					$input->getOption('visibility'),
					$input->getOption('owner')
				);
				break;

			case 'account':
				$this->createAccount(
					$type,
					$input->getOption('account')
				);
				break;

			case 'member':
				$this->createMember(
					$type,
					$input->getOption('entity'),
					$input->getOption('account'),
					$input->getOption('slave'),
					$input->getOption('level')
				);
				break;

			default:
				throw new Exception('Unknown item');
		}


//		switch ($action) {
//
//			case 'create':
//				$this->actionCreate($item, $type, $data);
//				break;
//
//			default:
//				throw new Exception('unknown action');
//
//		}
	}


	/**
	 * @param string $type
	 * @param string $name
	 * @param string $access
	 * @param string $visibility
	 *
	 * @param string $ownerId
	 *
	 * @throws Exception
	 */
	private function createEntity(
		string $type, string $name, string $access, string $visibility, string $ownerId
	): void {
		if ($name === '') {
			throw new Exception('must specify a name (--name)');
		}

		$listAccess = array_values(IEntity::CONVERT_ACCESS);
		if (!in_array($access, $listAccess)) {
			throw new Exception(
				'must specify an Access Level (--access): ' . implode(', ', $listAccess)
			);
		}

		$listVisibility = array_values(IEntity::CONVERT_VISIBILITY);
		if (!in_array($visibility, $listVisibility)) {
			throw new Exception(
				'must specify an Visibility Level (--visibility): ' . implode(', ', $listVisibility)
			);
		}

		$owner = null;
		if ($ownerId !== '') {
			$owner = $this->entitiesManager->getAccount($ownerId);
		}

		$entity = new Entity();
		$entity->setType($type);
		$entity->setName($name);
		$entity->setAccess(array_search($access, IEntity::CONVERT_ACCESS));
		$entity->setVisibility(array_search($visibility, IEntity::CONVERT_VISIBILITY));
		$this->outputEntity($entity);

		if ($owner !== null) {
			$this->outputAccount($owner);
		}

		$this->output->writeln('');
		$helper = $this->getHelper('question');
		$question = new ConfirmationQuestion(
			'<info>Create this Entity?</info> (Y/n) ', true, '/^(y|Y)/i'
		);
		if (!$helper->ask($this->input, $this->output, $question)) {
			return;
		}

		$this->entitiesManager->saveEntity($entity, $ownerId);
		$this->output->writeln('<comment>Entity created<comment>');
	}


	/**
	 * @param string $type
	 * @param string $accountName
	 *
	 * @throws Exception
	 */
	private function createAccount(string $type, string $accountName): void {
		if ($accountName === '') {
			throw new Exception('must specify an account name (--account)');
		}

		$account = new EntityAccount();
		$account->setType($type);
		$account->setAccount($accountName);
		$this->outputAccount($account);

		$this->output->writeln('');
		$helper = $this->getHelper('question');
		$question = new ConfirmationQuestion(
			'<info>Create this EntityAccount?</info> (Y/n) ', true, '/^(y|Y)/i'
		);
		if (!$helper->ask($this->input, $this->output, $question)) {
			return;
		}

		$this->entitiesManager->saveAccount($account);
		$this->output->writeln('<comment>EntityAccount created<comment>');
	}


	/**
	 * @param string $status
	 * @param string $entityId
	 * @param string $slaveId
	 * @param string $accountId
	 * @param string $level
	 *
	 * @throws Exception
	 */
	private function createMember(
		string $status, string $entityId, string $accountId, string $slaveId, string $level
	): void {
		$listLevel = array_values(IEntityMember::CONVERT_LEVEL);
		if (!in_array($level, $listLevel)) {
			throw new Exception(
				'must specify a level (--level): ' . implode(', ', $listLevel)
			);
		}

		try {
			$entity = $this->entitiesManager->getEntity($entityId);
		} catch (EntityNotFoundException $e) {
			throw new Exception('must specify a valid EntityId (--entity)');
		}

		$account = null;
		$slave = null;
		try {
			$account = $this->entitiesManager->getAccount($accountId);
		} catch (EntityAccountNotFoundException $e) {
			try {
				$slave = $this->entitiesManager->getEntity($slaveId);
			} catch (EntityNotFoundException $e) {
				throw new Exception(
					'must specify a valid AccountId (--account) or SlaveEntityId (--slave)'
				);
			}
		}

		$member = new EntityMember();
		$member->setStatus($status);
		$member->setLevel(array_search($level, IEntityMember::CONVERT_LEVEL));
		$member->setEntity($entity);
		if ($slave !== null) {
			$member->setSlaveEntityId($slave->getId());
		}
		if ($account !== null) {
			$member->setAccount($account);
		}

		$this->outputMember($member);
//		$this->outputEntity($entity);

//		if ($account !== null) {
//			$this->outputAccount($account);
//		}
//		if ($slave !== null) {
//			$this->outputEntity($slave);
//		}

		$this->output->writeln('');
		$helper = $this->getHelper('question');
		$question = new ConfirmationQuestion(
			'<info>Create this EntityMember?</info> (Y/n) ', true, '/^(y|Y)/i'
		);
		if (!$helper->ask($this->input, $this->output, $question)) {
			return;
		}

		$this->entitiesManager->saveMember($member);
		$this->output->writeln('<comment>EntityMember created<comment>');
	}


	/**
	 * @param string $item
	 * @param string $type
	 *
	 * @throws Exception
	 */
	private function verifyType(string $item, string $type) {
		switch ($item) {
			case 'entity':
				$interface = IEntities::INTERFACE;
				break;

			case 'account':
				$interface = IEntitiesAccounts::INTERFACE;
				break;

			case 'member':
				return;

			default:
				throw new Exception('unknown item');
		}

		$entityTypes = $this->entitiesHelper->getEntityTypes($interface);
		$types = array_map(
			function(IEntityType $item) {
				return $item->getType();
			}, $entityTypes
		);

		if (!in_array($type, $types)) {
			throw new Exception('Please specify a type: ' . implode(', ', $types));
		}
	}

}

