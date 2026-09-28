<?php

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Core\Command\User;

use OC\Core\Command\Base;
use OCP\Files\NotFoundException;
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
			'first_seen' => $this->formatLoginDate($user->getFirstLogin()),
			'last_seen' => $this->formatLoginDate($user->getLastLogin()),
			'user_directory' => $user->getHome(),
			'backend' => $user->getBackendClassName()
		];
		$this->writeArrayInOutputFormat($input, $output, $data);
		return 0;
	}

	private function formatLoginDate(int $timestamp): string {
		if ($timestamp < 0) {
			return 'unknown';
		} elseif ($timestamp === 0) {
			return 'never';
		} else {
			return date(\DateTimeInterface::ATOM, $timestamp); // ISO-8601
		}
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
		} catch (NotFoundException $e) {
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
