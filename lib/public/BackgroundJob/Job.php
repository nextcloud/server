<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2018, Roeland Jago Douma <roeland@famdouma.nl>
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
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
namespace OCP\BackgroundJob;

use OCP\AppFramework\Utility\ITimeFactory;
use OCP\ILogger;
use Psr\Log\LoggerInterface;

/**
 * Base class for background jobs
 *
 * This is here if you want to do advanced stuff in your background jobs.
 * For the most common use cases have a look at QueuedJob and TimedJob
 *
 * @since 15.0.0
 */
abstract class Job implements IJob, IParallelAwareJob {
	protected int $id = 0;
	protected int $lastRun = 0;
	protected $argument;
	protected ITimeFactory $time;
	protected bool $allowParallelRuns = true;

	/**
	 * @since 15.0.0
	 */
	public function __construct(ITimeFactory $time) {
		$this->time = $time;
	}

	/**
	 * The function to prepare the execution of the job.
	 *
	 * @return void
	 *
	 * @since 15.0.0
	 * @deprecated since 25.0.0 Use start() instead. This method will be removed
	 * with the ILogger interface
	 */
	public function execute(IJobList $jobList, ?ILogger $logger = null) {
		$this->start($jobList);
	}

	/**
	 * @inheritdoc
	 * @since 25.0.0
	 */
	public function start(IJobList $jobList): void {
		$jobList->setLastRun($this);
		$logger = \OCP\Server::get(LoggerInterface::class);

		try {
			$jobDetails = get_class($this) . ' (id: ' . $this->getId() . ', arguments: ' . json_encode($this->getArgument()) . ')';
			$jobStartTime = $this->time->getTime();
			$logger->debug('Starting job ' . $jobDetails, ['app' => 'cron']);
			$this->run($this->argument);
			$timeTaken = $this->time->getTime() - $jobStartTime;

			$logger->debug('Finished job ' . $jobDetails . ' in ' . $timeTaken . ' seconds', ['app' => 'cron']);
			$jobList->setExecutionTime($this, $timeTaken);
		} catch (\Throwable $e) {
			if ($logger) {
				$logger->error('Error while running background job ' . $jobDetails, [
					'app' => 'core',
					'exception' => $e,
				]);
			}
		}
	}

	/**
	 * @since 15.0.0
	 */
	final public function setId(int $id) {
		$this->id = $id;
	}

	/**
	 * @since 15.0.0
	 */
	final public function setLastRun(int $lastRun) {
		$this->lastRun = $lastRun;
	}

	/**
	 * @since 15.0.0
	 */
	public function setArgument($argument) {
		$this->argument = $argument;
	}

	/**
	 * @since 15.0.0
	 */
	final public function getId(): int {
		return $this->id;
	}

	/**
	 * @since 15.0.0
	 */
	final public function getLastRun(): int {
		return $this->lastRun;
	}

	/**
	 * @since 15.0.0
	 */
	public function getArgument() {
		return $this->argument;
	}

	/**
	 * Set this to false to prevent two Jobs from this class from running in parallel
	 *
	 * @param bool $allow
	 * @return void
	 * @since 27.0.0
	 */
	public function setAllowParallelRuns(bool $allow): void {
		$this->allowParallelRuns = $allow;
	}

	/**
	 * @return bool
	 * @since 27.0.0
	 */
	public function getAllowParallelRuns(): bool {
		return $this->allowParallelRuns;
	}

	/**
	 * The actual function that is called to run the job
	 *
	 * @param $argument
	 * @return void
	 *
	 * @since 15.0.0
	 */
	abstract protected function run($argument);
}
