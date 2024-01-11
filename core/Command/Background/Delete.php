<?php

declare(strict_types=1);
/**
 * @copyright Copyright (c) 2024, Maxence Lange <maxence@artificial-owl.com>
 *
 * @author Maxence Lange <maxence@artificial-owl.com>
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

namespace OC\Core\Command\Background;

use OC\Core\Command\Base;
use OCP\BackgroundJob\IJobList;
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
		$jobId = (int) $input->getArgument('job-id');

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

		$helper = $this->getHelper('question');
		if (!$helper->ask($input, $output, $question)) {
			$output->writeln('aborted.');
			return 0;
		}

		$this->jobList->remove($job, $job->getArgument());
		return 0;
	}
}
