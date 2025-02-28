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
use Symfony\Component\Console\Input\InputOption;
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
			->addOption(
				'output',
				null,
				InputOption::VALUE_OPTIONAL,
				'Output format (plain, json or json_pretty, default is plain)',
				$this->defaultOutputFormat
			)
		;
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$sections = $this->settingManager->getAdminSections();

		$settings = [];
		switch ($input->getOption('output')) {
			case self::OUTPUT_FORMAT_JSON:
				$output->writeln(json_encode($this->buildJsonOutput($sections)));
				break;
			case self::OUTPUT_FORMAT_JSON_PRETTY:
				$output->writeln(json_encode($this->buildJsonOutput($sections), JSON_PRETTY_PRINT));
				break;
			default:
				$io = new SymfonyStyle($input, $output);
				$io->title('Current delegations');

				$headers = ['Name', 'SettingId', 'Delegated to groups'];
				foreach ($sections as $sectionPriority) {
					foreach ($sectionPriority as $section) {
						$sectionSettings = $this->settingManager->getAdminSettings($section->getId());
						$sectionSettings = array_reduce($sectionSettings, [$this, 'getDelegatedSettings'], []);
						if (empty($sectionSettings)) {
							continue;
						}

						$io->section('Section: ' . $section->getID());
						$io->table($headers, array_map(function (IDelegatedSettings $setting) use ($section) {
							$className = get_class($setting);
							$groups = array_map(
								static fn (AuthorizedGroup $group) => $group->getGroupId(),
								$this->authorizedGroupService->findExistingGroupsForClass($className)
							);
							natsort($groups);
							return [
								$setting->getName() ?: 'Global',
								$className,
								implode(', ', $groups),
							];
						}, $sectionSettings));
					}
				}
		}

		return 0;
	}

	/**
	 * @param IDelegatedSettings[] $settings
	 * @param array $innerSection
	 */
	private function getDelegatedSettings(array $settings, array $innerSection): array {
		return $settings + array_filter($innerSection, fn (ISettings $setting) => $setting instanceof IDelegatedSettings);
	}

	private function buildJsonOutput(array $sections): array {
		$currentDelegations = [
			'currentDelegations' => []
		];

		foreach ($sections as $sectionPriority) {
			foreach ($sectionPriority as $section) {
				$sectionSettings = $this->settingManager->getAdminSettings($section->getId());
				$sectionSettings = array_reduce($sectionSettings, [$this, 'getDelegatedSettings'], []);
				if (empty($sectionSettings)) {
					continue;
				}

				$currentDelegations['currentDelegations'][] = [
					'section' => $section->getID(),
					'delegations' =>
						array_map(function (IDelegatedSettings $setting) use ($section, $headers) {
							$className = get_class($setting);
							$groups = array_map(
								static fn (AuthorizedGroup $group) => $group->getGroupId(),
								$this->authorizedGroupService->findExistingGroupsForClass($className)
							);
							natsort($groups);
							return [
								"name" => $setting->getName() ?: 'Global',
								"settingId" => $className,
								"delegatedToGroups" => implode(', ', $groups)
							];
						}, $sectionSettings)
				];
			}
		}

		return $currentDelegations;
	}
}
