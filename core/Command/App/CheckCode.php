<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Joas Schilling <coding@schilljs.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin McCorkell <robin@mccorkell.me.uk>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 *
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

namespace OC\Core\Command\App;

use OC\App\CodeChecker\CodeChecker;
use OC\App\CodeChecker\EmptyCheck;
use OC\App\CodeChecker\InfoChecker;
use OC\App\InfoParser;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CheckCode extends Command {

	/** @var InfoParser */
	private $infoParser;

	protected $checkers = [
		'private' => '\OC\App\CodeChecker\PrivateCheck',
		'deprecation' => '\OC\App\CodeChecker\DeprecationCheck',
		'strong-comparison' => '\OC\App\CodeChecker\StrongComparisonCheck',
	];

	public function __construct(InfoParser $infoParser) {
		parent::__construct();
		$this->infoParser = $infoParser;
	}

	protected function configure() {
		$this
			->setName('app:check-code')
			->setDescription('check code to be compliant')
			->addArgument(
				'app-id',
				InputArgument::REQUIRED,
				'check the specified app'
			)
			->addOption(
				'checker',
				'c',
				InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
				'enable the specified checker(s)',
				[ 'private', 'deprecation', 'strong-comparison' ]
			)
			->addOption(
				'--skip-validate-info',
				null,
				InputOption::VALUE_NONE,
				'skips the info.xml/version check'
			);
	}

	protected function execute(InputInterface $input, OutputInterface $output) {
		$appId = $input->getArgument('app-id');

		$checkList = new EmptyCheck();
		foreach ($input->getOption('checker') as $checker) {
			if (!isset($this->checkers[$checker])) {
				throw new \InvalidArgumentException('Invalid checker: '.$checker);
			}
			$checkerClass = $this->checkers[$checker];
			$checkList = new $checkerClass($checkList);
		}

		$codeChecker = new CodeChecker($checkList);

		$codeChecker->listen('CodeChecker', 'analyseFileBegin', function($params) use ($output) {
			if(OutputInterface::VERBOSITY_VERBOSE <= $output->getVerbosity()) {
				$output->writeln("<info>Analysing {$params}</info>");
			}
		});
		$codeChecker->listen('CodeChecker', 'analyseFileFinished', function($filename, $errors) use ($output) {
			$count = count($errors);

			// show filename if the verbosity is low, but there are errors in a file
			if($count > 0 && OutputInterface::VERBOSITY_VERBOSE > $output->getVerbosity()) {
				$output->writeln("<info>Analysing {$filename}</info>");
			}

			// show error count if there are errors present or the verbosity is high
			if($count > 0 || OutputInterface::VERBOSITY_VERBOSE <= $output->getVerbosity()) {
				$output->writeln(" {$count} errors");
			}
			usort($errors, function($a, $b) {
				return $a['line'] >$b['line'];
			});

			foreach($errors as $p) {
				$line = sprintf("%' 4d", $p['line']);
				$output->writeln("    <error>line $line: {$p['disallowedToken']} - {$p['reason']}</error>");
			}
		});
		$errors = $codeChecker->analyse($appId);

		if(!$input->getOption('skip-validate-info')) {
			$infoChecker = new InfoChecker($this->infoParser);

			$infoChecker->listen('InfoChecker', 'mandatoryFieldMissing', function($key) use ($output) {
				$output->writeln("<error>Mandatory field missing: $key</error>");
			});

			$infoChecker->listen('InfoChecker', 'deprecatedFieldFound', function($key, $value) use ($output) {
				if($value === [] || is_null($value) || $value === '') {
					$output->writeln("<info>Deprecated field available: $key</info>");
				} else {
					$output->writeln("<info>Deprecated field available: $key => $value</info>");
				}
			});

			$infoChecker->listen('InfoChecker', 'missingRequirement', function($minMax) use ($output) {
				$output->writeln("<comment>ownCloud $minMax version requirement missing (will be an error in ownCloud 11 and later)</comment>");
			});

			$infoChecker->listen('InfoChecker', 'duplicateRequirement', function($minMax) use ($output) {
				$output->writeln("<error>Duplicate $minMax ownCloud version requirement found</error>");
			});

			$infoChecker->listen('InfoChecker', 'differentVersions', function($versionFile, $infoXML) use ($output) {
				$output->writeln("<error>Different versions provided (appinfo/version: $versionFile - appinfo/info.xml: $infoXML)</error>");
			});

			$infoChecker->listen('InfoChecker', 'sameVersions', function($path) use ($output) {
				$output->writeln("<info>Version file isn't needed anymore and can be safely removed ($path)</info>");
			});

			$infoChecker->listen('InfoChecker', 'migrateVersion', function($version) use ($output) {
				$output->writeln("<info>Migrate the app version to appinfo/info.xml (add <version>$version</version> to appinfo/info.xml and remove appinfo/version)</info>");
			});

			if(OutputInterface::VERBOSITY_VERBOSE <= $output->getVerbosity()) {
				$infoChecker->listen('InfoChecker', 'mandatoryFieldFound', function($key, $value) use ($output) {
					$output->writeln("<info>Mandatory field available: $key => $value</info>");
				});

				$infoChecker->listen('InfoChecker', 'optionalFieldFound', function($key, $value) use ($output) {
					$output->writeln("<info>Optional field available: $key => $value</info>");
				});

				$infoChecker->listen('InfoChecker', 'unusedFieldFound', function($key, $value) use ($output) {
					$output->writeln("<info>Unused field available: $key => $value</info>");
				});
			}

			$infoErrors = $infoChecker->analyse($appId);

			$errors = array_merge($errors, $infoErrors);
		}

		$this->analyseUpdateFile($appId, $output);

		if (empty($errors)) {
			$output->writeln('<info>App is compliant - awesome job!</info>');
			return 0;
		} else {
			$output->writeln('<error>App is not compliant</error>');
			return 101;
		}
	}

	/**
	 * @param string $appId
	 * @param $output
	 */
	private function analyseUpdateFile($appId, OutputInterface $output) {
		$appPath = \OC_App::getAppPath($appId);
		if ($appPath === false) {
			throw new \RuntimeException("No app with given id <$appId> known.");
		}

		$updatePhp = $appPath . '/appinfo/update.php';
		if (file_exists($updatePhp)) {
			$output->writeln("<info>Deprecated file found: $updatePhp - please use repair steps</info>");
		}
	}
}
