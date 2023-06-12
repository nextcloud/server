<?php
/**
 * @copyright Copyright (c) 2016 Robin Appelman <robin@icewind.nl>
 *
 * @author Joas Schilling <coding@schilljs.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <robin@icewind.nl>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */
namespace OC\Core\Command\User;

use OC\Core\Command\Base;
use OCP\IGroupManager;
use OCP\IUser;
use OCP\IUserManager;
use Stecman\Component\Symfony\Console\BashCompletion\CompletionContext;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Info extends Base {
	public function __construct(
		protected IUserManager $userManager,
		protected IGroupManager $groupManager,
	) {
		parent::__construct();
	}

	protected function configure() {
		$this
			->setName('user:info')
			->setDescription('show user info')
			->addArgument(
				'user',
				InputArgument::REQUIRED,
				'user to show'
			)->addOption(
				'output',
				null,
				InputOption::VALUE_OPTIONAL,
				'Output format (plain, json or json_pretty, default is plain)',
				$this->defaultOutputFormat
			);
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$user = $this->userManager->get($input->getArgument('user'));
		if (is_null($user)) {
			$output->writeln('<error>user not found</error>');
			return 1;
		}
		$groups = $this->groupManager->getUserGroupIds($user);
		$data = [
			'user_id' => $user->getUID(),
			'display_name' => $user->getDisplayName(),
			'email' => (string)$user->getSystemEMailAddress(),
			'cloud_id' => $user->getCloudId(),
			'enabled' => $user->isEnabled(),
			'groups' => $groups,
			'quota' => $user->getQuota(),
			'storage' => $this->getStorageInfo($user),
			'last_seen' => date(\DateTimeInterface::ATOM, $user->getLastLogin()), // ISO-8601
			'user_directory' => $user->getHome(),
			'backend' => $user->getBackendClassName()
		];
		$this->writeArrayInOutputFormat($input, $output, $data);
		return 0;
	}

	/**
	 * @param IUser $user
	 * @return array
	 */
	protected function getStorageInfo(IUser $user): array {
		\OC_Util::tearDownFS();
		\OC_Util::setupFS($user->getUID());
		try {
			$storage = \OC_Helper::getStorageInfo('/');
		} catch (\OCP\Files\NotFoundException $e) {
			return [];
		}
		return [
			'free' => $storage['free'],
			'used' => $storage['used'],
			'total' => $storage['total'],
			'relative' => $storage['relative'],
			'quota' => $storage['quota'],
		];
	}

	/**
	 * @param string $argumentName
	 * @param CompletionContext $context
	 * @return string[]
	 */
	public function completeArgumentValues($argumentName, CompletionContext $context) {
		if ($argumentName === 'user') {
			return array_map(static fn (IUser $user) => $user->getUID(), $this->userManager->search($context->getCurrentWord()));
		}
		return [];
	}
}
