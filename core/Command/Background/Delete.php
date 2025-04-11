<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Core\Command\Background;

use OC\Core\Command\Base;
use OCP\BackgroundJob\IJobList;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

class Delete extends Base {
	public function __construct(
		protected IJobList $jobList,
	) {
		parent::__construct();
	}

	protected function configure(): void {
		$this
			->setName('background-job:delete')
			->setDescription('Remove a background job from database')
			->addArgument(
				'job-id',
				InputArgument::REQUIRED,
				'The ID of the job in the database'
			);
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$jobId = (int)$input->getArgument('job-id');

		$job = $this->jobList->getById($jobId);
		if ($job === null) {
			$output->writeln('<error>Job with ID ' . $jobId . ' could not be found in the database</error>');
			return 1;
		}

		$output->writeln('Job class: ' . get_class($job));
		$output->writeln('Arguments: ' . json_encode($job->getArgument()));
		$output->writeln('');

		$question = new ConfirmationQuestion(
			'<comment>Do you really want to delete this background job ? It could create some misbehaviours in Nextcloud.</comment> (y/N) ', false,
			'/^(y|Y)/i'
		);

		/** @var QuestionHelper $helper */
		$helper = $this->getHelper('question');
		if (!$helper->ask($input, $output, $question)) {
			$output->writeln('aborted.');
			return 0;
		}

		$this->jobList->remove($job, $job->getArgument());
		return 0;
	}
}
