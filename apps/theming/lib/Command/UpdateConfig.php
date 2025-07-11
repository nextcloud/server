<?php

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Theming\Command;

use OCA\Theming\ImageManager;
use OCA\Theming\ThemingDefaults;
use OCP\IConfig;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateConfig extends Command {
	public const SUPPORTED_KEYS = [
		'name', 'url', 'imprintUrl', 'privacyUrl', 'slogan', 'color', 'primary_color', 'background_color', 'disable-user-theming'
	];

	public function __construct(
		private ThemingDefaults $themingDefaults,
		private ImageManager $imageManager,
		private IConfig $config,
	) {
		parent::__construct();
	}

	protected function configure() {
		$this
			->setName('theming:config')
			->setDescription('Set theming app config values')
			->addArgument(
				'key',
				InputArgument::OPTIONAL,
				'Key to update the theming app configuration (leave empty to get a list of all configured values)' . PHP_EOL
				. 'One of: ' . implode(', ', self::SUPPORTED_KEYS)
			)
			->addArgument(
				'value',
				InputArgument::OPTIONAL,
				'Value to set (leave empty to obtain the current value)'
			)
			->addOption(
				'reset',
				'r',
				InputOption::VALUE_NONE,
				'Reset the given config key to default'
			);
	}


	protected function execute(InputInterface $input, OutputInterface $output): int {
		$key = $input->getArgument('key');
		$value = $input->getArgument('value');
		assert(is_string($value) || $value === null, 'At most one value should be provided.');

		if ($key === null) {
			$output->writeln('Current theming config:');
			foreach (self::SUPPORTED_KEYS as $key) {
				$value = $this->config->getAppValue('theming', $key, '');
				$output->writeln('- ' . $key . ': ' . $value . '');
			}
			foreach (ImageManager::SUPPORTED_IMAGE_KEYS as $key) {
				$value = $this->config->getAppValue('theming', $key . 'Mime', '');
				$output->writeln('- ' . $key . ': ' . $value . '');
			}
			return 0;
		}

		if (!in_array($key, self::SUPPORTED_KEYS, true) && !in_array($key, ImageManager::SUPPORTED_IMAGE_KEYS, true)) {
			$output->writeln('<error>Invalid config key provided</error>');
			return 1;
		}

		if ($input->getOption('reset')) {
			$defaultValue = $this->themingDefaults->undo($key);
			$output->writeln('<info>Reset ' . $key . ' to ' . $defaultValue . '</info>');
			return 0;
		}

		if ($value === null) {
			$value = $this->config->getAppValue('theming', $key, '');
			if ($value !== '') {
				$output->writeln('<info>' . $key . ' is currently set to ' . $value . '</info>');
			} else {
				$output->writeln('<info>' . $key . ' is currently not set</info>');
			}
			return 0;
		}

		if ($key === 'background' && $value === 'backgroundColor') {
			$this->themingDefaults->undo($key);
			$key = $key . 'Mime';
		}

		if (in_array($key, ImageManager::SUPPORTED_IMAGE_KEYS, true)) {
			if (!str_starts_with($value, '/')) {
				$output->writeln('<error>The image file needs to be provided as an absolute path: ' . $value . '.</error>');
				return 1;
			}
			if (!file_exists($value)) {
				$output->writeln('<error>File could not be found: ' . $value . '.</error>');
				return 1;
			}
			$value = $this->imageManager->updateImage($key, $value);
			$key = $key . 'Mime';
		}

		if ($key === 'color') {
			$output->writeln('<comment>Using "color" is deprecated, use "primary_color" instead</comment>');
			$key = 'primary_color';
		}

		if ($key === 'primary_color' && !preg_match('/^\#([0-9a-f]{3}|[0-9a-f]{6})$/i', $value)) {
			$output->writeln('<error>The given color is invalid: ' . $value . '</error>');
			return 1;
		}

		$this->themingDefaults->set($key, $value);
		$output->writeln('<info>Updated ' . $key . ' to ' . $value . '</info>');

		return 0;
	}
}
