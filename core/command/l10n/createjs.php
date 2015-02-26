<?php
/**
 * Copyright (c) 2013 Robin Appelman <icewind@owncloud.com> and
 * Copyright (c) 2014 Stephen Colebrook <scolebrook@mac.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OC\Core\Command\L10n;

use DirectoryIterator;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CreateJs extends Command {

	protected function configure() {
		$this
			->setName('l10n:createjs')
			->setDescription('Create javascript translation files for a given app')
			->addArgument(
				'app',
				InputOption::VALUE_REQUIRED,
				'name of the app'
			)
			->addArgument(
				'lang',
				InputOption::VALUE_OPTIONAL,
				'name of the language'
			);
	}

	protected function execute(InputInterface $input, OutputInterface $output) {
		$app = $input->getArgument('app');
		$lang = $input->getArgument('lang');

		$path = \OC_App::getAppPath($app);
		if ($path === false) {
			$output->writeln("The app <$app> is unknown.");
			return;
		}
		$languages = $lang;
		if (empty($lang)) {
			$languages= $this->getAllLanguages($path);
		}

		foreach($languages as $lang) {
			$this->writeFiles($app, $path, $lang, $output);
		}
	}

	private function getAllLanguages($path) {
		$result = array();
		foreach (new DirectoryIterator("$path/l10n") as $fileInfo) {
			if($fileInfo->isDot()) {
				continue;
			}
			if($fileInfo->isDir()) {
				continue;
			}
			if($fileInfo->getExtension() !== 'php') {
				continue;
			}
			$result[]= substr($fileInfo->getBasename(), 0, -4);
		}

		return $result;
	}

	private function writeFiles($app, $path, $lang, OutputInterface $output) {
		list($translations, $plurals) = $this->loadTranslations($path, $lang);
		$this->writeJsFile($app, $path, $lang, $output, $translations, $plurals);
		$this->writeJsonFile($path, $lang, $output, $translations, $plurals);
	}

	private function writeJsFile($app, $path, $lang, OutputInterface $output, $translations, $plurals) {
		$jsFile = "$path/l10n/$lang.js";
		if (file_exists($jsFile)) {
			$output->writeln("File already exists: $jsFile");
			return;
		}
		$content = "OC.L10N.register(\n    \"$app\",\n    {\n    ";
		$jsTrans = array();
		foreach ($translations as $id => $val) {
			if (is_array($val)) {
				$val = '[ ' . join(',', $val) . ']';
			}
			$jsTrans[] = "\"$id\" : \"$val\"";
		}
		$content .= join(",\n    ", $jsTrans);
		$content .= "\n},\n\"$plurals\");\n";

		file_put_contents($jsFile, $content);
		$output->writeln("Javascript translation file generated: $jsFile");
	}

	private function writeJsonFile($path, $lang, OutputInterface $output, $translations, $plurals) {
		$jsFile = "$path/l10n/$lang.json";
		if (file_exists($jsFile)) {
			$output->writeln("File already exists: $jsFile");
			return;
		}
		$content = array('translations' => $translations, 'pluralForm' => $plurals);
		file_put_contents($jsFile, json_encode($content));
		$output->writeln("Json translation file generated: $jsFile");
	}

	private function loadTranslations($path, $lang) {
		$phpFile = "$path/l10n/$lang.php";
		$TRANSLATIONS = array();
		$PLURAL_FORMS = '';
		require $phpFile;

		return array($TRANSLATIONS, $PLURAL_FORMS);
	}
}
