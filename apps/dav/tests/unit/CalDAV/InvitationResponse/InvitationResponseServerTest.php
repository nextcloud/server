<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\Tests\unit\CalDAV\InvitationResponse;

use OCA\DAV\CalDAV\InvitationResponse\InvitationResponseServer;
use OCA\DAV\CalDAV\Schedule\IMipPlugin;
use PHPUnit\Framework\Attributes\Group;
use Sabre\VObject\ITip\Message;
use Sabre\VObject\Reader;
use Test\TestCase;

#[Group(name: 'DB')]
class InvitationResponseServerTest extends TestCase {
	public function testIMipPluginIsOnlyRegisteredWhenHandlingITipMessages(): void {
		$server = new InvitationResponseServer();

		// Not registered by the constructor: other flows (e.g. createFromStringMinimal) must not send emails
		$this->assertNull($server->getServer()->getPlugin('imip'));

		$message = new Message();
		$message->uid = 'fb1bc04c-ac3e-4f5d-8329-7a1e0b07a1e0';
		$message->component = 'VEVENT';
		$message->method = 'REPLY';
		$message->sequence = 0;
		$message->sender = 'mailto:attendee@example.com';
		$message->recipient = 'mailto:unknown-organizer@example.com';
		$message->message = Reader::read(<<<EOF
			BEGIN:VCALENDAR
			VERSION:2.0
			METHOD:REPLY
			BEGIN:VEVENT
			ATTENDEE;PARTSTAT=ACCEPTED:mailto:attendee@example.com
			ORGANIZER:mailto:unknown-organizer@example.com
			UID:fb1bc04c-ac3e-4f5d-8329-7a1e0b07a1e0
			SEQUENCE:0
			DTSTAMP:20260611T120000Z
			END:VEVENT
			END:VCALENDAR
			EOF);

		$server->handleITipMessage($message);

		$this->assertInstanceOf(IMipPlugin::class, $server->getServer()->getPlugin('imip'));
	}
}
