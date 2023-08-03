<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Pierre Ozoux <pierre@ozoux.net>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 *
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 */
namespace OC\Core\Command\User;

use OC\Core\Command\Base;
use OCP\IUser;
use OCP\IUserManager;
use Stecman\Component\Symfony\Console\BashCompletion\CompletionContext;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class LastSeen extends Base {
	public function __construct(
		protected IUserManager $userManager,
	) {
		parent::__construct();
	}

	protected function configure(): void {
		$this
			->setName('user:lastseen')
			->setDescription('shows when the user was logged in last time')
			->addArgument(
				'uid',
				InputArgument::OPTIONAL,
				'the username'
			)
			->addOption(
				'all',
				null,
				InputOption::VALUE_NONE,
				'shows a list of when all users were last logged in'
			)
		;
	}

	protected function execute(InputInterface $input, OutputInterface $output): int	{
		$singleUserId = $input->getArgument('uid');
		if ($singleUserId) {
			$user = $this->userManager->get($singleUserId);
			if (is_null($user)) {
				$output->writeln('<error>User does not exist</error>');
				return 1;
			}
			$users = [$user];
		} elseif ($input->getOption('all')) {
			$users = $this->userManager->search('');
		} else {
			$output->writeln("<error>Please specify a username, or \"--all\" to list all</error>");
			return 1;
		}
		return 0;
	}

	/**
	 * @param string $argumentName
	 * @param CompletionContext $context
	 * @return string[]
	 */
	public function completeArgumentValues($argumentName, CompletionContext $context) {
		if ($argumentName === 'uid') {
			return array_map(static fn (IUser $user) => $user->getUID(), $this->userManager->search($context->getCurrentWord()));
		}
		return [];
	}
}
