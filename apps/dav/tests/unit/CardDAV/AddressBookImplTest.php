<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\DAV\Tests\unit\CardDAV;

use OCA\DAV\CardDAV\AddressBook;
use OCA\DAV\CardDAV\AddressBookImpl;
use OCA\DAV\CardDAV\CardDavBackend;
use OCA\DAV\Db\PropertyMapper;
use OCP\IURLGenerator;
use PHPUnit\Framework\MockObject\MockObject;
use Sabre\VObject\Component\VCard;
use Sabre\VObject\Property\Text;
//use Sabre\VObject\Property\;
use Test\TestCase;

class AddressBookImplTest extends TestCase {
	private array $addressBookInfo;
	private AddressBook&MockObject $addressBook;
	private IURLGenerator&MockObject $urlGenerator;
	private CardDavBackend&MockObject $backend;
	private PropertyMapper&MockObject $propertyMapper;
	private VCard&MockObject $vCard;
	private AddressBookImpl $addressBookImpl;

	protected function setUp(): void {
		parent::setUp();

		$this->addressBookInfo = [
			'id' => 42,
			'uri' => 'system',
			'principaluri' => 'principals/system/system',
			'{DAV:}displayname' => 'display name',
		];
		$this->addressBook = $this->createMock(AddressBook::class);
		$this->backend = $this->createMock(CardDavBackend::class);
		$this->vCard = $this->createMock(VCard::class);
		$this->urlGenerator = $this->createMock(IURLGenerator::class);
		$this->propertyMapper = $this->createMock(PropertyMapper::class);

		$this->addressBookImpl = new AddressBookImpl(
			$this->addressBook,
			$this->addressBookInfo,
			$this->backend,
			$this->urlGenerator,
			$this->propertyMapper,
			null
		);
	}

	public function testGetKey(): void {
		$this->assertSame($this->addressBookInfo['id'],
			$this->addressBookImpl->getKey());
	}

	public function testGetDisplayName(): void {
		$this->assertSame($this->addressBookInfo['{DAV:}displayname'],
			$this->addressBookImpl->getDisplayName());
	}

	public function testSearch(): void {
		/** @var MockObject&AddressBookImpl $addressBookImpl */
		$addressBookImpl = $this->getMockBuilder(AddressBookImpl::class)
			->setConstructorArgs(
				[
					$this->addressBook,
					$this->addressBookInfo,
					$this->backend,
					$this->urlGenerator,
					$this->propertyMapper,
					null
				]
			)
			->onlyMethods(['vCard2Array', 'readCard'])
			->getMock();

		$pattern = 'pattern';
		$searchProperties = 'properties';

		$this->backend->expects($this->once())->method('search')
			->with($this->addressBookInfo['id'], $pattern, $searchProperties)
			->willReturn(
				[
					['uri' => 'foo.vcf', 'carddata' => 'cardData1'],
					['uri' => 'bar.vcf', 'carddata' => 'cardData2']
				]
			);

		$addressBookImpl->expects($this->exactly(2))->method('readCard')
			->willReturn($this->vCard);
		$addressBookImpl->expects($this->exactly(2))->method('vCard2Array')
			->willReturnMap([
				['foo.vcf', $this->vCard, 'vCard'],
				['bar.vcf', $this->vCard, 'vCard'],
			]);

		$result = $addressBookImpl->search($pattern, $searchProperties, []);
		$this->assertTrue((is_array($result)));
		$this->assertSame(2, count($result));
	}

	/**
	 * @dataProvider dataTestCreate
	 */
	public function testCreate(array $properties): void {
		$uid = 'uid';

		/** @var MockObject&AddressBookImpl $addressBookImpl */
		$addressBookImpl = $this->getMockBuilder(AddressBookImpl::class)
			->setConstructorArgs(
				[
					$this->addressBook,
					$this->addressBookInfo,
					$this->backend,
					$this->urlGenerator,
					$this->propertyMapper,
					null
				]
			)
			->onlyMethods(['vCard2Array', 'createUid', 'createEmptyVCard'])
			->getMock();

		$expectedProperties = 0;
		foreach ($properties as $data) {
			if (is_string($data)) {
				$expectedProperties++;
			} else {
				$expectedProperties += count($data);
			}
		}

		$addressBookImpl->expects($this->once())->method('createUid')
			->willReturn($uid);
		$addressBookImpl->expects($this->once())->method('createEmptyVCard')
			->with($uid)->willReturn($this->vCard);
		$this->vCard->expects($this->exactly($expectedProperties))
			->method('createProperty');
		$this->backend->expects($this->once())->method('createCard');
		$this->backend->expects($this->never())->method('updateCard');
		$this->backend->expects($this->never())->method('getCard');
		$addressBookImpl->expects($this->once())->method('vCard2Array')
			->with('uid.vcf', $this->vCard)->willReturn(true);

		$this->assertTrue($addressBookImpl->createOrUpdate($properties));
	}

