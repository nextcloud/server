<?php
/**
 * @author Robin Appelman <icewind@owncloud.com>
 *
 * @copyright Copyright (c) 2016, ownCloud, Inc.
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OC\Core\Command\Queue;

use OC\Command\BusFactory;

use OC\Command\LaravelBus;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Worker extends Command {
	/** @var BusFactory  */
	private $busFactory;

	public function __construct(BusFactory $busFactory) {
		$this->busFactory = $busFactory;
		parent::__construct();
	}

	protected function configure() {
		$this
			->setName('queue:worker')
			->setDescription('run a job queue worker');
	}

	protected function execute(InputInterface $input, OutputInterface $output) {
		$bus = $this->busFactory->getAsyncBus();
		if (!$bus instanceof LaravelBus) {
			$output->writeln('<error>Queue worker is not applicable when using the cron based job queue</error>');
			return 1;
		}

		$queue = $bus->getQueue();
		$worker = new \Illuminate\Queue\Worker($queue->getQueueManager());
		$worker->daemon('default');
	}
}
