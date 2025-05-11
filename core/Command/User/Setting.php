<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Core\Command\User;

use OC\Core\Command\Base;
use OCP\Accounts\IAccountManager;
use OCP\IConfig;
use OCP\IUser;
use OCP\IUserManager;
use Stecman\Component\Symfony\Console\BashCompletion\CompletionContext;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Setting extends Base {
	public function __construct(
		protected IUserManager $userManager,
		protected IAccountManager $accountManager,
		protected IConfig $config,
	) {
		parent::__construct();
	}

	protected function configure() {
		parent::configure();
		$this
			->setName('user:setting')
			->setDescription('Read and modify user settings')
			->addArgument(
				'uid',
				InputArgument::REQUIRED,
				'Account ID used to login'
			)
			->addArgument(
				'app',
				InputArgument::OPTIONAL,
				'Restrict the settings to a given app',
				''
			)
			->addArgument(
				'key',
				InputArgument::OPTIONAL,
				'Setting key to set, get or delete',
				''
			)
			->addOption(
				'ignore-missing-user',
				null,
				InputOption::VALUE_NONE,
				'Use this option to ignore errors when the user does not exist'
			)

			// Get
			->addOption(
				'default-value',
				null,
				InputOption::VALUE_REQUIRED,
				'(Only applicable on get) If no default value is set and the config does not exist, the command will exit with 1'
			)

			// Set
			->addArgument(
				'value',
				InputArgument::OPTIONAL,
				'The new value of the setting',
				null
			)
			->addOption(
				'update-only',
				null,
				InputOption::VALUE_NONE,
				'Only updates the value, if it is not set before, it is not being added'
			)

			// Delete
			->addOption(
				'delete',
				null,
				InputOption::VALUE_NONE,
				'Specify this option to delete the config'
			)
			->addOption(
				'error-if-not-exists',
				null,
				InputOption::VALUE_NONE,
				'Checks whether the setting exists before deleting it'
			)
		;
	}

	protected function checkInput(InputInterface $input) {
		if (!$input->getOption('ignore-missing-user')) {
			$uid = $input->getArgument('uid');
			$user = $this->userManager->get($uid);
			if (!$user) {
				throw new \InvalidArgumentException('The user "' . $uid . '" does not exist.');
			}
			// normalize uid
			$input->setArgument('uid', $user->getUID());
		}

		if ($input->getArgument('key') === '' && $input->hasParameterOption('--default-value')) {
			throw new \InvalidArgumentException('The "default-value" option can only be used when specifying a key.');
		}

		if ($input->getArgument('key') === '' && $input->getArgument('value') !== null) {
			throw new \InvalidArgumentException('The value argument can only be used when specifying a key.');
		}
		if ($input->getArgument('value') !== null && $input->hasParameterOption('--default-value')) {
			throw new \InvalidArgumentException('The value argument can not be used together with "default-value".');
		}
		if ($input->getOption('update-only') && $input->getArgument('value') === null) {
			throw new \InvalidArgumentException('The "update-only" option can only be used together with "value".');
		}

		if ($input->getArgument('key') === '' && $input->getOption('delete')) {
			throw new \InvalidArgumentException('The "delete" option can only be used when specifying a key.');
		}
		if ($input->getOption('delete') && $input->hasParameterOption('--default-value')) {
			throw new \InvalidArgumentException('The "delete" option can not be used together with "default-value".');
		}
		if ($input->getOption('delete') && $input->getArgument('value') !== null) {
			throw new \InvalidArgumentException('The "delete" option can not be used together with "value".');
		}
		if ($input->getOption('error-if-not-exists') && !$input->getOption('delete')) {
			throw new \InvalidArgumentException('The "error-if-not-exists" option can only be used together with "delete".');
		}
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		try {
			$this->checkInput($input);
		} catch (\InvalidArgumentException $e) {
			$output->writeln('<error>' . $e->getMessage() . '</error>');
			return 1;
		}

		$uid = $input->getArgument('uid');
		$app = $input->getArgument('app');
		$key = $input->getArgument('key');

		if ($key === '') {
			$settings = $this->getUserSettings($uid, $app);
			$this->writeArrayInOutputFormat($input, $output, $settings);
			return 0;
		}

		$value = $this->getStoredValue($uid, $app, $key);
		if ($input->getArgument('value') !== null) {
			if ($input->hasParameterOption('--update-only') && $value === null) {
				$output->writeln('<error>The setting does not exist for user "' . $uid . '".</error>');
				return 1;
			}

			if ($app === 'profile'
				&& in_array($key, IAccountManager::ALLOWED_PROPERTIES)
				&& $key !== IAccountManager::PROPERTY_EMAIL
				&& $key !== IAccountManager::PROPERTY_DISPLAYNAME) {
				$this->editProfileProperty($uid, $key, $input->getArgument('value'));
				return 0;
			} else if ($this->isSettingProperty($app, $key)) {
				$returnCode = $this->setSettingsProperty($input, $output, $uid, $key);
				if ($returnCode !== null) {
					return $returnCode;
				}
			}

			$this->config->setUserValue($uid, $app, $key, $input->getArgument('value'));
		} elseif ($input->hasParameterOption('--delete')) {
			if ($input->hasParameterOption('--error-if-not-exists') && $value === null) {
				$output->writeln('<error>The setting does not exist for user "' . $uid . '".</error>');
				return 1;
			}

			if ($this->isProfileProperty($app, $key)) {
				$this->deleteProfileProperty($app, $key);
				return 0;
			}

			if ($this->isSettingProperty($app, $key)) {
				$returnCode = $this->deleteSettingsProperty($output, $uid, $key);
				if ($returnCode !== null) {
					return $returnCode;
				}
			}

			$this->config->deleteUserValue($uid, $app, $key);
		} elseif ($value !== null) {
			$output->writeln($value);
		} elseif ($input->hasParameterOption('--default-value')) {
			$output->writeln($input->getOption('default-value'));
		} elseif ($app === 'settings' && $key === 'display_name') {
			$user = $this->userManager->get($uid);
			$output->writeln($user->getDisplayName());
		} else {
			$output->writeln('<error>The setting does not exist for user "' . $uid . '".</error>');
			return 1;
		}

		return 0;
	}

	private function deleteProfileProperty($uid, $key): void {
		$this->editProfileProperty($uid, $key, '');
	}

	private function editProfileProperty($uid, $key, $value): void {
		$user = $this->userManager->get($uid);
		if (!$user) {
			throw new \InvalidArgumentException('The user "' . $uid . '" must exist to edit this setting.');
		}
		$account = $this->accountManager->getAccount($user);
		$account->getProperty($key)->setValue($value);
		$this->accountManager->updateAccount($account);
	}

	private function deleteSettingsProperty(OutputInterface $output, $uid, $key): ?int {
		$user = $this->userManager->get($uid);
		if ($user instanceof IUser) {
			if ($key === 'email') {
				$user->setEMailAddress('');
				// setEmailAddress already deletes the value
				return 0;
			} elseif ($key === 'display_name') {
				$output->writeln('<error>Display name can\'t be deleted.</error>');
				return 1;
			}
		}

		return null;
	}
	private function setSettingsProperty(InputInterface $input, OutputInterface $output, string $uid, string $key): ?int {
		$user = $this->userManager->get($uid);
		if ($user instanceof IUser) {
			if ($key === 'email') {
				$user->setEMailAddress($input->getArgument('value'));
			} elseif ($key === 'display_name') {
				if (!$user->setDisplayName($input->getArgument('value'))) {
					if ($user->getDisplayName() === $input->getArgument('value')) {
						$output->writeln('<error>New and old display name are the same</error>');
					} elseif ($input->getArgument('value') === '') {
						$output->writeln('<error>New display name can\'t be empty</error>');
					} else {
						$output->writeln('<error>Could not set display name</error>');
					}
					return 1;
				}
			}
			// setEmailAddress and setDisplayName both internally set the value
			return 0;
		}
		return null;
	}

	private function getStoredValue(mixed $uid, mixed $app, mixed $key): ?string {
		if ($app === 'profile') {
			$user = $this->userManager->get($uid);
			$account = $this->accountManager->getAccount($user);
			$property = $account->getProperty($key);
			return $property->getValue() === '' ? null : $property->getValue();
		}

		return $this->config->getUserValue($uid, $app, $key, null);
	}


	protected function getUserSettings(string $uid, string $app): array {
		$settings = $this->config->getAllUserValues($uid);
		if ($app !== '') {
			if (isset($settings[$app])) {
				$settings = [$app => $settings[$app]];
			} else {
				$settings = [];
			}
		}

		// add user properties that are not stored as settings
		$settings = array_merge_recursive($settings, $this->getExtraSettings($uid, $app));
		return $settings;
	}

	private function getExtraSettings(string $uid, string $app): array {
		$user = $this->userManager->get($uid);
		$settings = [];
		if ($user === null) {
			return $settings;
		}

		if (!$app || $app === 'settings') {
			// Only add the display name if the user exists
			$settings['settings']['display_name'] = $user->getDisplayName();
		}

		if (!$app || $app === 'profile') {
			$userAccount = $this->accountManager->getAccount($user);
			foreach ($userAccount->getAllProperties() as $property) {
				if ($property->getValue() !== '' && !in_array($property->getName(), ['email', 'displayname', 'profile_enabled'])) {
					$settings['profile'][$property->getName()] = $property->getValue();
				}
			}
		}

		return $settings;
	}

	private function isSettingProperty(string $app, string $key): bool {
		return $app === 'settings' && in_array($key, ['email', 'display_name']);
	}

	private function isProfileProperty(string $app, string $key): bool {
		return $app === 'profile'
			&& in_array($key, IAccountManager::ALLOWED_PROPERTIES)
			&& $key !== IAccountManager::PROPERTY_EMAIL
			&& $key !== IAccountManager::PROPERTY_DISPLAYNAME;
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
		if ($argumentName === 'app') {
			$userId = $context->getWordAtIndex($context->getWordIndex() - 1);
			$settings = $this->getUserSettings($userId, '');
			return array_keys($settings);
		}
		if ($argumentName === 'key') {
			$userId = $context->getWordAtIndex($context->getWordIndex() - 2);
			$app = $context->getWordAtIndex($context->getWordIndex() - 1);
			$settings = $this->getUserSettings($userId, $app);
			return array_keys($settings[$app]);
		}
		return [];
	}
}
