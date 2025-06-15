<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Core\Command\User;

use OC\Core\Command\Base;
use OCP\Accounts\IAccount;
use OCP\Accounts\IAccountManager;
use OCP\Accounts\PropertyDoesNotExistException;
use OCP\IUser;
use OCP\IUserManager;
use Stecman\Component\Symfony\Console\BashCompletion\CompletionContext;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Profile extends Base {
	public function __construct(
		protected IUserManager $userManager,
		protected IAccountManager $accountManager,
	) {
		parent::__construct();
	}

	protected function configure() {
		parent::configure();
		$this
			->setName('user:profile')
			->setDescription('Read and modify user profile properties')
			->addArgument(
				'uid',
				InputArgument::REQUIRED,
				'Account ID used to login'
			)
			->addArgument(
				'key',
				InputArgument::OPTIONAL,
				'Profile property to set, get or delete',
				''
			)

			// Get
			->addOption(
				'default-value',
				null,
				InputOption::VALUE_REQUIRED,
				'(Only applicable on get) If no default value is set and the property does not exist, the command will exit with 1'
			)

			// Set
			->addArgument(
				'value',
				InputArgument::OPTIONAL,
				'The new value of the property',
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
				'Specify this option to delete the property value'
			)
			->addOption(
				'error-if-not-exists',
				null,
				InputOption::VALUE_NONE,
				'Checks whether the property exists before deleting it'
			)
		;
	}

	protected function checkInput(InputInterface $input): IUser {
		$uid = $input->getArgument('uid');
		$user = $this->userManager->get($uid);
		if (!$user) {
			throw new \InvalidArgumentException('The user "' . $uid . '" does not exist.');
		}
		// normalize uid
		$input->setArgument('uid', $user->getUID());

		$key = $input->getArgument('key');
		if ($key === '') {
			if ($input->hasParameterOption('--default-value')) {
				throw new \InvalidArgumentException('The "default-value" option can only be used when specifying a key.');
			}
			if ($input->getArgument('value') !== null) {
				throw new \InvalidArgumentException('The value argument can only be used when specifying a key.');
			}
			if ($input->getOption('delete')) {
				throw new \InvalidArgumentException('The "delete" option can only be used when specifying a key.');
			}
		}

		if ($input->getArgument('value') !== null && $input->hasParameterOption('--default-value')) {
			throw new \InvalidArgumentException('The value argument can not be used together with "default-value".');
		}
		if ($input->getOption('update-only') && $input->getArgument('value') === null) {
			throw new \InvalidArgumentException('The "update-only" option can only be used together with "value".');
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

		return $user;
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		try {
			$user = $this->checkInput($input);
		} catch (\InvalidArgumentException $e) {
			$output->writeln('<error>' . $e->getMessage() . '</error>');
			return self::FAILURE;
		}

		$uid = $input->getArgument('uid');
		$key = $input->getArgument('key');
		$userAccount = $this->accountManager->getAccount($user);

		if ($key === '') {
			$settings = $this->getAllProfileProperties($userAccount);
			$this->writeArrayInOutputFormat($input, $output, $settings);
			return self::SUCCESS;
		}

		$value = $this->getStoredValue($userAccount, $key);
		$inputValue = $input->getArgument('value');
		if ($inputValue !== null) {
			if ($input->hasParameterOption('--update-only') && $value === null) {
				$output->writeln('<error>The property does not exist for user "' . $uid . '".</error>');
				return self::FAILURE;
			}

			return $this->editProfileProperty($output, $userAccount, $key, $inputValue);
		} elseif ($input->hasParameterOption('--delete')) {
			if ($input->hasParameterOption('--error-if-not-exists') && $value === null) {
				$output->writeln('<error>The property does not exist for user "' . $uid . '".</error>');
				return self::FAILURE;
			}

			return $this->deleteProfileProperty($output, $userAccount, $key);
		} elseif ($value !== null) {
			$output->writeln($value);
		} elseif ($input->hasParameterOption('--default-value')) {
			$output->writeln($input->getOption('default-value'));
		} else {
			$output->writeln('<error>The property does not exist for user "' . $uid . '".</error>');
			return self::FAILURE;
		}

		return self::SUCCESS;
	}

	private function deleteProfileProperty(OutputInterface $output, IAccount $userAccount, string $key): int {
		return $this->editProfileProperty($output, $userAccount, $key, '');
	}

	private function editProfileProperty(OutputInterface $output, IAccount $userAccount, string $key, string $value): int {
		try {
			$userAccount->getProperty($key)->setValue($value);
		} catch (PropertyDoesNotExistException $exception) {
			$output->writeln('<error>' . $exception->getMessage() . '</error>');
			return self::FAILURE;
		}

		$this->accountManager->updateAccount($userAccount);
		return self::SUCCESS;
	}

	private function getStoredValue(IAccount $userAccount, string $key): ?string {
		try {
			$property = $userAccount->getProperty($key);
		} catch (PropertyDoesNotExistException) {
			return null;
		}
		return $property->getValue() === '' ? null : $property->getValue();
	}

	private function getAllProfileProperties(IAccount $userAccount): array {
		$properties = [];

		foreach ($userAccount->getAllProperties() as $property) {
			if ($property->getValue() !== '') {
				$properties[$property->getName()] = $property->getValue();
			}
		}

		return $properties;
	}

	/**
	 * @param string $argumentName
	 * @param CompletionContext $context
	 * @return string[]
	 */
	public function completeArgumentValues($argumentName, CompletionContext $context): array {
		if ($argumentName === 'uid') {
			return array_map(static fn (IUser $user) => $user->getUID(), $this->userManager->search($context->getCurrentWord()));
		}
		if ($argumentName === 'key') {
			$userId = $context->getWordAtIndex($context->getWordIndex() - 1);
			$user = $this->userManager->get($userId);
			if (!($user instanceof IUser)) {
				return [];
			}

			$account = $this->accountManager->getAccount($user);

			$properties = $this->getAllProfileProperties($account);
			return array_keys($properties);
		}
		return [];
	}
}
