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
		$this
			->setName('admin-delegation:show')
			->setDescription('show delegated settings')
		;
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$io = new SymfonyStyle($input, $output);

		// Collect delegation data
		$delegationData = $this->collectDelegationData();

		// Handle empty results
		if (empty($delegationData)) {
			$io->info('No delegated settings found.');
			return 0;
		}

		$this->outputPlainFormat($io, $delegationData);

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
	 * @param IDelegatedSettings[] $settings
	 * @param array $innerSection
	 */
	private function getDelegatedSettings(array $settings, array $innerSection): array {
		return $settings + array_filter($innerSection, fn (ISettings $setting) => $setting instanceof IDelegatedSettings);
	}
}
