<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Files_External\Command;

use OC\Core\Command\Base;
use OCA\Files_External\Lib\Auth\AuthMechanism;
use OCA\Files_External\Lib\Backend\Backend;
use OCA\Files_External\Lib\DefinitionParameter;
use OCA\Files_External\Service\BackendService;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Backends extends Base {
	public function __construct(
		private BackendService $backendService,
	) {
		parent::__construct();
	}

	protected function configure(): void {
		$this
			->setName('files_external:backends')
			->setDescription('Show available authentication and storage backends')
			->addArgument(
				'type',
				InputArgument::OPTIONAL,
				'only show backends of a certain type. Possible values are "authentication" or "storage"'
			)->addArgument(
				'backend',
				InputArgument::OPTIONAL,
				'only show information of a specific backend'
			);
		parent::configure();
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$authBackends = $this->backendService->getAuthMechanisms();
		$storageBackends = $this->backendService->getBackends();

		$data = [
			'authentication' => array_map([$this, 'serializeAuthBackend'], $authBackends),
			'storage' => array_map([$this, 'serializeAuthBackend'], $storageBackends)
		];

		$type = $input->getArgument('type');
		$backend = $input->getArgument('backend');
		if ($type) {
			if (!isset($data[$type])) {
				$output->writeln('<error>Invalid type "' . $type . '". Possible values are "authentication" or "storage"</error>');
				return self::FAILURE;
			}
			$data = $data[$type];

			if ($backend) {
				if (!isset($data[$backend])) {
					$output->writeln('<error>Unknown backend "' . $backend . '" of type  "' . $type . '"</error>');
					return self::FAILURE;
				}
				$data = $data[$backend];
			}
		}

		$this->writeArrayInOutputFormat($input, $output, $data);
		return self::SUCCESS;
	}

	private function serializeAuthBackend(\JsonSerializable $backend): array {
		$data = $backend->jsonSerialize();
		$result = [
			'name' => $data['name'],
			'identifier' => $data['identifier'],
			'configuration' => $this->formatConfiguration($data['configuration'])
		];
		if ($backend instanceof Backend) {
			$result['storage_class'] = $backend->getStorageClass();
			$authBackends = $this->backendService->getAuthMechanismsByScheme(array_keys($backend->getAuthSchemes()));
			$result['supported_authentication_backends'] = array_keys($authBackends);
			$authConfig = array_map(function (AuthMechanism $auth) {
				return $this->serializeAuthBackend($auth)['configuration'];
			}, $authBackends);
			$result['authentication_configuration'] = array_combine(array_keys($authBackends), $authConfig);
		}
		return $result;
	}

	/**
	 * @param DefinitionParameter[] $parameters
	 * @return string[]
	 */
	private function formatConfiguration(array $parameters): array {
		$configuration = array_filter($parameters, function (DefinitionParameter $parameter) {
			return $parameter->isFlagSet(DefinitionParameter::FLAG_HIDDEN);
		});
		return array_map(function (DefinitionParameter $parameter) {
			return $parameter->getTypeName();
		}, $configuration);
	}
}
