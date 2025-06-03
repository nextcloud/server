<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Core\Command\User;

use OC\Core\Command\Base;
use OCP\Accounts\IAccountManager;
use OCP\Accounts\PropertyDoesNotExistException;
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

		// prevent reading or editing of "profile" app properties exposed via the "settings" app
		if ($this->isOverlappingSettingProperty($app, $key)) {
			$output->writeln("<error>Setting '{$key}' belongs to the settings app.</error>");
			return 1;
		}

		$value = $this->getStoredValue($uid, $app, $key);
		$inputValue = $input->getArgument('value');
		if ($inputValue !== null) {
			if ($input->hasParameterOption('--update-only') && $value === null) {
				$output->writeln('<error>The setting does not exist for user "' . $uid . '".</error>');
				return 1;
			}

			if ($this->isProfileProperty($app, $key)) {
				return $this->editProfileProperty($output, $uid, $key, $inputValue);
			} elseif ($this->isSettingProperty($app, $key)) {
				return $this->setSettingsProperty($output, $uid, $key, $inputValue);
			} else {
				$this->config->setUserValue($uid, $app, $key, $inputValue);
			}
		} elseif ($input->hasParameterOption('--delete')) {
			if ($input->hasParameterOption('--error-if-not-exists') && $value === null) {
				$output->writeln('<error>The setting does not exist for user "' . $uid . '".</error>');
				return 1;
			}

			if ($this->isProfileProperty($app, $key)) {
				return $this->deleteProfileProperty($output, $uid, $key);
			} elseif ($this->isSettingProperty($app, $key)) {
				return $this->deleteSettingsProperty($output, $uid, $key);
			} else {
				$this->config->deleteUserValue($uid, $app, $key);
			}
		} elseif ($value !== null) {
			$output->writeln($value);
		} elseif ($input->hasParameterOption('--default-value')) {
			$output->writeln($input->getOption('default-value'));
		} else {
			$output->writeln('<error>The setting does not exist for user "' . $uid . '".</error>');
			return 1;
		}

		return 0;
	}

	/**
	 * @throws PropertyDoesNotExistException if $key is not a property of the account.
	 */
	private function deleteProfileProperty(OutputInterface $output, string $uid, string $key): int {
		return $this->editProfileProperty($output, $uid, $key, '');
	}

	/**
	 * @throws PropertyDoesNotExistException if $key is not a property of the account.
	 */
	private function editProfileProperty(OutputInterface $output, string $uid, string $key, string $value): int {
		$user = $this->userManager->get($uid);
		if (!$user) {
			$output->writeln("<error>The user {$uid} must exist to edit this setting.</error>");
			return 1;
		}
		$account = $this->accountManager->getAccount($user);
		$account->getProperty($key)->setValue($value);
		$this->accountManager->updateAccount($account);
		return 0;
	}

	private function deleteSettingsProperty(OutputInterface $output, string $uid, string $key): int {
		$user = $this->userManager->get($uid);
		if (!($user instanceof IUser)) {
			$output->writeln("<error>The user {$uid} must exist to delete this setting.</error>");
			return 1;
		}

		if ($key === 'email') {
			$user->setEMailAddress('');
			// setEmailAddress already deletes the value
			return 0;
		} elseif ($key === 'display_name') {
			$output->writeln('<error>Display name can\'t be deleted.</error>');
			return 1;
		}

		$output->writeln("<error>Unknown setting: {$key}</error>");
		return 1;
	}

	private function setSettingsProperty(OutputInterface $output, string $uid, string $key, string $value): int {
		$user = $this->userManager->get($uid);
		if (!($user instanceof IUser)) {
			$output->writeln("<error>The user {$uid} must exist to set this setting.</error>");
			return 1;
		}

		if ($key === 'email') {
			$user->setEMailAddress($value);
		} elseif ($key === 'display_name') {
			if (!$user->setDisplayName($value)) {
				if ($user->getDisplayName() === $value) {
					$output->writeln('<error>New and old display name are the same</error>');
				} elseif ($value === '') {
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

	private function getStoredValue(string $uid, string $app, string $key): ?string {
		if ($app === 'profile') {
			$user = $this->userManager->get($uid);
			$account = $this->accountManager->getAccount($user);
			$property = $account->getProperty($key);
			return $property->getValue() === '' ? null : $property->getValue();
		} elseif ($app === 'settings' && $key === 'display_name') {
			$user = $this->userManager->get($uid);
			return $user->getDisplayName();
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

	private function isOverlappingSettingProperty(string $app, string $key): bool {
		return $app === 'profile' && in_array($key, ['email', 'display_name', 'displayname']);
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
