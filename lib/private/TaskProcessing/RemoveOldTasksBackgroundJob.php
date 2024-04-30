<?php

namespace OC\TaskProcessing;

use OC\TaskProcessing\Db\TaskMapper;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\TimedJob;
use Psr\Log\LoggerInterface;

class RemoveOldTasksBackgroundJob extends TimedJob {
	public const MAX_TASK_AGE_SECONDS = 60 * 50 * 24 * 7 * 4; // 4 weeks

	public function __construct(
		ITimeFactory $timeFactory,
		private TaskMapper $taskMapper,
		private LoggerInterface $logger,
	) {
		parent::__construct($timeFactory);
		$this->setInterval(60 * 60 * 24);
		// can be deferred to maintenance window
		$this->setTimeSensitivity(TimedJob::TIME_INSENSITIVE);
	}


	/**
	 * @inheritDoc
	 */
	protected function run($argument) {
		try {
			$this->taskMapper->deleteOlderThan(self::MAX_TASK_AGE_SECONDS);
		} catch (\OCP\DB\Exception $e) {
			$this->logger->warning('Failed to delete stale language model tasks', ['exception' => $e]);
		}
	}
}
