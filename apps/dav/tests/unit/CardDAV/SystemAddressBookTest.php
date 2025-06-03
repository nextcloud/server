<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\Tests\unit\CardDAV;

use OC\AppFramework\Http\Request;
use OCA\DAV\CardDAV\SyncService;
use OCA\DAV\CardDAV\SystemAddressbook;
use OCA\Federation\TrustedServers;
use OCP\Accounts\IAccountManager;
use OCP\IConfig;
use OCP\IGroup;
use OCP\IGroupManager;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\MockObject\MockObject;
use Sabre\CardDAV\Backend\BackendInterface;
use Sabre\DAV\Exception\Forbidden;
use Sabre\DAV\Exception\NotFound;
use Sabre\VObject\Component\VCard;
use Sabre\VObject\Reader;
use Test\TestCase;

class SystemAddressBookTest extends TestCase {
	private BackendInterface&MockObject $cardDavBackend;
	private array $addressBookInfo;
	private IL10N&MockObject $l10n;
	private IConfig&MockObject $config;
	private IUserSession $userSession;
	private IRequest&MockObject $request;
	private array $server;
	private TrustedServers&MockObject $trustedServers;
	private IGroupManager&MockObject $groupManager;
	private SystemAddressbook $addressBook;

	protected function setUp(): void {
		parent::setUp();

		$this->cardDavBackend = $this->createMock(BackendInterface::class);
		$this->addressBookInfo = [
			'id' => 123,
			'{DAV:}displayname' => 'Accounts',
			'principaluri' => 'principals/system/system',
		];
		$this->l10n = $this->createMock(IL10N::class);
		$this->config = $this->createMock(IConfig::class);
		$this->userSession = $this->createMock(IUserSession::class);
		$this->request = $this->createMock(Request::class);
		$this->server = [
			'PHP_AUTH_USER' => 'system',
			'PHP_AUTH_PW' => 'shared123',
		];
		$this->request->method('__get')->with('server')->willReturn($this->server);
		$this->trustedServers = $this->createMock(TrustedServers::class);
		$this->groupManager = $this->createMock(IGroupManager::class);

		$this->addressBook = new SystemAddressbook(
			$this->cardDavBackend,
			$this->addressBookInfo,
			$this->l10n,
			$this->config,
			$this->userSession,
			$this->request,
			$this->trustedServers,
			$this->groupManager,
		);
	}

	public function testGetChildrenAsGuest(): void {
		$this->config->expects(self::exactly(3))
			->method('getAppValue')
			->willReturnMap([
				['core', 'shareapi_allow_share_dialog_user_enumeration', 'yes', 'yes'],
				['core', 'shareapi_restrict_user_enumeration_to_group', 'no', 'no'],
				['core', 'shareapi_restrict_user_enumeration_to_phone', 'no', 'no'],
			]);
		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('user');
		$user->method('getBackendClassName')->willReturn('Guests');
		$this->userSession->expects(self::once())
			->method('getUser')
			->willReturn($user);
		$vcfWithScopes = <<<VCF
BEGIN:VCARD
VERSION:3.0
PRODID:-//Sabre//Sabre VObject 4.4.2//EN
UID:admin
FN;X-NC-SCOPE=v2-federated:admin
N;X-NC-SCOPE=v2-federated:admin;;;;
ADR;TYPE=OTHER;X-NC-SCOPE=v2-local:Testing test test test;;;;;;
EMAIL;TYPE=OTHER;X-NC-SCOPE=v2-federated:miau_lalala@gmx.net
TEL;TYPE=OTHER;X-NC-SCOPE=v2-local:+435454454544
CLOUD:admin@http://localhost
END:VCARD
VCF;
		$originalCard = [
			'carddata' => $vcfWithScopes,
		];
		$this->cardDavBackend->expects(self::once())
			->method('getCard')
			->with(123, 'Guests:user.vcf')
			->willReturn($originalCard);

		$children = $this->addressBook->getChildren();

		self::assertCount(1, $children);
	}

