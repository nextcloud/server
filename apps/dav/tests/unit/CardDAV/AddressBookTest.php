<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Thomas Citharel <nextcloud@tcit.fr>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 *
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 */
namespace OCA\DAV\Tests\unit\CardDAV;

use OCA\DAV\CardDAV\AddressBook;
use OCA\DAV\CardDAV\Card;
use OCA\DAV\CardDAV\CardDavBackend;
use OCP\IL10N;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Sabre\DAV\Exception\Forbidden;
use Sabre\DAV\PropPatch;
use Test\TestCase;

class AddressBookTest extends TestCase {
	public function testMove(): void {
		$backend = $this->createMock(CardDavBackend::class);
		$addressBookInfo = [
			'{http://owncloud.org/ns}owner-principal' => 'user1',
			'{DAV:}displayname' => 'Test address book',
			'principaluri' => 'user2',
			'id' => 666,
			'uri' => 'default',
		];
		$l10n = $this->createMock(IL10N::class);
		$logger = $this->createMock(LoggerInterface::class);
		$addressBook = new AddressBook($backend, $addressBookInfo, $l10n, $logger);

		$card = new Card($backend, $addressBookInfo, ['id' => 5, 'carddata' => 'RANDOM VCF DATA', 'uri' => 'something', 'addressbookid' => 23]);

		$backend->expects($this->once())->method('moveCard')->with(23, 666, 'something', 'user1')->willReturn(true);

		$addressBook->moveInto('new', 'old', $card);
	}

	public function testDelete(): void {
		/** @var MockObject | CardDavBackend $backend */
		$backend = $this->getMockBuilder(CardDavBackend::class)->disableOriginalConstructor()->getMock();
		$backend->expects($this->once())->method('updateShares');
		$backend->expects($this->any())->method('getShares')->willReturn([
			['href' => 'principal:user2']
		]);
		$addressBookInfo = [
			'{http://owncloud.org/ns}owner-principal' => 'user1',
			'{DAV:}displayname' => 'Test address book',
			'principaluri' => 'user2',
			'id' => 666,
			'uri' => 'default',
		];
		$l10n = $this->createMock(IL10N::class);
		$logger = $this->createMock(LoggerInterface::class);
		$addressBook = new AddressBook($backend, $addressBookInfo, $l10n, $logger);
		$addressBook->delete();
	}


	public function testDeleteFromGroup(): void {
		$this->expectException(Forbidden::class);

		/** @var MockObject | CardDavBackend $backend */
		$backend = $this->getMockBuilder(CardDavBackend::class)->disableOriginalConstructor()->getMock();
		$backend->expects($this->never())->method('updateShares');
		$backend->expects($this->any())->method('getShares')->willReturn([
			['href' => 'principal:group2']
		]);
		$addressBookInfo = [
			'{http://owncloud.org/ns}owner-principal' => 'user1',
			'{DAV:}displayname' => 'Test address book',
			'principaluri' => 'user2',
			'id' => 666,
			'uri' => 'default',
		];
		$l10n = $this->createMock(IL10N::class);
		$logger = $this->createMock(LoggerInterface::class);
		$addressBook = new AddressBook($backend, $addressBookInfo, $l10n, $logger);
		$addressBook->delete();
	}


	public function testPropPatch(): void {
		$this->expectException(Forbidden::class);

		/** @var MockObject | CardDavBackend $backend */
		$backend = $this->getMockBuilder(CardDavBackend::class)->disableOriginalConstructor()->getMock();
		$addressBookInfo = [
			'{http://owncloud.org/ns}owner-principal' => 'user1',
			'{DAV:}displayname' => 'Test address book',
			'principaluri' => 'user2',
			'id' => 666,
			'uri' => 'default',
		];
		$l10n = $this->createMock(IL10N::class);
		$logger = $this->createMock(LoggerInterface::class);
		$addressBook = new AddressBook($backend, $addressBookInfo, $l10n, $logger);
		$addressBook->propPatch(new PropPatch([]));
	}

	/**
	 * @dataProvider providesReadOnlyInfo
	 */
	public function testAcl($expectsWrite, $readOnlyValue, $hasOwnerSet): void {
		/** @var MockObject | CardDavBackend $backend */
		$backend = $this->getMockBuilder(CardDavBackend::class)->disableOriginalConstructor()->getMock();
		$backend->expects($this->any())->method('applyShareAcl')->willReturnArgument(1);
		$addressBookInfo = [
			'{DAV:}displayname' => 'Test address book',
			'principaluri' => 'user2',
			'id' => 666,
			'uri' => 'default'
		];
		if (!is_null($readOnlyValue)) {
			$addressBookInfo['{http://owncloud.org/ns}read-only'] = $readOnlyValue;
		}
		if ($hasOwnerSet) {
			$addressBookInfo['{http://owncloud.org/ns}owner-principal'] = 'user1';
		}
		$l10n = $this->createMock(IL10N::class);
		$logger = $this->createMock(LoggerInterface::class);
		$addressBook = new AddressBook($backend, $addressBookInfo, $l10n, $logger);
		$acl = $addressBook->getACL();
		$childAcl = $addressBook->getChildACL();

		$expectedAcl = [[
			'privilege' => '{DAV:}read',
			'principal' => $hasOwnerSet ? 'user1' : 'user2',
			'protected' => true
		], [
			'privilege' => '{DAV:}write',
			'principal' => $hasOwnerSet ? 'user1' : 'user2',
			'protected' => true
		]];
		if ($hasOwnerSet) {
			$expectedAcl[] = [
				'privilege' => '{DAV:}read',
				'principal' => 'user2',
				'protected' => true
			];
			if ($expectsWrite) {
				$expectedAcl[] = [
					'privilege' => '{DAV:}write',
					'principal' => 'user2',
					'protected' => true
				];
			}
		}
		$this->assertEquals($expectedAcl, $acl);
		$this->assertEquals($expectedAcl, $childAcl);
	}

	public function providesReadOnlyInfo(): array {
		return [
			'read-only property not set' => [true, null, true],
			'read-only property is false' => [true, false, true],
			'read-only property is true' => [false, true, true],
			'read-only property not set and no owner' => [true, null, false],
			'read-only property is false and no owner' => [true, false, false],
			'read-only property is true and no owner' => [false, true, false],
		];
	}
}
