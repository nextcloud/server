<?php

declare(strict_types=1);
/**
 * @copyright Copyright (c) 2021 Robin Appelman <robin@icewind.nl>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\Files_External\Command;

use OC\Core\Command\Base;
use OCA\Files_External\Lib\InsufficientDataForMeaningfulAnswerException;
use OCA\Files_External\Lib\StorageConfig;
use OCA\Files_External\NotFoundException;
use OCA\Files_External\Service\GlobalStoragesService;
use OCP\Files\Storage\IStorage;
use OCP\Files\StorageNotAvailableException;
use OCP\IUserManager;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

abstract class StorageAuthBase extends Base {
	public function __construct(
		protected GlobalStoragesService $globalService,
		protected IUserManager $userManager,
	) {
		parent::__construct();
	}

	private function getUserOption(InputInterface $input): ?string {
		if ($input->getOption('user')) {
			return (string)$input->getOption('user');
		}

		return $_ENV['NOTIFY_USER'] ?? $_SERVER['NOTIFY_USER'] ?? null;
	}

	private function getPasswordOption(InputInterface $input): ?string {
		if ($input->getOption('password')) {
			return (string)$input->getOption('password');
		}

		return $_ENV['NOTIFY_PASSWORD'] ?? $_SERVER['NOTIFY_PASSWORD'] ?? null;
	}

	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 * @return array
	 * @psalm-return array{0: StorageConfig, 1: IStorage}|array{0: null, 1: null}
	 */
	protected function createStorage(InputInterface $input, OutputInterface $output): array {
		try {
			/** @var StorageConfig|null $mount */
			$mount = $this->globalService->getStorage($input->getArgument('mount_id'));
		} catch (NotFoundException $e) {
			$output->writeln('<error>Mount not found</error>');
			return [null, null];
		}
		if (is_null($mount)) {
			$output->writeln('<error>Mount not found</error>');
			return [null, null];
		}
		$noAuth = false;

		$userOption = $this->getUserOption($input);
		$passwordOption = $this->getPasswordOption($input);

		// if only the user is provided, we get the user object to pass along to the auth backend
		// this allows using saved user credentials
		$user = ($userOption && !$passwordOption) ? $this->userManager->get($userOption) : null;

		try {
			$authBackend = $mount->getAuthMechanism();
			$authBackend->manipulateStorageConfig($mount, $user);
		} catch (InsufficientDataForMeaningfulAnswerException $e) {
			$noAuth = true;
		} catch (StorageNotAvailableException $e) {
			$noAuth = true;
		}

		if ($userOption) {
			$mount->setBackendOption('user', $userOption);
		}
		if ($passwordOption) {
			$mount->setBackendOption('password', $passwordOption);
		}

		try {
			$backend = $mount->getBackend();
			$backend->manipulateStorageConfig($mount, $user);
		} catch (InsufficientDataForMeaningfulAnswerException $e) {
			$noAuth = true;
		} catch (StorageNotAvailableException $e) {
			$noAuth = true;
		}

		try {
			$class = $mount->getBackend()->getStorageClass();
			/** @var IStorage $storage */
			$storage = new $class($mount->getBackendOptions());
			if (!$storage->test()) {
				throw new \Exception();
			}
			return [$mount, $storage];
		} catch (\Exception $e) {
			$output->writeln('<error>Error while trying to create storage</error>');
			if ($noAuth) {
				$output->writeln('<error>Username and/or password required</error>');
			}
			return [null, null];
		}
	}
}
