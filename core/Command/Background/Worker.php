<?php

namespace OC\Core\Command\Background;

use OC\Console\CommandLogger;
use OCP\ILogger;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Worker extends Command {

	/** @var \OCP\BackgroundJob\IJobList */
	private $jobList;
	/** @var ILogger */
	private $logger;

	public function __construct() {
		$this->jobList = \OC::$server->getJobList();
		parent::__construct();
	}

	protected function configure() {
		$this
			->setName("background:worker")
			->setDescription("Listen to the background job queue and execute the jobs")
			->addOption('sleep', null, InputOption::VALUE_OPTIONAL, 'Number of seconds to sleep when no job is available', 3);
	}

	/**
	* @param InputInterface $input
	* @param OutputInterface $output
	*/
	protected function execute(InputInterface $input, OutputInterface $output) {
		$this->logger = new CommandLogger($output);

		$waitTime = $input->getOption('sleep');
		while (true) {
			if (is_null($this->executeNext())) {
				sleep($waitTime);
			}
		}
	}

	private function executeNext() {
		$job = $this->jobList->getNext();
		if (is_null($job)) {
			return null;
		}
		$jobId = $job->getId();
		$this->logger->debug('Run job with ID ' . $job->getId(), ['app' => 'cron']);
		$job->execute($this->jobList, $this->logger);
		$this->logger->debug('Finished job with ID ' . $job->getId(), ['app' => 'cron']);

		$this->jobList->setLastJob($job);
		unset($job);

		return $jobId;
	}
}
