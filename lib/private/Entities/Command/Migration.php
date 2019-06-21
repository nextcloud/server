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
use OC\Core\Command\Base;
use OCP\Entities\Helper\IEntitiesMigrationHelper;
use OCP\Entities\IEntitiesManager;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


/**
 * Class Migration
 *
 * @package OC\Entities\Command
 */
class Migration extends Base {


	/** @var IEntitiesManager */
	private $entitiesManager;

	/** @var IEntitiesMigrationHelper */
	private $entitiesMigrationHelper;


	/**
	 * Migration constructor.
	 *
	 * @param IEntitiesManager $entitiesManager
	 * @param IEntitiesMigrationHelper $entitiesMigrationHelper
	 */
	public function __construct(
		IEntitiesManager $entitiesManager, IEntitiesMigrationHelper $entitiesMigrationHelper
	) {
		parent::__construct();

		$this->entitiesManager = $entitiesManager;
		$this->entitiesMigrationHelper = $entitiesMigrationHelper;
	}


	/**
	 *
	 */
	protected function configure() {
		parent::configure();
		$this->setName('entities:migration')
			 ->setDescription('Migrate current users/groups/circles to Entities');
	}


	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 *
	 * @throws Exception
	 */
	protected function execute(InputInterface $input, OutputInterface $output) {

		$this->entitiesMigrationHelper->migrateUsers();
		$this->entitiesMigrationHelper->migrateGroups();

	}


}