	public function testGetFilteredChildForFederation(): void {
		$this->config->expects(self::exactly(3))
			->method('getAppValue')
			->willReturnMap([
				['core', 'shareapi_allow_share_dialog_user_enumeration', 'yes', 'yes'],
				['core', 'shareapi_restrict_user_enumeration_to_group', 'no', 'no'],
				['core', 'shareapi_restrict_user_enumeration_to_phone', 'no', 'no'],
			]);
		$this->trustedServers->expects(self::once())
			->method('getServers')
			->willReturn([
				[
					'shared_secret' => 'shared123',
				],
			]);
		$vcfWithScopes = <<<VCF
BEGIN:VCARD
VERSION:3.0
PRODID:-//Sabre//Sabre VObject 4.4.2//EN
UID:admin
FN;X-NC-SCOPE=v2-federated:admin
N;X-NC-SCOPE=v2-federated:admin;;;;
ADR;TYPE=OTHER;X-NC-SCOPE=v2-local:Testing test test test;;;;;;
EMAIL;TYPE=OTHER;X-NC-SCOPE=v2-federated:miau_lalala@gmx.net
TEL;TYPE=OTHER;X-NC-SCOPE=v2-local:+435454454544
CLOUD:admin@http://localhost
END:VCARD
VCF;
		$originalCard = [
			'carddata' => $vcfWithScopes,
		];
		$this->cardDavBackend->expects(self::once())
			->method('getCard')
			->with(123, 'user.vcf')
			->willReturn($originalCard);

		$card = $this->addressBook->getChild('user.vcf');

		/** @var VCard $vCard */
		$vCard = Reader::read($card->get());
		foreach ($vCard->children() as $child) {
			$scope = $child->offsetGet('X-NC-SCOPE');
			if ($scope !== null) {
				self::assertNotEquals(IAccountManager::SCOPE_PRIVATE, $scope->getValue());
				self::assertNotEquals(IAccountManager::SCOPE_LOCAL, $scope->getValue());
			}
		}
	}

	public function testGetChildNotFound(): void {
		$this->config->expects(self::exactly(3))
			->method('getAppValue')
			->willReturnMap([
				['core', 'shareapi_allow_share_dialog_user_enumeration', 'yes', 'yes'],
				['core', 'shareapi_restrict_user_enumeration_to_group', 'no', 'no'],
				['core', 'shareapi_restrict_user_enumeration_to_phone', 'no', 'no'],
			]);
		$this->trustedServers->expects(self::once())
			->method('getServers')
			->willReturn([
				[
					'shared_secret' => 'shared123',
				],
			]);
		$this->expectException(NotFound::class);

		$this->addressBook->getChild('LDAP:user.vcf');
	}

	public function testGetChildWithoutEnumeration(): void {
		$this->config->expects(self::exactly(3))
			->method('getAppValue')
			->willReturnMap([
				['core', 'shareapi_allow_share_dialog_user_enumeration', 'yes', 'no'],
				['core', 'shareapi_restrict_user_enumeration_to_group', 'no', 'no'],
				['core', 'shareapi_restrict_user_enumeration_to_phone', 'no', 'no'],
			]);
		$this->expectException(Forbidden::class);

		$this->addressBook->getChild('LDAP:user.vcf');
	}

	public function testGetChildAsGuest(): void {
		$this->config->expects(self::exactly(3))
			->method('getAppValue')
			->willReturnMap([
				['core', 'shareapi_allow_share_dialog_user_enumeration', 'yes', 'yes'],
				['core', 'shareapi_restrict_user_enumeration_to_group', 'no', 'no'],
				['core', 'shareapi_restrict_user_enumeration_to_phone', 'no', 'no'],
			]);
		$user = $this->createMock(IUser::class);
		$user->method('getBackendClassName')->willReturn('Guests');
		$this->userSession->expects(self::once())
			->method('getUser')
			->willReturn($user);
		$this->expectException(Forbidden::class);

		$this->addressBook->getChild('LDAP:user.vcf');
	}

