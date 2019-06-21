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


use daita\NcSmallPhpTools\Exceptions\ShellMissingCommandException;
use daita\NcSmallPhpTools\Exceptions\ShellMissingItemException;
use daita\NcSmallPhpTools\Exceptions\ShellUnknownCommandException;
use daita\NcSmallPhpTools\Exceptions\ShellUnknownItemException;
use daita\NcSmallPhpTools\IInteractiveShellClient;
use daita\NcSmallPhpTools\Service\InteractiveShell;
use daita\NcSmallPhpTools\Traits\TStringTools;
use Exception;
use OC\Entities\Exceptions\EntityAccountNotFoundException;
use OC\Entities\Exceptions\EntityMemberNotFoundException;
use OC\Entities\Exceptions\EntityNotFoundException;
use OC\Entities\Exceptions\EntityTypeNotFoundException;
use OC\Entities\Model\Entity;
use OC\Entities\Model\EntityAccount;
use OCP\Entities\Helper\IEntitiesHelper;
use OCP\Entities\IEntitiesManager;
use OCP\Entities\Implementation\IEntities\IEntities;
use OCP\Entities\Implementation\IEntitiesAccounts\IEntitiesAccounts;
use OCP\Entities\Model\IEntity;
use OCP\Entities\Model\IEntityAccount;
use OCP\Entities\Model\IEntityMember;
use OCP\Entities\Model\IEntityType;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;


/**
 * Class Session
 *
 * @package OC\Entities\Command
 */
class Session extends ExtendedBase implements IInteractiveShellClient {


	use TStringTools;


	/** @var IEntitiesManager */
	private $entitiesManager;

	/** @var IEntitiesHelper */
	private $entitiesHelper;


	/** @var IEntityAccount */
	private $viewer;

	/** @var InteractiveShell */
	private $interactiveShell;


	/**
	 * Session constructor.
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
	protected function configure(): void {
		parent::configure();
		$this->setName('entities:session')
			 ->addArgument(
				 'viewer', InputArgument::OPTIONAL, 'session from a user\'s point of view',
				 ''
			 )
			 ->addOption(
				 'visibility', '', InputOption::VALUE_REQUIRED, 'level of visibility (as admin)',
				 'none'
			 )
			 ->addOption(
				 'as-non-admin', '', InputOption::VALUE_NONE,
				 'create a non-admin temporary viewer'
			 )
			 ->setDescription('Start session as a temporary (or local) user');
	}


	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 *
	 * @throws Exception
	 */
	protected function execute(InputInterface $input, OutputInterface $output): void {

		$output = new ConsoleOutput();
		$this->output = $output->section();
		$this->input = $input;

		$this->generateViewer();
		$this->entitiesManager->setViewer($this->viewer);

		$this->output->writeln('* Identity used during this session:');
		$this->outputAccount($this->viewer);
		$this->output->writeln('');

		$this->interactiveShell = new InteractiveShell($this, $input, $output, $this);

		$commands = [
			'create.entity.?type_IEntities',
			'create.account.?type_IEntitiesAccounts',
			'list.entities.?type_IEntities',
			'list.accounts.?type_IEntitiesAccounts',
			'search.accounts',
			'details.?entity_id',
			'invite.?entity_id.?account_id',
			'join.?entity_id',
			'notifications'
		];

		$tag = '$';
		if ($this->viewer->hasAdminRights()) {
			$tag = '#';
			$commands = array_merge(
				$commands,
				[
					'create.member.?entity_id.?account_id',
				]
			);
		}

		sort($commands);
		$this->interactiveShell->setCommands($commands);

		$this->interactiveShell->run(
			'EntitiesManager [<info>' . $this->viewer->getAccount()
			. '</info>]:<comment>%PATH%</comment>' . $tag
		);

	}


