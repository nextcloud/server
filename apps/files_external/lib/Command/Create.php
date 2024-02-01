<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Robin Appelman <robin@icewind.nl>
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
namespace OCA\Files_External\Command;

use OC\Core\Command\Base;
use OC\Files\Filesystem;
use OC\User\NoUserException;
use OCA\Files_External\Lib\Auth\AuthMechanism;
use OCA\Files_External\Lib\Backend\Backend;
use OCA\Files_External\Lib\DefinitionParameter;
use OCA\Files_External\Lib\StorageConfig;
use OCA\Files_External\Service\BackendService;
use OCA\Files_External\Service\GlobalStoragesService;
use OCA\Files_External\Service\StoragesService;
use OCA\Files_External\Service\UserStoragesService;
use OCP\IUserManager;
use OCP\IUserSession;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpFoundation\Response;

class Create extends Base {
	public function __construct(
		private GlobalStoragesService $globalService,
		private UserStoragesService $userService,
		private IUserManager $userManager,
		private IUserSession $userSession,
		private BackendService $backendService,
	) {
		parent::__construct();
	}

	protected function configure(): void {
		$this
			->setName('files_external:create')
			->setDescription('Create a new mount configuration')
			->addOption(
				'user',
				'',
				InputOption::VALUE_OPTIONAL,
				'user to add the mount configuration for, if not set the mount will be added as system mount'
			)
			->addArgument(
				'mount_point',
				InputArgument::REQUIRED,
				'mount point for the new mount'
			)
			->addArgument(
				'storage_backend',
				InputArgument::REQUIRED,
				'storage backend identifier for the new mount, see `occ files_external:backends` for possible values'
			)
			->addArgument(
				'authentication_backend',
				InputArgument::REQUIRED,
				'authentication backend identifier for the new mount, see `occ files_external:backends` for possible values'
			)
			->addOption(
				'config',
				'c',
				InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
				'Mount configuration option in key=value format'
			)
			->addOption(
				'dry',
				'',
				InputOption::VALUE_NONE,
				'Don\'t save the created mount, only list the new mount'
			);
		parent::configure();
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$user = (string)$input->getOption('user');
		$mountPoint = $input->getArgument('mount_point');
		$storageIdentifier = $input->getArgument('storage_backend');
		$authIdentifier = $input->getArgument('authentication_backend');
		$configInput = $input->getOption('config');

		$storageBackend = $this->backendService->getBackend($storageIdentifier);
		$authBackend = $this->backendService->getAuthMechanism($authIdentifier);

		if (!Filesystem::isValidPath($mountPoint)) {
			$output->writeln('<error>Invalid mountpoint "' . $mountPoint . '"</error>');
			return self::FAILURE;
		}
		if (is_null($storageBackend)) {
			$output->writeln('<error>Storage backend with identifier "' . $storageIdentifier . '" not found (see `occ files_external:backends` for possible values)</error>');
			return Response::HTTP_NOT_FOUND;
		}
		if (is_null($authBackend)) {
			$output->writeln('<error>Authentication backend with identifier "' . $authIdentifier . '" not found (see `occ files_external:backends` for possible values)</error>');
			return Response::HTTP_NOT_FOUND;
		}
		$supportedSchemes = array_keys($storageBackend->getAuthSchemes());
		if (!in_array($authBackend->getScheme(), $supportedSchemes)) {
			$output->writeln('<error>Authentication backend "' . $authIdentifier . '" not valid for storage backend "' . $storageIdentifier . '" (see `occ files_external:backends storage ' . $storageIdentifier . '` for possible values)</error>');
			return self::FAILURE;
		}

		$config = [];
		foreach ($configInput as $configOption) {
			if (!str_contains($configOption, '=')) {
				$output->writeln('<error>Invalid mount configuration option "' . $configOption . '"</error>');
				return self::FAILURE;
			}
			[$key, $value] = explode('=', $configOption, 2);
			if (!$this->validateParam($key, $value, $storageBackend, $authBackend)) {
				$output->writeln('<error>Unknown configuration for backends "' . $key . '"</error>');
				return self::FAILURE;
			}
			$config[$key] = $value;
		}

		$mount = new StorageConfig();
		$mount->setMountPoint($mountPoint);
		$mount->setBackend($storageBackend);
		$mount->setAuthMechanism($authBackend);
		$mount->setBackendOptions($config);

		if ($user) {
			if (!$this->userManager->userExists($user)) {
				$output->writeln('<error>User "' . $user . '" not found</error>');
				return self::FAILURE;
			}
			$mount->setApplicableUsers([$user]);
		}

		if ($input->getOption('dry')) {
			$this->showMount($user, $mount, $input, $output);
		} else {
			$this->getStorageService($user)->addStorage($mount);
			if ($input->getOption('output') === self::OUTPUT_FORMAT_PLAIN) {
				$output->writeln('<info>Storage created with id ' . $mount->getId() . '</info>');
			} else {
				$output->writeln((string)$mount->getId());
			}
		}
		return self::SUCCESS;
	}

	private function validateParam(string $key, &$value, Backend $storageBackend, AuthMechanism $authBackend): bool {
		$params = array_merge($storageBackend->getParameters(), $authBackend->getParameters());
		foreach ($params as $param) {
			/** @var DefinitionParameter $param */
			if ($param->getName() === $key) {
				if ($param->getType() === DefinitionParameter::VALUE_BOOLEAN) {
					$value = ($value === 'true');
				}
				return true;
			}
		}
		return false;
	}

	private function showMount(string $user, StorageConfig $mount, InputInterface $input, OutputInterface $output): void {
		$listCommand = new ListCommand($this->globalService, $this->userService, $this->userSession, $this->userManager);
		$listInput = new ArrayInput([], $listCommand->getDefinition());
		$listInput->setOption('output', $input->getOption('output'));
		$listInput->setOption('show-password', true);
		$listCommand->listMounts($user, [$mount], $listInput, $output);
	}

	protected function getStorageService(string $userId): StoragesService {
		if (empty($userId)) {
			return $this->globalService;
		}

		$user = $this->userManager->get($userId);
		if (is_null($user)) {
			throw new NoUserException("user $userId not found");
		}
		$this->userSession->setUser($user);
		return $this->userService;
	}
}