	public static function dataTestCreate(): array {
		return [
			[[]],
			[['FN' => 'John Doe']],
			[['FN' => 'John Doe', 'EMAIL' => ['john@doe.cloud', 'john.doe@example.org']]],
		];
	}

	public function testUpdate(): void {
		$uid = 'uid';
		$uri = 'bla.vcf';
		$properties = ['URI' => $uri, 'UID' => $uid, 'FN' => 'John Doe'];

		/** @var MockObject&AddressBookImpl $addressBookImpl */
		$addressBookImpl = $this->getMockBuilder(AddressBookImpl::class)
			->setConstructorArgs(
				[
					$this->addressBook,
					$this->addressBookInfo,
					$this->backend,
					$this->urlGenerator,
					$this->propertyMapper,
					null
				]
			)
			->onlyMethods(['vCard2Array', 'createUid', 'createEmptyVCard', 'readCard'])
			->getMock();

		$addressBookImpl->expects($this->never())->method('createUid');
		$addressBookImpl->expects($this->never())->method('createEmptyVCard');
		$this->backend->expects($this->once())->method('getCard')
			->with($this->addressBookInfo['id'], $uri)
			->willReturn(['carddata' => 'data']);
		$addressBookImpl->expects($this->once())->method('readCard')
			->with('data')->willReturn($this->vCard);
		$this->vCard->expects($this->exactly(count($properties) - 1))
			->method('createProperty');
		$this->backend->expects($this->never())->method('createCard');
		$this->backend->expects($this->once())->method('updateCard');
		$addressBookImpl->expects($this->once())->method('vCard2Array')
			->with($uri, $this->vCard)->willReturn(true);

		$this->assertTrue($addressBookImpl->createOrUpdate($properties));
	}

	public function testUpdateWithTypes(): void {
		$uid = 'uid';
		$uri = 'bla.vcf';
		$properties = ['URI' => $uri, 'UID' => $uid, 'FN' => 'John Doe', 'ADR' => [['type' => 'HOME', 'value' => ';;street;city;;;country']]];
		$vCard = new vCard;
		$textProperty = $vCard->createProperty('KEY', 'value');

		/** @var MockObject&AddressBookImpl $addressBookImpl */
		$addressBookImpl = $this->getMockBuilder(AddressBookImpl::class)
			->setConstructorArgs(
				[
					$this->addressBook,
					$this->addressBookInfo,
					$this->backend,
					$this->urlGenerator,
					$this->propertyMapper,
					null
				]
			)
			->onlyMethods(['vCard2Array', 'createUid', 'createEmptyVCard', 'readCard'])
			->getMock();

		$this->backend->expects($this->once())->method('getCard')
			->with($this->addressBookInfo['id'], $uri)
			->willReturn(['carddata' => 'data']);
		$addressBookImpl->expects($this->once())->method('readCard')
			->with('data')->willReturn($this->vCard);
		$this->vCard->method('createProperty')->willReturn($textProperty);
		$this->vCard->expects($this->exactly(count($properties) - 1))
			->method('createProperty');
		$this->vCard->expects($this->once())->method('remove')
			->with('ADR');
		$this->vCard->expects($this->once())->method('add');

		$addressBookImpl->createOrUpdate($properties);
	}

	/**
	 * @dataProvider dataTestGetPermissions
	 */
	public function testGetPermissions(array $permissions, int $expected): void {
		$this->addressBook->expects($this->once())->method('getACL')
			->willReturn($permissions);

		$this->assertSame($expected,
			$this->addressBookImpl->getPermissions()
		);
	}

	public static function dataTestGetPermissions(): array {
		return [
			[[], 0],
			[[['privilege' => '{DAV:}read']], 1],
			[[['privilege' => '{DAV:}write']], 6],
			[[['privilege' => '{DAV:}all']], 31],
			[[['privilege' => '{DAV:}read'],['privilege' => '{DAV:}write']], 7],
			[[['privilege' => '{DAV:}read'],['privilege' => '{DAV:}all']], 31],
			[[['privilege' => '{DAV:}all'],['privilege' => '{DAV:}write']], 31],
			[[['privilege' => '{DAV:}read'],['privilege' => '{DAV:}write'],['privilege' => '{DAV:}all']], 31],
			[[['privilege' => '{DAV:}all'],['privilege' => '{DAV:}read'],['privilege' => '{DAV:}write']], 31],
		];
	}

	public function testDelete(): void {
		$cardId = 1;
		$cardUri = 'cardUri';
		$this->backend->expects($this->once())->method('getCardUri')
			->with($cardId)->willReturn($cardUri);
		$this->backend->expects($this->once())->method('deleteCard')
			->with($this->addressBookInfo['id'], $cardUri)
			->willReturn(true);

		$this->assertTrue($this->addressBookImpl->delete($cardId));
	}

