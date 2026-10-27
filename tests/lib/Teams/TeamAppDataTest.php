<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Teams;

use OCP\Teams\TeamAppAction;
use OCP\Teams\TeamAppActionConfirmation;
use OCP\Teams\TeamAppActionPrompt;
use OCP\Teams\TeamAppActionResult;
use OCP\Teams\TeamAppEntry;
use OCP\Teams\TeamAppNavigation;
use PHPUnit\Framework\TestCase;

class TeamAppDataTest extends TestCase {
	public function testStoresNestedNavigationData(): void {
		$icon = 'M3 3H21V21H3Z';
		$prompt = new TeamAppActionPrompt('Board name', 'Sketch');
		$action = new TeamAppAction('create', 'Create', $icon, $prompt);
		$entry = new TeamAppEntry('42', 'Board', $icon, '/board/42', 'https://cloud.example/f/42', 'link');
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
		$this->assertSame($prompt, $action->dialog);
	}

	public function testStoresActionDialogData(): void {
		$prompt = new TeamAppActionPrompt('Name', 'Guide');
		$confirmation = new TeamAppActionConfirmation('Delete', 'Delete this item?');

		$this->assertSame('Name', $prompt->title);
		$this->assertSame('Guide', $prompt->value);
		$this->assertSame('Delete', $confirmation->title);
		$this->assertSame('Delete this item?', $confirmation->message);
		$this->assertSame($prompt, (new TeamAppAction('prompt', 'Prompt', dialog: $prompt))->dialog);
		$this->assertSame($confirmation, (new TeamAppAction('confirm', 'Confirm', dialog: $confirmation))->dialog);
	}

	public function testPreservesPathAndEmojiIcons(): void {
		foreach (['M3 3H21V21H3Z', "\u{1F4DA}", "\u{1F469}\u{200D}\u{1F4BB}"] as $icon) {
			$this->assertSame($icon, (new TeamAppEntry('42', 'Example', icon: $icon))->icon);
			$this->assertSame($icon, (new TeamAppAction('create', 'Example', icon: $icon))->icon);
		}
	}

	public function testStoresAbsentAndExplicitEmptyValues(): void {
		$navigation = new TeamAppNavigation([]);
		$entry = new TeamAppEntry('42', 'Board');
		$action = new TeamAppAction('rename', 'Rename');
		$prompt = new TeamAppActionPrompt('Name');

		$this->assertSame([], $navigation->entries);
		$this->assertNull($navigation->createAction);
		$this->assertNull($entry->openMode);
		$this->assertNull($action->icon);
		$this->assertNull($action->dialog);
		$this->assertNull($prompt->value);
	}

	public function testStoresActionResultData(): void {
		$result = new TeamAppActionResult(['42'], ['99'], '42');
		$emptyResult = new TeamAppActionResult([], [], '');
		$unchangedResult = new TeamAppActionResult();

		$this->assertSame(['42'], $result->navigationEntryIdsToAdd);
		$this->assertSame(['99'], $result->navigationEntryIdsToRemove);
		$this->assertSame('42', $result->open);
		$this->assertSame([], $emptyResult->navigationEntryIdsToAdd);
		$this->assertSame([], $emptyResult->navigationEntryIdsToRemove);
		$this->assertSame('', $emptyResult->open);
		$this->assertNull($unchangedResult->navigationEntryIdsToAdd);
		$this->assertNull($unchangedResult->navigationEntryIdsToRemove);
		$this->assertNull($unchangedResult->open);
	}
}
