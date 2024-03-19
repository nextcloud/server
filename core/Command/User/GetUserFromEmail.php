<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Andreas Fischer <bantu@owncloud.com>
 * @author Christopher Schäpers <kondou@ts.unde.re>
 * @author Clark Tomlinson <fallen013@gmail.com>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Laurens Post <lkpost@scept.re>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Sujith H <sharidasan@owncloud.com>
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
use OCP\App\IAppManager;
use OCP\IUser;
use OCP\IUserManager;
use Stecman\Component\Symfony\Console\BashCompletion\CompletionContext;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;

class GetUserFromEmail extends Base {
	public function __construct(
		protected IUserManager $userManager,
		private IAppManager $appManager,
	) {
		parent::__construct();
	}

	protected function configure() {
		$this
			->setName('user:getfromemail')
			->setDescription('Find the user ID for the user with a given email')
			->addArgument(
				'email',
				InputArgument::REQUIRED,
				'Email address'
			)
		;
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$email = $input->getArgument('email');

		$users = $this->userManager->getByEmail($email);
		if (count($users) == 0) {
			$output->writeln('<error>User with given email not found</error>');
			return 1;

		}
		foreach ($users as $user) {
			$output->writeln($user->getUID());
		}
		return 0;
	}

	/**
	 * @param string $argumentName
	 * @param CompletionContext $context
	 * @return string[]
	 */
	public function completeArgumentValues($argumentName, CompletionContext $context) {
		if ($argumentName === 'email') {
			return array_map(static fn (IUser $user) => $user->getEmail(), $this->userManager->search($context->getCurrentWord()));
		}
		return [];
	}
}