	/**
	 * @param string $source
	 * @param string $needle
	 *
	 * @return string[]
	 */
	public function fillCommandList(string $source, string $needle): array {

		switch ($source) {
			case 'type':
				$entries = $this->entitiesHelper->getEntityTypes($needle);

				return array_map(
					function(IEntityType $entry) {
						return $entry->getType();
					}, $entries
				);

			case 'entity':
				$entries = $this->entitiesManager->getAllEntities();

				return array_map(
					function(IEntity $entry) {
						return $entry->getId();
					}, $entries
				);

			case 'account':
				$accounts = $this->entitiesManager->getAllAccounts();

				return array_map(
					function(IEntityAccount $entry) {
						return $entry->getId();
					}, $accounts
				);
		}

		return [];
	}


	/**
	 * @param string $command
	 *
	 * @throws ShellMissingCommandException
	 * @throws ShellMissingItemException
	 * @throws ShellUnknownCommandException
	 * @throws ShellUnknownItemException
	 */
	public function manageCommand(string $command): void {
		$args = explode(' ', $command);
		$cmd = array_shift($args);
		switch ($cmd) {

			case 'create':
				$this->manageCommandCreate($args);
				break;

			case 'list':
				$this->manageCommandList($args);
				break;

			case 'search':
				$this->manageCommandSearch($args);
				break;

			case 'details':
				$this->manageCommandDetails($args);
				break;

			case 'invite':
				$this->manageCommandInvite($args);
				break;

			case 'join':
				$this->manageCommandJoin($args);
				break;

			case 'notifications':
				$this->manageCommandNotifications($args);
				break;

			default:
				throw new ShellUnknownCommandException();
		}
	}


	/**
	 * @param array $args
	 *
	 * @throws ShellMissingCommandException
	 * @throws ShellUnknownCommandException
	 * @throws ShellUnknownItemException
	 */
	private function manageCommandCreate(array $args): void {
		$item = array_shift($args);
		switch ($item) {
			case 'entity':
				$this->manageCommandCreateEntity($args);
				break;

			case 'account':
				$this->manageCommandCreateAccount($args);
				break;

			case 'member':
				$this->manageCommandCreateMember($args);
				break;

			case '':
				throw new ShellMissingCommandException();

			default:
				throw new ShellUnknownCommandException();
		}
	}


	/**
	 * @param array $args
	 *
	 * @throws ShellUnknownItemException
	 * @throws ShellMissingCommandException
	 */
	private function manageCommandCreateEntity(array $args): void {
		$type = array_shift($args);
		if (!is_string($type)) {
			throw new ShellMissingCommandException();
		}

		try {
			$this->verifyEntityType($type);
		} catch (EntityTypeNotFoundException $e) {
			throw new ShellUnknownItemException();
		}

		$entity = new Entity();
		$entity->setType($type);

		$listVisibility = array_values(IEntity::CONVERT_VISIBILITY);
		$visibility = $this->interactiveShell->asking('Visibility', '', $listVisibility);
		$entity->setVisibility(array_search($visibility, IEntity::CONVERT_VISIBILITY));

		$listAccess = array_values(IEntity::CONVERT_ACCESS);
		$access = $this->interactiveShell->asking('Access', '', $listAccess);
		$entity->setAccess(array_search($access, IEntity::CONVERT_ACCESS));

		$entity->setName($this->interactiveShell->asking('Name', ''));


		$this->outputEntity($entity);
		try {
			$this->interactiveShell->confirming('Save this Entity?');
			$this->entitiesManager->saveEntity($entity);
			$this->output->writeln('<info>Entity saved</info>');
		} catch (Exception $e) {
			$this->output->writeln('<comment>Entity NOT saved</comment>');
		}

	}


	/**
	 * @param array $args
	 *
	 * @throws ShellMissingCommandException
	 * @throws ShellUnknownItemException
	 */
	private function manageCommandCreateAccount(array $args): void {
		$type = array_shift($args);
		if (!is_string($type)) {
			throw new ShellMissingCommandException();
		}

		try {
			$this->verifyAccountType($type);
		} catch (EntityTypeNotFoundException $e) {
			throw new ShellUnknownItemException();
		}

		$account = new EntityAccount();
		$account->setType($type);
		$account->setAccount($this->interactiveShell->asking('Account', ''));

		$this->outputAccount($account);
		try {
			$this->interactiveShell->confirming('Save this EntityAccount?');
			$this->entitiesManager->saveAccount($account);
			$this->output->writeln('<info>Account saved</info>');
		} catch (Exception $e) {
			$this->output->writeln('<comment>Account NOT saved</comment>');
		}
	}


