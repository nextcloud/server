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


use daita\NcSmallPhpTools\Traits\TArrayTools;
use OC\Core\Command\Base;
use OCP\Entities\Model\IEntity;
use OCP\Entities\Model\IEntityAccount;
use OCP\Entities\Model\IEntityMember;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


/**
 * Class Details
 *
 * @package OC\Entities\Command
 */
class ExtendedBase extends Base {


	use TArrayTools;


	/** @var InputInterface */
	protected $input;

	/** @var OutputInterface */
	protected $output;

	/** @var bool */
	protected $short = false;


	/**
	 * @param IEntity $entity
	 * @param string $prefix
	 */
	protected function outputEntity(IEntity $entity, $prefix = ''): void {
		$this->output($prefix . '- Entity Id: <info>' . $entity->getId() . '</info>');
		$this->output($prefix . '  - Type: <comment>' . $entity->getType() . '</comment>');
		$this->output($prefix . '  - Name: <comment>' . $entity->getName() . '</comment>');
		$this->output(
			$prefix . '  - Access: <info>' . $entity->getAccess() . '</info> ('
			. $entity->getAccessString() . ')', true
		);
		$this->output(
			$prefix . '  - Visibility: <info>' . $entity->getVisibility() . '</info> ('
			. $entity->getVisibilityString() . ')', true
		);

		if ($entity->getCreation() > 0) {
			$this->output(
				$prefix . '  - Creation: <info>' . date('Y-m-d H:i:s', $entity->getCreation()) . '</info>', true
			);
		}
	}


	/**
	 * @param IEntity[] $entities
	 */
	protected function outputEntities(array $entities): void {
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
	 * @param IEntityAccount $account
	 * @param string $prefix
	 */
	protected function outputAccount(IEntityAccount $account, $prefix = ''): void {
		$this->output($prefix . '- Account Id: <info>' . $account->getId() . '</info>');
		$this->output($prefix . '  - Type: <comment>' . $account->getType() . '</comment>');
		$this->output(
			$prefix . '  - Account: <comment>' . $account->getAccount() . '</comment>'
		);
		$this->output(
			$prefix . '  - Admin: ' . ($account->hasAdminRights() ? '<info>yes</info>' : 'no')
		);

		if ($account->getCreation() > 0) {
			$this->output(
				$prefix . '  - Creation: <info>' . date('Y-m-d H:i:s', $account->getCreation()) . '</info>', true
			);
		}

		if ($account->getDeleteOn() > 0) {
			$this->output(
				$prefix . '  - Deadline: <info>' . date('Y-m-d H:i:s', $account->getDeleteOn()) . '</info>', true
			);
		}
	}


	/**
	 * @param IEntityAccount[] $accounts
	 */
	protected function outputAccounts(array $accounts): void {
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
	 * @param IEntityMember $member
	 * @param string $prefix
	 * @param array $details
	 */
	protected function outputMember(IEntityMember $member, string $prefix = '', array $details = []
	) {
		$this->output($prefix . '- Member Id: <info>' . $member->getId() . '</info>');

		if ($this->getBool('entity', $details, true)) {
			$this->outputEntity($member->getEntity(), $prefix . '  ');
		} else {
			$this->output(
				$prefix . '  - Entity Id: <info>' . $member->getEntityId() . '</info>', true
			);
		}

		if ($this->getBool('account', $details, true)) {
			$this->outputAccount($member->getAccount(), $prefix . '  ');
		} else {
			$this->output(
				$prefix . '  - Account Id: <info>' . $member->getAccountId() . '</info>', true
			);

		}

		$this->output(
			$prefix . '  - Slave Entity Id: <info>' . $member->getSlaveEntityId() . '</info>'
		);
		$this->output($prefix . '  - Status: <info>' . $member->getStatus() . '</info>', true);
		$this->output($prefix . '  - Level: <info>' . $member->getLevel() . '</info>', true);

		if ($member->getCreation() > 0) {
			$this->output(
				$prefix . '  - Creation: <info>' . date('Y-m-d H:i:s', $member->getCreation()) . '</info>', true
			);
		}
	}


	/**
	 * @param string $line
	 * @param bool $optional
	 */
	protected function output(string $line, bool $optional = false) {
		if ($optional && $this->short) {
			return;
		}
		$this->output->writeln($line);
	}

}

