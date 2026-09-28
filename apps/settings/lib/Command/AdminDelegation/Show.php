<?php

declare(strict_types = 1);
/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Settings\Command\AdminDelegation;

use OC\Core\Command\Base;
use OC\Settings\AuthorizedGroup;
use OCA\Settings\Service\AuthorizedGroupService;
use OCP\Settings\IDelegatedSettings;
use OCP\Settings\IManager;
use OCP\Settings\ISettings;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class Show extends Base {
	public function __construct(
		private IManager $settingManager,
		private AuthorizedGroupService $authorizedGroupService,
	) {
		parent::__construct();
	}

	protected function configure(): void {
		parent::configure();
		$this
			->setName('admin-delegation:show')
			->setDescription('show delegated settings')
		;
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$io = new SymfonyStyle($input, $output);
		$outputFormat = $input->getOption('output');

		// Validate output format
		if (!$this->validateOutputFormat($outputFormat)) {
			$io->error("Invalid output format: {$outputFormat}. Valid formats are: plain, json, json_pretty");
			return 1;
		}

		// Collect delegation data
		$delegationData = $this->collectDelegationData();

		// Handle empty results
		if (empty($delegationData)) {
			if ($outputFormat === self::OUTPUT_FORMAT_PLAIN) {
				$io->info('No delegated settings found.');
			} else {
				$this->writeArrayInOutputFormat($input, $io, []);
			}
			return 0;
		}

		// Output based on format
		switch ($outputFormat) {
			case self::OUTPUT_FORMAT_JSON:
			case self::OUTPUT_FORMAT_JSON_PRETTY:
				$this->writeArrayInOutputFormat($input, $io, $delegationData);
				break;
			default:
				$this->outputPlainFormat($io, $delegationData);
				break;
		}

		return 0;
	}

	/**
	 * Collect all delegation data in a structured format
	 */
	private function collectDelegationData(): array {
		$result = [];
		$sections = $this->settingManager->getAdminSections();

		foreach ($sections as $sectionPriority) {
			foreach ($sectionPriority as $section) {
				$sectionSettings = $this->settingManager->getAdminSettings($section->getId());
				$delegatedSettings = array_reduce($sectionSettings, [$this, 'getDelegatedSettings'], []);

				if (empty($delegatedSettings)) {
					continue;
				}

				$result[] = [
					'id' => $section->getID(),
					'name' => $section->getName() ?: $section->getID(),
					'settings' => $this->formatSettingsData($delegatedSettings)
				];
			}
		}

		return $result;
	}

	/**
	 * Format settings data for consistent output
	 */
	private function formatSettingsData(array $settings): array {
		return array_map(function (IDelegatedSettings $setting) {
			$className = get_class($setting);
			$groups = array_map(
				static fn (AuthorizedGroup $group) => $group->getGroupId(),
				$this->authorizedGroupService->findExistingGroupsForClass($className)
			);
			natsort($groups);

			return [
				'name' => $setting->getName() ?: 'Global',
				'className' => $className,
				'priority' => $setting->getPriority(),
				'delegatedGroups' => $groups,
			];
		}, $settings);
	}

	/**
	 * Output data in plain table format
	 */
	private function outputPlainFormat(SymfonyStyle $io, array $data): void {
		$io->title('Current delegations');
		$headers = ['Name', 'SettingId', 'Delegated to groups'];

		foreach ($data as $section) {
			$io->section('Section: ' . $section['id']);

			$tableData = array_map(static function (array $setting) {
				return [
					$setting['name'],
					$setting['className'],
					implode(', ', $setting['delegatedGroups']),
				];
			}, $section['settings']);

			$io->table($headers, $tableData);
		}
	}

	/**
	 * Validate the output format parameter
	 */
	private function validateOutputFormat(string $format): bool {
		return in_array($format, [
			self::OUTPUT_FORMAT_PLAIN,
			self::OUTPUT_FORMAT_JSON,
			self::OUTPUT_FORMAT_JSON_PRETTY
		], true);
	}

	/**
	 * @param IDelegatedSettings[] $settings
	 * @param array $innerSection
	 */
	private function getDelegatedSettings(array $settings, array $innerSection): array {
		return array_merge($settings, array_filter($innerSection, fn (ISettings $setting) => $setting instanceof IDelegatedSettings));
	}
}
