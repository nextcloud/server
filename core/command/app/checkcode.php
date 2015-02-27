<?php
/**
 * Copyright (c) 2015 Thomas Müller <deepdiver@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OC\Core\Command\App;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CheckCode extends Command {
	protected function configure() {
		$this
			->setName('app:check-code')
			->setDescription('check code to be compliant')
			->addArgument(
				'app-id',
				InputArgument::REQUIRED,
				'enable the specified app'
			);
	}

	protected function execute(InputInterface $input, OutputInterface $output) {
		$appId = $input->getArgument('app-id');
		$codeChecker = new \OC\App\CodeChecker();
		$codeChecker->listen('CodeChecker', 'analyseFileBegin', function($params) use ($output) {
			$output->writeln("<info>Analysing {$params}</info>");
		});
		$codeChecker->listen('CodeChecker', 'analyseFileFinished', function($params) use ($output) {
			$count = count($params);
			$output->writeln(" {$count} errors");
			usort($params, function($a, $b) {
				return $a['line'] >$b['line'];
			});

			foreach($params as $p) {
				$line = sprintf("%' 4d", $p['line']);
				$output->writeln("    <error>line $line: {$p['disallowedToken']} - {$p['reason']}</error>");
			}
		});
		$errors = $codeChecker->analyse($appId);
		if (empty($errors)) {
			$output->writeln('<info>App is compliant - awesome job!</info>');
		} else {
			$output->writeln('<error>App is not compliant</error>');
		}
	}
}
