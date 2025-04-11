<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\Tests\integration\UserMigration;

use OCA\DAV\AppInfo\Application;
use OCA\DAV\UserMigration\CalendarMigrator;
use OCP\AppFramework\App;
use OCP\IUserManager;
use Sabre\VObject\Component as VObjectComponent;
use Sabre\VObject\Component\VCalendar;
use Sabre\VObject\Property as VObjectProperty;
use Sabre\VObject\Reader as VObjectReader;
use Sabre\VObject\UUIDUtil;
use Symfony\Component\Console\Output\OutputInterface;
use Test\TestCase;
use function scandir;

/**
 * @group DB
 */
class CalendarMigratorTest extends TestCase {

	private IUserManager $userManager;

	private CalendarMigrator $migrator;

	private OutputInterface $output;

	private const ASSETS_DIR = __DIR__ . '/assets/calendars/';

	protected function setUp(): void {
		$app = new App(Application::APP_ID);
		$container = $app->getContainer();

		$this->userManager = $container->get(IUserManager::class);
		$this->migrator = $container->get(CalendarMigrator::class);
		$this->output = $this->createMock(OutputInterface::class);
	}

	public function dataAssets(): array {
		return array_map(
			function (string $filename) {
				/** @var VCalendar $vCalendar */
				$vCalendar = VObjectReader::read(
					fopen(self::ASSETS_DIR . $filename, 'r'),
					VObjectReader::OPTION_FORGIVING,
				);
				[$initialCalendarUri, $ext] = explode('.', $filename, 2);
				return [UUIDUtil::getUUID(), $filename, $initialCalendarUri, $vCalendar];
			},
			array_diff(
				scandir(self::ASSETS_DIR),
				// Exclude current and parent directories
				['.', '..'],
			),
		);
	}

	private function getProperties(VCalendar $vCalendar): array {
		return array_map(
			fn (VObjectProperty $property) => $property->serialize(),
			array_values(array_filter(
				$vCalendar->children(),
				fn ($child) => $child instanceof VObjectProperty,
			)),
		);
	}

	private function getComponents(VCalendar $vCalendar): array {
		return array_map(
			// Elements of the serialized blob are sorted
			fn (VObjectComponent $component) => $component->serialize(),
			$vCalendar->getComponents(),
		);
	}

	private function getSanitizedComponents(VCalendar $vCalendar): array {
		return array_map(
			// Elements of the serialized blob are sorted
			fn (VObjectComponent $component) => $this->invokePrivate($this->migrator, 'sanitizeComponent', [$component])->serialize(),
			$vCalendar->getComponents(),
		);
	}

	/**
	 * @dataProvider dataAssets
	 */
	public function testImportExportAsset(string $userId, string $filename, string $initialCalendarUri, VCalendar $importCalendar): void {
		$user = $this->userManager->createUser($userId, 'topsecretpassword');

		$problems = $importCalendar->validate();
		$this->assertEmpty($problems);

		$this->invokePrivate($this->migrator, 'importCalendar', [$user, $filename, $initialCalendarUri, $importCalendar, $this->output]);

		$calendarExports = $this->invokePrivate($this->migrator, 'getCalendarExports', [$user, $this->output]);
		$this->assertCount(1, $calendarExports);

		/** @var VCalendar $exportCalendar */
		['vCalendar' => $exportCalendar] = reset($calendarExports);

		$this->assertEqualsCanonicalizing(
			$this->getProperties($importCalendar),
			$this->getProperties($exportCalendar),
		);

		$this->assertEqualsCanonicalizing(
			// Components are expected to be sanitized on import
			$this->getSanitizedComponents($importCalendar),
			$this->getComponents($exportCalendar),
		);
	}
}
