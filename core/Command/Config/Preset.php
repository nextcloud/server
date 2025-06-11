<?php

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Core\Command\Config;

use NCU\Config\Lexicon\ConfigLexiconPreset;
use OC\Config\ConfigManager;
use OCP\IConfig;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Preset extends Command {
	public function __construct(
		private readonly IConfig $config,
		private readonly ConfigManager $configManager,
	) {
		parent::__construct();
	}

	protected function configure() {
		$this
			->setName('config:preset')
			->setDescription('Select a config preset')
			->addArgument('preset', InputArgument::OPTIONAL, 'selected preset', '')
			->addOption('list', '', InputOption::VALUE_NONE, 'display available preset');
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		if ($input->getOption('list')) {
			$this->getEnum('', $list);
			$output->writeln('list of available preset: <info>' . implode(', ', $list) . '</info>');
			return 0;
		}

		$presetArg = $input->getArgument('preset');
		if ($presetArg !== '') {
			$preset = $this->getEnum($input->getArgument('preset'), $list);
			if ($preset === null) {
				throw new \Exception('invalid preset. please choose one from the list: ' . implode(', ', $list));
			}

			$this->configManager->setLexiconPreset($preset);
		}

		$current = ConfigLexiconPreset::tryFrom($this->config->getSystemValueInt(ConfigManager::PRESET_CONFIGKEY, 0)) ?? ConfigLexiconPreset::NONE;
		$output->writeln('current preset: ' . $current->name);
		return 0;
	}

	private function getEnum(string $name, ?array &$list = null): ?ConfigLexiconPreset {
		$list = [];
		foreach (ConfigLexiconPreset::cases() as $case) {
			$list[] = $case->name;
			if (strtolower($case->name) === strtolower($name)) {
				return $case;
			}
		}

		return null;
	}
}
