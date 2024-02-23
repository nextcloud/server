<?php
/**
 * @copyright Copyright (c) 2017 Arthur Schiwon <blizzz@arthur-schiwon.de>
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
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
namespace OC\Collaboration\AutoComplete;

use OCP\Collaboration\AutoComplete\IManager;
use OCP\Collaboration\AutoComplete\ISorter;
use OCP\IServerContainer;

class Manager implements IManager {
	/** @var string[] */
	protected array $sorters = [];

	/** @var ISorter[]  */
	protected array $sorterInstances = [];

	public function __construct(
		private IServerContainer $container,
	) {
	}

	public function runSorters(array $sorters, array &$sortArray, array $context): void {
		$sorterInstances = $this->getSorters();
		while ($sorter = array_shift($sorters)) {
			if (isset($sorterInstances[$sorter])) {
				$sorterInstances[$sorter]->sort($sortArray, $context);
			} else {
				$this->container->getLogger()->warning('No sorter for ID "{id}", skipping', [
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
				/** @var ISorter $instance */
				$instance = $this->container->resolve($sorter);
				if (!$instance instanceof ISorter) {
					$this->container->getLogger()->notice('Skipping sorter which is not an instance of ISorter. Class name: {class}',
						['app' => 'core', 'class' => $sorter]);
					continue;
				}
				$sorterId = trim($instance->getId());
				if (trim($sorterId) === '') {
					$this->container->getLogger()->notice('Skipping sorter with empty ID. Class name: {class}',
						['app' => 'core', 'class' => $sorter]);
					continue;
				}
				$this->sorterInstances[$sorterId] = $instance;
			}
		}
		return $this->sorterInstances;
	}
}
