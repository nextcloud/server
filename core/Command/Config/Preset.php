<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Core\Command\Config;

use OC\Config\PresetManager;
use OC\Core\Command\Base;
use OCP\Config\Lexicon\Preset as ConfigLexiconPreset;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Preset extends Base {
	public function __construct(
		private readonly PresetManager $presetManager,
	) {
		parent::__construct();
	}

	protected function configure() {
		parent::configure();
		$this->setName('config:preset')
			->setDescription('Select a config preset')
			->addArgument('preset', InputArgument::OPTIONAL, 'Preset to use for all unset config values', '')
			->addOption('list', '', InputOption::VALUE_NONE, 'display available preset')
			->addOption('apps', '', InputOption::VALUE_NONE, 'return list of enabled/disabled apps when switching preset')
			->addOption('compare', '', InputOption::VALUE_NONE, 'compare preset');
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		if ($input->getOption('list')) {
			$this->getEnum('', $list);
			$this->writeArrayInOutputFormat($input, $output, $list);
			return self::SUCCESS;
		}

		if ($input->getOption('apps')) {
			$this->writeArrayInOutputFormat($input, $output, $this->presetManager->retrieveLexiconPresetApps());
			return self::SUCCESS;
		}

		if ($input->getOption('compare')) {
			$list = $this->presetManager->retrieveLexiconPreset();
			if ($input->getOption('output') === 'plain') {
				$table = new Table($output);
				$table->setHeaders(['app', 'config', 'config key', 'value', ...array_map(static fn (ConfigLexiconPreset $p): string => $p->name, ConfigLexiconPreset::cases())]);
				foreach ($list as $appId => $entries) {
					foreach ($entries as $item) {
						$table->addRow([$appId, $item['config'], $item['entry']['key'], '<comment>' . ($item['value'] ?? '') . '</comment>', ...($item['defaults'] ?? [])]);
					}
				}
				$table->render();
				return self::SUCCESS;
			}

			$this->writeArrayInOutputFormat($input, $output, $list);
			return self::SUCCESS;
		}

		$presetArg = $input->getArgument('preset');
		if ($presetArg !== '') {
			$preset = $this->getEnum($presetArg, $list);
			if ($preset === null) {
				$output->writeln('<error>Invalid preset: ' . $presetArg . '</error>');
				$output->writeln('Available presets: ' . implode(', ', $list));
				return self::INVALID;
			}

			$this->presetManager->setLexiconPreset($preset);
		}

		$current = $this->presetManager->getLexiconPreset();
		$this->writeArrayInOutputFormat($input, $output, [$current->name], 'current preset: ');
		return self::SUCCESS;
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