	public function testGetChildWithGroupEnumerationRestriction(): void {
		$this->config->expects(self::exactly(3))
			->method('getAppValue')
			->willReturnMap([
				['core', 'shareapi_allow_share_dialog_user_enumeration', 'yes', 'yes'],
				['core', 'shareapi_restrict_user_enumeration_to_group', 'no', 'yes'],
				['core', 'shareapi_restrict_user_enumeration_to_phone', 'no', 'no'],
			]);
		$user = $this->createMock(IUser::class);
		$user->method('getBackendClassName')->willReturn('LDAP');
		$this->userSession->expects(self::once())
			->method('getUser')
			->willReturn($user);
		$otherUser = $this->createMock(IUser::class);
		$user->method('getBackendClassName')->willReturn('LDAP');
		$otherUser->method('getUID')->willReturn('other');
		$group = $this->createMock(IGroup::class);
		$group->expects(self::once())
			->method('getUsers')
			->willReturn([$otherUser]);
		$this->groupManager->expects(self::once())
			->method('getUserGroups')
			->with($user)
			->willReturn([$group]);
		$cardData = <<<VCF
BEGIN:VCARD
VERSION:3.0
PRODID:-//Sabre//Sabre VObject 4.4.2//EN
UID:admin
FN;X-NC-SCOPE=v2-federated:other
END:VCARD
VCF;
		$this->cardDavBackend->expects(self::once())
			->method('getCard')
			->with($this->addressBookInfo['id'], "{$otherUser->getBackendClassName()}:{$otherUser->getUID()}.vcf")
			->willReturn([
				'id' => 123,
				'carddata' => $cardData,
			]);

		$this->addressBook->getChild("{$otherUser->getBackendClassName()}:{$otherUser->getUID()}.vcf");
	}

	public function testGetChildWithPhoneNumberEnumerationRestriction(): void {
		$this->config->expects(self::exactly(3))
			->method('getAppValue')
			->willReturnMap([
				['core', 'shareapi_allow_share_dialog_user_enumeration', 'yes', 'yes'],
				['core', 'shareapi_restrict_user_enumeration_to_group', 'no', 'no'],
				['core', 'shareapi_restrict_user_enumeration_to_phone', 'no', 'yes'],
			]);
		$user = $this->createMock(IUser::class);
		$user->method('getBackendClassName')->willReturn('LDAP');
		$this->userSession->expects(self::once())
			->method('getUser')
			->willReturn($user);
		$this->expectException(Forbidden::class);

		$this->addressBook->getChild('LDAP:user.vcf');
	}

	public function testGetOwnChildWithPhoneNumberEnumerationRestriction(): void {
		$this->config->expects(self::exactly(3))
			->method('getAppValue')
			->willReturnMap([
				['core', 'shareapi_allow_share_dialog_user_enumeration', 'yes', 'yes'],
				['core', 'shareapi_restrict_user_enumeration_to_group', 'no', 'no'],
				['core', 'shareapi_restrict_user_enumeration_to_phone', 'no', 'yes'],
			]);
		$user = $this->createMock(IUser::class);
		$user->method('getBackendClassName')->willReturn('LDAP');
		$user->method('getUID')->willReturn('user');
		$this->userSession->expects(self::once())
			->method('getUser')
			->willReturn($user);
		$cardData = <<<VCF
BEGIN:VCARD
VERSION:3.0
PRODID:-//Sabre//Sabre VObject 4.4.2//EN
UID:admin
FN;X-NC-SCOPE=v2-federated:user
END:VCARD
VCF;
		$this->cardDavBackend->expects(self::once())
			->method('getCard')
			->with($this->addressBookInfo['id'], 'LDAP:user.vcf')
			->willReturn([
				'id' => 123,
				'carddata' => $cardData,
			]);

		$this->addressBook->getChild('LDAP:user.vcf');
	}