	public function testReadCard(): void {
		$vCard = new VCard();
		$vCard->add(new Text($vCard, 'UID', 'uid'));
		$vCardSerialized = $vCard->serialize();

		$result = $this->invokePrivate($this->addressBookImpl, 'readCard', [$vCardSerialized]);
		$resultSerialized = $result->serialize();

		$this->assertSame($vCardSerialized, $resultSerialized);
	}

	public function testCreateUid(): void {
		/** @var MockObject&AddressBookImpl $addressBookImpl */
		$addressBookImpl = $this->getMockBuilder(AddressBookImpl::class)
			->setConstructorArgs(
				[
					$this->addressBook,
					$this->addressBookInfo,
					$this->backend,
					$this->urlGenerator,
					$this->propertyMapper,
					null
				]
			)
			->onlyMethods(['getUid'])
			->getMock();

		$addressBookImpl->expects($this->exactly(2))
			->method('getUid')
			->willReturnOnConsecutiveCalls(
				'uid0',
				'uid1',
			);

		// simulate that 'uid0' already exists, so the second uid will be returned
		$this->backend->expects($this->exactly(2))->method('getContact')
			->willReturnCallback(
				function ($id, $uid) {
					return ($uid === 'uid0.vcf');
				}
			);

		$this->assertSame('uid1',
			$this->invokePrivate($addressBookImpl, 'createUid', [])
		);
	}

	public function testCreateEmptyVCard(): void {
		$uid = 'uid';
		$expectedVCard = new VCard();
		$expectedVCard->UID = $uid;
		$expectedVCardSerialized = $expectedVCard->serialize();

		$result = $this->invokePrivate($this->addressBookImpl, 'createEmptyVCard', [$uid]);
		$resultSerialized = $result->serialize();

		$this->assertSame($expectedVCardSerialized, $resultSerialized);
	}

	public function testVCard2Array(): void {
		$vCard = new VCard();

		$vCard->add($vCard->createProperty('FN', 'Full Name'));

		// Multi-value properties
		$vCard->add($vCard->createProperty('CLOUD', 'cloud-user1@localhost'));
		$vCard->add($vCard->createProperty('CLOUD', 'cloud-user2@example.tld'));
		$vCard->add($vCard->createProperty('EMAIL', 'email-user1@localhost'));
		$vCard->add($vCard->createProperty('EMAIL', 'email-user2@example.tld'));
		$vCard->add($vCard->createProperty('IMPP', 'impp-user1@localhost'));
		$vCard->add($vCard->createProperty('IMPP', 'impp-user2@example.tld'));
		$vCard->add($vCard->createProperty('TEL', '+49 123456789'));
		$vCard->add($vCard->createProperty('TEL', '+1 555 123456789'));
		$vCard->add($vCard->createProperty('URL', 'https://localhost'));
		$vCard->add($vCard->createProperty('URL', 'https://example.tld'));

		// Type depending properties
		$property = $vCard->createProperty('X-SOCIALPROFILE', 'tw-example');
		$property->add('TYPE', 'twitter');
		$vCard->add($property);
		$property = $vCard->createProperty('X-SOCIALPROFILE', 'tw-example-2');
		$property->add('TYPE', 'twitter');
		$vCard->add($property);
		$property = $vCard->createProperty('X-SOCIALPROFILE', 'fb-example');
		$property->add('TYPE', 'facebook');
		$vCard->add($property);

		$array = $this->invokePrivate($this->addressBookImpl, 'vCard2Array', ['uri', $vCard]);
		unset($array['PRODID']);
		unset($array['UID']);

		$this->assertEquals([
			'URI' => 'uri',
			'VERSION' => '4.0',
			'FN' => 'Full Name',
			'CLOUD' => [
				'cloud-user1@localhost',
				'cloud-user2@example.tld',
			],
			'EMAIL' => [
				'email-user1@localhost',
				'email-user2@example.tld',
			],
			'IMPP' => [
				'impp-user1@localhost',
				'impp-user2@example.tld',
			],
			'TEL' => [
				'+49 123456789',
				'+1 555 123456789',
			],
			'URL' => [
				'https://localhost',
				'https://example.tld',
			],

			'X-SOCIALPROFILE' => [
				'tw-example',
				'tw-example-2',
				'fb-example',
			],

			'isLocalSystemBook' => true,
		], $array);
	}

