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
use OCP\Entities\Helper\IEntitiesHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


/**
 * Class Install
 *
 * @package OC\Entities\Command
 */
class Install extends Base {


	/** @var IEntitiesHelper */
	private $entitiesHelper;


	/**
	 * Migration constructor.
	 *
	 * @param IEntitiesHelper $entitiesHelper
	 */
	public function __construct(IEntitiesHelper $entitiesHelper) {
		parent::__construct();

		$this->entitiesHelper = $entitiesHelper;
	}


	/**
	 *
	 */
	protected function configure() {
		parent::configure();
		$this->setName('entities:install')
			 ->setDescription('Fresh install');
	}


	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 *
	 * @throws Exception
	 */
	protected function execute(InputInterface $input, OutputInterface $output) {
		$this->entitiesHelper->refreshInstall();
	}


}

