<?php
/**
 * @author Morris Jobke <hey@morrisjobke.de>
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

namespace OC\Core\Command\User;

use OC\Authentication\Token\IProvider;
use OCP\IUserManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Sessions extends Command {
	/** @var IProvider */
	protected $provider;
	/** @var IUserManager */
	protected $userManager;

	/**
	 * @param IProvider $provider
	 */
	public function __construct(IProvider $provider, IUserManager $userManager) {
		$this->provider = $provider;
		$this->userManager = $userManager;
		parent::__construct();
	}

	protected function configure() {
		$this
			->setName('user:sessions')
			->setDescription('shows active sessions of a user')
			->addArgument(
				'uid',
				InputArgument::REQUIRED,
				'the username'
			);
	}

	protected function execute(InputInterface $input, OutputInterface $output) {
		$uid = $input->getArgument('uid');
		$user = $this->userManager->get($uid);
		if(is_null($user)) {
			$output->writeln('<error>User "' . $uid . '" does not exist</error>');
			return;
		}

		$rows = [];
		$sessions = $this->provider->getTokenByUser($user);
		foreach ($sessions as $session) {
			$rows[] = [
				$session->getId(),
				$session->getName(),
				date('Y-m-d H:i:s e (P)', $session->getLastActivity())
			];
		}
		if(empty($rows)) {
			$output->writeln('No sessions for user "' . $uid . '" available.');
			return;
		}

		/** @var \Symfony\Component\Console\Helper\TableHelper $table */
		$table = $this->getHelperSet()->get('table');
		$table->setHeaders(['id', 'name', 'last activity']);
		$table->setRows($rows);
		$table->render($output);

	}
}