	public function testVCard2ArrayWithTypes(): void {
		$vCard = new VCard();

		$vCard->add($vCard->createProperty('FN', 'Full Name'));

		// Multi-value properties
		$vCard->add($vCard->createProperty('CLOUD', 'cloud-user1@localhost'));
		$vCard->add($vCard->createProperty('CLOUD', 'cloud-user2@example.tld'));

		$property = $vCard->createProperty('EMAIL', 'email-user1@localhost');
		$property->add('TYPE', 'HOME');
		$vCard->add($property);
		$property = $vCard->createProperty('EMAIL', 'email-user2@example.tld');
		$property->add('TYPE', 'WORK');
		$vCard->add($property);

		$vCard->add($vCard->createProperty('IMPP', 'impp-user1@localhost'));
		$vCard->add($vCard->createProperty('IMPP', 'impp-user2@example.tld'));

		$property = $vCard->createProperty('TEL', '+49 123456789');
		$property->add('TYPE', 'HOME,VOICE');
		$vCard->add($property);
		$property = $vCard->createProperty('TEL', '+1 555 123456789');
		$property->add('TYPE', 'WORK');
		$vCard->add($property);

		$vCard->add($vCard->createProperty('URL', 'https://localhost'));
		$vCard->add($vCard->createProperty('URL', 'https://example.tld'));

		// Type depending properties
		$property = $vCard->createProperty('X-SOCIALPROFILE', 'tw-example');
		$property->add('TYPE', 'twitter');
		$vCard->add($property);
		$property = $vCard->createProperty('X-SOCIALPROFILE', 'tw-example-2');
		$property->add('TYPE', 'twitter');
		$vCard->add($property);
		$property = $vCard->createProperty('X-SOCIALPROFILE', 'fb-example');
		$property->add('TYPE', 'facebook');
		$vCard->add($property);

		$array = $this->invokePrivate($this->addressBookImpl, 'vCard2Array', ['uri', $vCard, true]);
		unset($array['PRODID']);
		unset($array['UID']);

		$this->assertEquals([
			'URI' => 'uri',
			'VERSION' => '4.0',
			'FN' => 'Full Name',
			'CLOUD' => [
				['type' => '', 'value' => 'cloud-user1@localhost'],
				['type' => '', 'value' => 'cloud-user2@example.tld'],
			],
			'EMAIL' => [
				['type' => 'HOME', 'value' => 'email-user1@localhost'],
				['type' => 'WORK', 'value' => 'email-user2@example.tld'],
			],
			'IMPP' => [
				['type' => '', 'value' => 'impp-user1@localhost'],
				['type' => '', 'value' => 'impp-user2@example.tld'],
			],
			'TEL' => [
				['type' => 'HOME,VOICE', 'value' => '+49 123456789'],
				['type' => 'WORK', 'value' => '+1 555 123456789'],
			],
			'URL' => [
				['type' => '', 'value' => 'https://localhost'],
				['type' => '', 'value' => 'https://example.tld'],
			],

			'X-SOCIALPROFILE' => [
				['type' => 'twitter', 'value' => 'tw-example'],
				['type' => 'twitter', 'value' => 'tw-example-2'],
				['type' => 'facebook', 'value' => 'fb-example'],
			],

			'isLocalSystemBook' => true,
		], $array);
	}

	public function testIsSystemAddressBook(): void {
		$addressBookInfo = [
			'{http://owncloud.org/ns}owner-principal' => 'principals/system/system',
			'principaluri' => 'principals/system/system',
			'{DAV:}displayname' => 'display name',
			'id' => 666,
			'uri' => 'system',
		];

		$addressBookImpl = new AddressBookImpl(
			$this->addressBook,
			$addressBookInfo,
			$this->backend,
			$this->urlGenerator,
			$this->propertyMapper,
			null
		);

		$this->assertTrue($addressBookImpl->isSystemAddressBook());
	}

	public function testIsShared(): void {
		$addressBookInfo = [
			'{http://owncloud.org/ns}owner-principal' => 'user1',
			'{DAV:}displayname' => 'Test address book',
			'principaluri' => 'user2',
			'id' => 666,
			'uri' => 'default',
		];

		$addressBookImpl = new AddressBookImpl(
			$this->addressBook,
			$addressBookInfo,
			$this->backend,
			$this->urlGenerator,
			$this->propertyMapper,
			'user2'
		);

		$this->assertFalse($addressBookImpl->isSystemAddressBook());
		$this->assertTrue($addressBookImpl->isShared());
	}

	public function testIsNotShared(): void {
		$addressBookInfo = [
			'{http://owncloud.org/ns}owner-principal' => 'user1',
			'{DAV:}displayname' => 'Test address book',
			'principaluri' => 'user1',
			'id' => 666,
			'uri' => 'default',
		];

		$addressBookImpl = new AddressBookImpl(
			$this->addressBook,
			$addressBookInfo,
			$this->backend,
			$this->urlGenerator,
			$this->propertyMapper,
			'user2'
		);

		$this->assertFalse($addressBookImpl->isSystemAddressBook());
		$this->assertFalse($addressBookImpl->isShared());
	}
}
