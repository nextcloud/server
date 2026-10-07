<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Mail;

use OCP\Mail\EMailDetails;
use OCP\Mail\EMailDetailsRow;
use Test\TestCase;

class EMailDetailsTest extends TestCase {
	public function testDefaults(): void {
		$details = new EMailDetails('Title');

		$this->assertSame('Title', $details->getTitle());
		$this->assertSame('', $details->getSubtitle());
		$this->assertNull($details->getInitialsName());
		$this->assertNull($details->getDateBadge());
		$this->assertSame([], $details->getRows());
	}

	public function testLeadingVisualReplacesTheOther(): void {
		$details = (new EMailDetails('Title'))
			->setInitials('Board prep')
			->setDateBadge('Oct', '15');

		$this->assertNull($details->getInitialsName());
		$this->assertSame(['month' => 'Oct', 'day' => '15'], $details->getDateBadge());

		$details->setInitials('Board prep');

		$this->assertSame('Board prep', $details->getInitialsName());
		$this->assertNull($details->getDateBadge());
	}

	public function testRowsKeepOrder(): void {
		$details = new EMailDetails('Title');
		$details->addRow('Where')
			->text('Room 2')
			->link('Join', 'https://example.org')
			->muted('Optional');
		$details->addRow('When')->text('Today');

		$rows = $details->getRows();
		$this->assertCount(2, $rows);
		$this->assertSame('Where', $rows[0]->getLabel());
		$this->assertSame([
			['type' => EMailDetailsRow::PART_TEXT, 'text' => 'Room 2', 'url' => ''],
			['type' => EMailDetailsRow::PART_LINK, 'text' => 'Join', 'url' => 'https://example.org'],
			['type' => EMailDetailsRow::PART_MUTED, 'text' => 'Optional', 'url' => ''],
		], $rows[0]->getParts());
		$this->assertSame('When', $rows[1]->getLabel());
	}
}
