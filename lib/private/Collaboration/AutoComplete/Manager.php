<?php
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Collaboration\AutoComplete;

use OCP\Collaboration\AutoComplete\IManager;
use OCP\Collaboration\AutoComplete\ISorter;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

class Manager implements IManager {
	/** @var string[] */
	protected array $sorters = [];

	/** @var ISorter[] */
	protected array $sorterInstances = [];

	public function __construct(
		private ContainerInterface $container,
		private LoggerInterface $logger,
	) {
	}

	public function runSorters(array $sorters, array &$sortArray, array $context): void {
		$sorterInstances = $this->getSorters();
		while ($sorter = array_shift($sorters)) {
			if (isset($sorterInstances[$sorter])) {
				$sorterInstances[$sorter]->sort($sortArray, $context);
			} else {
				$this->logger->warning('No sorter for ID "{id}", skipping', [
					'app' => 'core', 'id' => $sorter
				]);
			}
		}
	}

	public function registerSorter($className): void {
		$this->sorters[] = $className;
	}

	protected function getSorters(): array {
		if (count($this->sorterInstances) === 0) {
			foreach ($this->sorters as $sorter) {
				try {
					$instance = $this->container->get($sorter);
				} catch (ContainerExceptionInterface) {
					$this->logger->notice(
						'Skipping not registered sorter. Class name: {class}',
						['app' => 'core', 'class' => $sorter],
					);
					continue;
				}
				if (!$instance instanceof ISorter) {
					$this->logger->notice('Skipping sorter which is not an instance of ISorter. Class name: {class}',
						['app' => 'core', 'class' => $sorter]);
					continue;
				}
				$sorterId = trim($instance->getId());
				if (trim($sorterId) === '') {
					$this->logger->notice('Skipping sorter with empty ID. Class name: {class}',
						['app' => 'core', 'class' => $sorter]);
					continue;
				}
				$this->sorterInstances[$sorterId] = $instance;
			}
		}
		return $this->sorterInstances;
	}
}