	public function testGetMultipleChildrenWithGroupEnumerationRestriction(): void {
		$this->config
			->method('getAppValue')
			->willReturnMap([
				['core', 'shareapi_allow_share_dialog_user_enumeration', 'yes', 'yes'],
				['core', 'shareapi_restrict_user_enumeration_to_group', 'no', 'yes'],
				['core', 'shareapi_restrict_user_enumeration_to_phone', 'no', 'no'],
			]);
		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('user');
		$user->method('getBackendClassName')->willReturn('LDAP');
		$other1 = $this->createMock(IUser::class);
		$other1->method('getUID')->willReturn('other1');
		$other1->method('getBackendClassName')->willReturn('LDAP');
		$other2 = $this->createMock(IUser::class);
		$other2->method('getUID')->willReturn('other2');
		$other2->method('getBackendClassName')->willReturn('LDAP');
		$other3 = $this->createMock(IUser::class);
		$other3->method('getUID')->willReturn('other3');
		$other3->method('getBackendClassName')->willReturn('LDAP');
		$this->userSession
			->method('getUser')
			->willReturn($user);
		$group1 = $this->createMock(IGroup::class);
		$group1
			->method('getUsers')
			->willReturn([$user, $other1]);
		$group2 = $this->createMock(IGroup::class);
		$group2
			->method('getUsers')
			->willReturn([$other1, $other2, $user]);
		$this->groupManager
			->method('getUserGroups')
			->with($user)
			->willReturn([$group1]);
		$this->cardDavBackend->expects(self::once())
			->method('getMultipleCards')
			->with($this->addressBookInfo['id'], [
				SyncService::getCardUri($user),
				SyncService::getCardUri($other1),
			])
			->willReturn([
				[],
				[],
			]);

		$cards = $this->addressBook->getMultipleChildren([
			SyncService::getCardUri($user),
			SyncService::getCardUri($other1),
			// SyncService::getCardUri($other2), // Omitted to test that it's not returned as stray
			SyncService::getCardUri($other3), // No overlapping group with this one
		]);

		self::assertCount(2, $cards);
	}

	public function testGetMultipleChildrenAsGuest(): void {
		$this->config
			->method('getAppValue')
			->willReturnMap([
				['core', 'shareapi_allow_share_dialog_user_enumeration', 'yes', 'yes'],
				['core', 'shareapi_restrict_user_enumeration_to_group', 'no', 'no'],
				['core', 'shareapi_restrict_user_enumeration_to_phone', 'no', 'no'],
			]);
		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('user');
		$user->method('getBackendClassName')->willReturn('Guests');
		$this->userSession->expects(self::once())
			->method('getUser')
			->willReturn($user);

		$cards = $this->addressBook->getMultipleChildren(['Database:user1.vcf', 'LDAP:user2.vcf']);

		self::assertEmpty($cards);
	}

	public function testGetMultipleChildren(): void {
		$this->config
			->method('getAppValue')
			->willReturnMap([
				['core', 'shareapi_allow_share_dialog_user_enumeration', 'yes', 'yes'],
				['core', 'shareapi_restrict_user_enumeration_to_group', 'no', 'no'],
				['core', 'shareapi_restrict_user_enumeration_to_phone', 'no', 'no'],
			]);
		$this->trustedServers
			->method('getServers')
			->willReturn([
				[
					'shared_secret' => 'shared123',
				],
			]);
		$cardData = <<<VCF
BEGIN:VCARD
VERSION:3.0
PRODID:-//Sabre//Sabre VObject 4.4.2//EN
UID:admin
FN;X-NC-SCOPE=v2-federated:user
END:VCARD
VCF;
		$this->cardDavBackend->expects(self::once())
			->method('getMultipleCards')
			->with($this->addressBookInfo['id'], ['Database:user1.vcf', 'LDAP:user2.vcf'])
			->willReturn([
				[
					'id' => 123,
					'carddata' => $cardData,
				],
				[
					'id' => 321,
					'carddata' => $cardData,
				],
			]);

		$cards = $this->addressBook->getMultipleChildren(['Database:user1.vcf', 'LDAP:user2.vcf']);

		self::assertCount(2, $cards);
	}
}
