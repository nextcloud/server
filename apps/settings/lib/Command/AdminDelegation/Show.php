<?php

declare(strict_types = 1);
/**
 * @copyright Copyright (c) 2023 Benjamin Gaussorgues <benjamin.gaussorgues@nextcloud.com>
 *
 * @author Benjamin Gaussorgues <benjamin.gaussorgues@nextcloud.com>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
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
		$io->title('Current delegations');

		$sections = $this->settingManager->getAdminSections();
		$settings = [];
		$headers = ['Name', 'SettingId', 'Delegated to groups'];
		foreach ($sections as $sectionPriority) {
			foreach ($sectionPriority as $section) {
				$sectionSettings = $this->settingManager->getAdminSettings($section->getId());
				$sectionSettings = array_reduce($sectionSettings, [$this, 'getDelegatedSettings'], []);
				if (empty($sectionSettings)) {
					continue;
				}

				$io->section('Section: '.$section->getID());
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

		return 0;
	}

	/**
	 * @param IDelegatedSettings[] $settings
	 * @param array $innerSection
	 */
	private function getDelegatedSettings(array $settings, array $innerSection): array {
		return $settings + array_filter($innerSection, fn (ISettings $setting) => $setting instanceof IDelegatedSettings);
	}
}
