<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Teams;

use OCP\Teams\TeamAppNavigation;
use OCP\Teams\TeamAppNavigationAction;
use OCP\Teams\TeamAppNavigationEntry;
use PHPUnit\Framework\TestCase;

class TeamAppDataTest extends TestCase {
	public function testStoresNestedNavigationData(): void {
		$icon = 'M3 3H21V21H3Z';
		$prompt = 'Board name';
		$action = new TeamAppNavigationAction('create', 'Create', $icon, $prompt);
		$entry = new TeamAppNavigationEntry('42', 'Board', $icon, '/board/42', 'https://cloud.example/f/42', 'link');
		$navigation = new TeamAppNavigation([$entry], $action);

		$this->assertSame([$entry], $navigation->entries);
		$this->assertSame($action, $navigation->createAction);
		$this->assertSame('42', $entry->id);
		$this->assertSame('Board', $entry->label);
		$this->assertSame($icon, $entry->icon);
		$this->assertSame('/board/42', $entry->href);
		$this->assertSame('https://cloud.example/f/42', $entry->referenceUrl);
		$this->assertSame('link', $entry->openMode);
		$this->assertSame('create', $action->id);
		$this->assertSame('Create', $action->label);
		$this->assertSame($icon, $action->icon);
		$this->assertSame($prompt, $action->prompt);
	}

	public function testStoresActionPromptLabel(): void {
		$action = new TeamAppNavigationAction('create', 'Create', prompt: 'Name');

		$this->assertSame('Name', $action->prompt);
	}

	public function testPreservesPathAndEmojiIcons(): void {
		foreach (['M3 3H21V21H3Z', "\u{1F4DA}", "\u{1F469}\u{200D}\u{1F4BB}"] as $icon) {
			$this->assertSame($icon, (new TeamAppNavigationEntry('42', 'Example', icon: $icon))->icon);
			$this->assertSame($icon, (new TeamAppNavigationAction('create', 'Example', icon: $icon))->icon);
		}
	}

	public function testStoresAbsentAndExplicitEmptyValues(): void {
		$navigation = new TeamAppNavigation([]);
		$entry = new TeamAppNavigationEntry('42', 'Board');
		$action = new TeamAppNavigationAction('create', 'Create');

		$this->assertSame([], $navigation->entries);
		$this->assertNull($navigation->createAction);
		$this->assertNull($entry->openMode);
		$this->assertNull($action->icon);
		$this->assertNull($action->prompt);
	}

}
