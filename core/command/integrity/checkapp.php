<?php
/**
 * @author Bart Visscher <bartv@thisnet.nl>
 * @author Björn Schießle <schiessle@owncloud.com>
 * @author Christian Kampka <christian@kampka.net>
 * @author Joas Schilling <nickvergessen@owncloud.com>
 * @author Lukas Reschke <lukas@owncloud.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin McCorkell <robin@mccorkell.me.uk>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author Victor Dubiniuk <dubiniuk@owncloud.com>
 * @author Vincent Petry <pvince81@owncloud.com>
 *
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */
namespace OC\Core\Command\Integrity;

use OC\IntegrityCheck\Checker;
use OC\Core\Command\Base;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class SignApp
 *
 * @package OC\Core\Command\Integrity
 */
class CheckApp extends Base {
	public function __construct(Checker $checker) {
		parent::__construct();
		$this->checker = $checker;
	}
	
	/**
	 * {@inheritdoc }
	 */
	protected function configure() {
		parent::configure();
		$this
			->setName('integrity:check-app')
			->setDescription('Check an app integrity using a signature.')
			->addArgument('appid', null, InputArgument::REQUIRED, 'Application to check');
	}

	/**
	 * {@inheritdoc }
	 */
	protected function execute(InputInterface $input, OutputInterface $output) {
		$appid = $input->getArgument('appid');
		$result = $this->checker->verifyAppSignature($appid);
		$this->writeArrayInOutputFormat($input, $output, $result);
	}

}
