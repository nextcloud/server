<?php
/**
 * @copyright Copyright (c) 2016, ownCloud GmbH.
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Jörn Friedrich Dreyer <jfd@butonic.de>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
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
namespace OCA\Files_Versions\Command;

use OCA\Files_Versions\Expiration;
use OCA\Files_Versions\Storage;
use OCP\IUser;
use OCP\IUserManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ExpireVersions extends Command {
	public function __construct(
		private IUserManager $userManager,
		private Expiration $expiration,
	) {
		parent::__construct();
	}

	protected function configure(): void {
		$this
			->setName('versions:expire')
			->setDescription('Expires the users file versions')
			->addArgument(
				'user_id',
				InputArgument::OPTIONAL | InputArgument::IS_ARRAY,
				'expire file versions of the given user(s), if no user is given file versions for all users will be expired.'
			);
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$maxAge = $this->expiration->getMaxAgeAsTimestamp();
		if (!$maxAge) {
			$output->writeln("Auto expiration is configured - expiration will be handled automatically according to the expiration patterns detailed at the following link https://docs.nextcloud.com/server/latest/admin_manual/configuration_files/file_versioning.html.");
			return self::FAILURE;
		}

		$users = $input->getArgument('user_id');
		if (!empty($users)) {
			foreach ($users as $user) {
				if (!$this->userManager->userExists($user)) {
					$output->writeln("<error>Unknown user $user</error>");
					return self::FAILURE;
				}

				$output->writeln("Remove deleted files of   <info>$user</info>");
				$userObject = $this->userManager->get($user);
				$this->expireVersionsForUser($userObject);
			}
			return self::SUCCESS;
		}

		$p = new ProgressBar($output);
		$p->start();
		$this->userManager->callForSeenUsers(function (IUser $user) use ($p) {
			$p->advance();
			$this->expireVersionsForUser($user);
		});
		$p->finish();
		$output->writeln('');
		return self::SUCCESS;
	}

	public function expireVersionsForUser(IUser $user): void {
		$uid = $user->getUID();
		if (!$this->setupFS($uid)) {
			return;
		}
		Storage::expireOlderThanMaxForUser($uid);
	}

	/**
	 * Act on behalf on versions item owner
	 */
	protected function setupFS(string $user): bool {
		\OC_Util::tearDownFS();
		\OC_Util::setupFS($user);

		// Check if this user has a version directory
		$view = new \OC\Files\View('/' . $user);
		if (!$view->is_dir('/files_versions')) {
			return false;
		}

		return true;
	}
}