	/**
	 * @param array $args
	 *
	 * @throws ShellMissingCommandException
	 * @throws ShellUnknownCommandException
	 * @throws ShellUnknownItemException
	 */
	private function manageCommandCreateMember(array $args): void {
		if (!$this->viewer->hasAdminRights()) {
			throw new ShellUnknownCommandException();
		}
	}


	/**
	 * @param array $args
	 *
	 * @throws ShellMissingCommandException
	 * @throws ShellUnknownCommandException
	 * @throws ShellUnknownItemException
	 */
	private function manageCommandList(array $args): void {
		$item = array_shift($args);
		switch ($item) {
			case 'entities':
				$this->manageCommandListEntities($args);
				break;

			case 'accounts':
				$this->manageCommandListAccounts($args);
				break;

			case '':
				throw new ShellMissingCommandException();

			default:
				throw new ShellUnknownCommandException();
		}
	}


	/**
	 * @param array $args
	 *
	 * @throws ShellUnknownItemException
	 */
	private function manageCommandListEntities(array $args): void {
		$type = array_shift($args);
		if (is_string($type)) {
			try {
				$this->verifyEntityType($type);
			} catch (EntityTypeNotFoundException $e) {
				throw new ShellUnknownItemException();
			}
		} else {
			$type = '';
		}

		$entities = $this->entitiesManager->getAllEntities($type);
		$this->outputEntities($entities);
	}


	/**
	 * @param array $args
	 *
	 * @throws ShellUnknownItemException
	 */
	private function manageCommandListAccounts(array $args): void {
		$type = array_shift($args);
		if (is_string($type)) {
			try {
				$this->verifyAccountType($type);
			} catch (EntityTypeNotFoundException $e) {
				throw new ShellUnknownItemException();
			}
		} else {
			$type = '';
		}

		$accounts = $this->entitiesManager->getAllAccounts($type);
		$this->outputAccounts($accounts);
	}


	/**
	 * @param array $args
	 *
	 * @throws ShellMissingItemException
	 */
	private function manageCommandSearch(array $args): void {
		$item = array_shift($args);
		if (!is_string($item)) {
			throw new ShellMissingItemException();
		}

		switch ($item) {
			case 'accounts':
				$item = array_shift($args);
				if (!is_string($item)) {
					throw new ShellMissingItemException();
				}

				$this->manageCommandSearchAccounts($item);
				break;

			default:
				$this->manageCommandSearchEntities($item);
		}
	}


	/**
	 * @param string $needle
	 */
	private function manageCommandSearchEntities(string $needle): void {
		$entities = $this->entitiesManager->searchEntities($needle);
		$this->outputEntities($entities);
	}


	/**
	 * @param string $needle
	 */
	private function manageCommandSearchAccounts(string $needle): void {
		$accounts = $this->entitiesManager->searchAccounts($needle);
		$this->outputAccounts($accounts);
	}


	/**
	 * @param array $args
	 *
	 * @throws ShellUnknownItemException
	 * @throws ShellMissingItemException
	 */
	private function manageCommandDetails(array $args): void {
		$itemId = array_shift($args);
		if (!is_string($itemId)) {
			throw new ShellMissingItemException();
		}

		try {
			$this->detailsOnEntity($itemId);

			return;
		} catch (EntityNotFoundException $e) {
		}

		try {
			$this->detailsOnAccount($itemId);

			return;
		} catch (EntityAccountNotFoundException $e) {
		}

		try {
			$this->detailsOnMember($itemId);

			return;
		} catch (EntityMemberNotFoundException $e) {
		}

		throw new ShellUnknownItemException();
	}


