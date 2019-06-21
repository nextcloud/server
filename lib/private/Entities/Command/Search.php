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
use OCP\Entities\Helper\IEntitiesHelper;
use OCP\Entities\IEntitiesManager;
use OCP\Entities\Implementation\IEntities\IEntities;
use OCP\Entities\Implementation\IEntitiesAccounts\IEntitiesAccounts;
use OCP\Entities\Model\IEntityAccount;
use OCP\Entities\Model\IEntityMember;
use OCP\Entities\Model\IEntityType;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;


class Search extends ExtendedBase {


	/** @var IEntitiesManager */
	private $entitiesManager;

	/** @var IEntitiesHelper */
	private $entitiesHelper;


	public function __construct(IEntitiesHelper $entitiesHelper, IEntitiesManager $entitiesManager
	) {
		parent::__construct();

		$this->entitiesHelper = $entitiesHelper;
		$this->entitiesManager = $entitiesManager;
	}


	/**
	 *
	 */
	protected function configure() {
		parent::configure();
		$this->setName('entities:search')
			 ->addArgument('needle', InputArgument::OPTIONAL, 'needle', '')
			 ->addOption('accounts', '', InputOption::VALUE_NONE, 'search for accounts')
			 ->addOption(
				 'viewer', '', InputOption::VALUE_REQUIRED, 'search from a user\'s point of view',
				 ''
			 )
			 ->addOption(
				 'visibility', '', InputOption::VALUE_REQUIRED, 'level of visibility (as admin)',
				 'none'
			 )
			 ->addOption(
				 'non-admin-viewer', '', InputOption::VALUE_NONE,
				 'create a non-admin temporary viewer'
			 )
			 ->addOption('type', '', InputOption::VALUE_REQUIRED, 'limit to a type', '')
			 ->setDescription('Search for entities');
	}


	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 *
	 * @throws Exception
	 */
	protected function execute(InputInterface $input, OutputInterface $output) {
		$output = new ConsoleOutput();
		$this->output = $output->section();

		$needle = $input->getArgument('needle');
		$viewerName = $input->getOption('viewer');
		$type = $input->getOption('type');
		$level = $input->getOption('visibility');

		if ($input->getOption('accounts')) {
			$this->searchAccounts($needle, $type);
		} else {

			if ($viewerName === '') {
				$viewer =
					$this->entitiesHelper->temporaryLocalAccount(
						!$input->getOption('non-admin-viewer')
					);
			} else {
				$viewer = $this->entitiesHelper->getLocalAccount($viewerName);
			}


			$listLevel = array_values(IEntityMember::CONVERT_LEVEL);
			if (!in_array($level, $listLevel)) {
				throw new Exception(
					'must specify an Visibility Level (--visibility): ' . implode(', ', $listLevel)
				);
			}

			$viewer->getOptions()
				   ->setOptionInt(
					   'viewer.visibility', array_search($level, IEntityMember::CONVERT_LEVEL)
				   );


			$this->searchEntities($needle, $viewer, $type);
		}

		$this->output->writeln('');
	}


	/**
	 * @param string $needle
	 * @param string $type
	 *
	 * @throws Exception
	 */
	private function searchAccounts(string $needle, $type = ''): void {

		if ($type !== '') {
			$this->verifyType(IEntitiesAccounts::INTERFACE, $type);
		}

		if ($needle === '') {
			$accounts = $this->entitiesManager->getAllAccounts($type);
		} else {
			$accounts = $this->entitiesManager->searchAccounts($needle, $type);
		}

		$table = new Table($this->output);
		$table->setHeaders(['Account Id', 'Type', 'Account', 'Admin']);
		$table->render();
		$this->output->writeln('');
		foreach ($accounts as $account) {
			$table->appendRow(
				[
					'<info>' . $account->getId() . '</info>',
					'<comment>' . $account->getType() . '</comment>',
					$account->getAccount(),
					$account->hasAdminRights() ? '<info>yes</info>' : 'no'
				]
			);
		}

	}


	/**
	 * @param string $needle
	 * @param IEntityAccount $viewer
	 * @param string $type
	 *
	 * @throws Exception
	 */
	private function searchEntities(string $needle, IEntityAccount $viewer, $type = ''): void {

		$this->output->writeln('Viewer: <info>' . $viewer->getAccount() . '</info>');
		$this->output->writeln('');
		$this->outputAccount($viewer);
		$this->entitiesManager->setViewer($viewer);

		$this->output->writeln('');

		if ($type !== '') {
			$this->verifyType(IEntities::INTERFACE, $type);
		}

		if ($needle === '') {
			$entities = $this->entitiesManager->getAllEntities($type);
		} else {
			$entities = $this->entitiesManager->searchEntities($needle, $type);
		}

		$table = new Table($this->output);
		$table->setHeaders(
			[
				'Entity Id', 'Type', 'Name', 'Owner Id', 'Owner Account', 'Owner Type', 'Admin',
				'Viewer Status', 'Viewer Level'
			]
		);
		$table->render();
		$this->output->writeln('');
		foreach ($entities as $entity) {
			$ownerId = '';
			$ownerName = '';
			$ownerType = '';
			if ($entity->hasOwner()) {
				$owner = $entity->getOwner();
				$ownerId = '<info>' . $owner->getId() . '</info>';
				$ownerName = $owner->getAccount();
				$ownerType = '<comment>' . $owner->getType() . '</comment>';
			}

			$viewerStatus = '';
			$viewerLevel = '';
			if ($entity->hasViewer()) {
				$viewer = $entity->getViewer();
				$viewerStatus =
					($viewer->getStatus() !== IEntityMember::STATUS_MEMBER) ? $viewer->getStatus(
					) : '<info>' . $viewer->getStatus() . '</info>';
				$viewerLevel = ($viewer->getLevel() > 0) ? '<info>' . $viewer->getLevelString()
														   . '</info>' : $viewer->getLevelString();
			}

			$table->appendRow(
				[
					'<info>' . $entity->getId() . '</info>',
					'<comment>' . $entity->getType() . '</comment>',
					$entity->getName(),
					$ownerId,
					$ownerName,
					$ownerType,
					$entity->hasAdminRights() ? '<info>yes</info>' : 'no',
					$viewerStatus,
					$viewerLevel
				]
			);
		}
	}


	/**
	 * @param string $interface
	 * @param string $type
	 *
	 * @throws Exception
	 */
	private function verifyType(string $interface, string $type) {

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