	/**
	 * @param string $itemId
	 *
	 * @throws EntityNotFoundException
	 */
	private function detailsOnEntity(string $itemId): void {

		$entity = $this->entitiesManager->getEntity($itemId);
		$this->outputEntity($entity);

		if (!$this->short) {
			$this->output('- Owner');
			if ($entity->getOwnerId() === '') {
				$this->output('  (no owner)');
			} else {
				$this->outputAccount($entity->getOwner(), '  ');
			}
		}

		$members = $entity->getMembers();
		$this->output('- getMembers (' . count($members) . ')');
		foreach ($members as $member) {
			$this->outputMember(
				$member, '  ',
				[
					'entity'  => ($member->getEntityId() !== $entity->getId()),
					'account' => ($member->getAccountId() !== $entity->getOwnerId()),
				]
			);
		}
	}


	/**
	 * @param string $itemId
	 *
	 * @throws EntityAccountNotFoundException
	 */
	private function detailsOnAccount(string $itemId): void {
		$account = $this->entitiesManager->getAccount($itemId);
		$this->outputAccount($account);

		$belongsTo = $account->belongsTo();
		$this->output('- belongsTo (' . count($belongsTo) . ')');
		foreach ($belongsTo as $member) {
			$this->outputMember(
				$member, '  ', [
						   'account' => ($member->getAccountId() !== $account->getId())
					   ]
			);
		}
	}


	/**
	 * @param string $itemId
	 *
	 * @throws EntityMemberNotFoundException
	 */
	private function detailsOnMember(string $itemId): void {
		$member = $this->entitiesManager->getMember($itemId);
		$this->outputMember($member);
	}


	/**
	 * @param array $args
	 *
	 * @throws ShellMissingItemException
	 * @throws ShellUnknownItemException
	 */
	private function manageCommandInvite(array $args): void {
		list($entityId, $accountId) = $args;

		if ($entityId === null || $accountId === null) {
			throw new ShellMissingItemException('syntax: invite entityId accountId');
		}

		try {
			$entity = $this->entitiesManager->getEntity($entityId);
		} catch (EntityNotFoundException $e) {
			throw new ShellUnknownItemException($entityId);
		}

		try {
			$account = $this->entitiesManager->getAccount($accountId);
		} catch (EntityAccountNotFoundException $e) {
			throw new ShellUnknownItemException($accountId);
		}

		$account->inviteTo($entity->getId());
	}

	private function manageCommandJoin(array $args): void {
	}

	private function manageCommandNotifications(array $args): void {
	}


	/**
	 * @throws EntityAccountNotFoundException
	 * @throws Exception
	 */
	private function generateViewer(): void {
		$viewerName = $this->input->getArgument('viewer');
		$level = $this->input->getOption('visibility');

		if ($viewerName === '') {
			$asAdmin = !$this->input->getOption('as-non-admin');
			$this->viewer = $this->entitiesHelper->temporaryLocalAccount($asAdmin);
		} else {
			$this->viewer = $this->entitiesHelper->getLocalAccount($viewerName);
		}

		$listLevel = array_values(IEntityMember::CONVERT_LEVEL);
		if (!in_array($level, $listLevel)) {
			throw new Exception(
				'must specify an Visibility Level (--visibility): ' . implode(', ', $listLevel)
			);
		}

		$this->viewer->getOptions()
					 ->setOptionInt(
						 'viewer.visibility', array_search($level, IEntityMember::CONVERT_LEVEL)
					 );
	}


	/**
	 * @param string $type
	 *
	 * @throws EntityTypeNotFoundException
	 */
	private function verifyEntityType(string $type): void {
		$all = $this->entitiesHelper->getEntityTypes(IEntities::INTERFACE);
		foreach ($all as $entityType) {
			if ($entityType->getType() === $type) {
				return;
			}
		}

		throw new EntityTypeNotFoundException();
	}


	/**
	 * @param string $type
	 *
	 * @throws EntityTypeNotFoundException
	 */
	private function verifyAccountType(string $type): void {
		$all = $this->entitiesHelper->getEntityTypes(IEntitiesAccounts::INTERFACE);
		foreach ($all as $entityType) {
			if ($entityType->getType() === $type) {
				return;
			}
		}

		throw new EntityTypeNotFoundException();
	}


}

