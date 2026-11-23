<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\Tests\unit\CardDAV;

use OC\AppFramework\Http\Request;
use OCA\DAV\CardDAV\CardDavBackend;
use OCA\DAV\CardDAV\SyncService;
use OCA\DAV\CardDAV\SystemAddressbook;
use OCA\Federation\TrustedServers;
use OCP\Accounts\IAccountManager;
use OCP\IGroup;
use OCP\IGroupManager;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserSession;
use OCP\Share\IManager as IShareManager;
use PHPUnit\Framework\Attributes\DataProvider;
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
	private IShareManager&MockObject $shareManager;
	private IUserSession&MockObject $userSession;
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
		$this->shareManager = $this->createMock(IShareManager::class);
		$this->userSession = $this->createMock(IUserSession::class);
		$this->request = $this->createMock(Request::class);
		$this->server = [];
		$this->request->method('__get')->with('server')->willReturnCallback(fn () => $this->server);
		$this->trustedServers = $this->createMock(TrustedServers::class);
		$this->groupManager = $this->createMock(IGroupManager::class);

		$this->addressBook = new SystemAddressbook(
			$this->cardDavBackend,
			$this->addressBookInfo,
			$this->l10n,
			$this->shareManager,
			$this->userSession,
			$this->request,
			$this->trustedServers,
			$this->groupManager,
		);
	}

	private function mockFederatedRequest(): void {
		$this->server = [
			'PHP_AUTH_USER' => 'system',
			'PHP_AUTH_PW' => 'shared123',
		];
	}

	private function mockShareSettings(bool $enumeration, bool $group, bool $phone, bool $ownGroups, array $excludedGroups = []): void {
		$this->shareManager->method('allowEnumeration')->willReturn($enumeration);
		$this->shareManager->method('limitEnumerationToGroups')->willReturn($enumeration && $group);
		$this->shareManager->method('limitEnumerationToPhone')->willReturn($enumeration && $phone);
		$this->shareManager->method('shareWithGroupMembersOnly')->willReturn($ownGroups);
		$this->shareManager->method('shareWithGroupMembersOnlyExcludeGroupsList')->willReturn($ownGroups ? $excludedGroups : []);
	}

	public function testGetChildrenAsGuest(): void {
		$this->mockShareSettings(true, false, false, false);
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
		$this->mockFederatedRequest();
		$this->shareManager->expects(self::never())
			->method('allowEnumeration');
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
		$this->mockFederatedRequest();
		$this->shareManager->expects(self::never())
			->method('allowEnumeration');
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

	public static function federationRestrictionProvider(): array {
		return [
			'enumeration disabled' => [false, false, false, false],
			'enumeration limited to phone' => [true, false, true, false],
		];
	}

	public static function federationGroupRestrictionProvider(): array {
		return [
			'enumeration limited to groups' => [true, true, false, false],
			'enumeration limited to groups and phone' => [true, true, true, false],
			'only share with group members' => [true, false, false, true],
			'only share with group members, enumeration disabled' => [false, false, false, true],
		];
	}

	private function mockFederationWithRestrictions(bool $enumeration, bool $group, bool $phone, bool $ownGroups): void {
		$this->mockFederatedRequest();
		$this->mockShareSettings($enumeration, $group, $phone, $ownGroups);
		$this->trustedServers
			->method('getServers')
			->willReturn([
				[
					'shared_secret' => 'shared123',
				],
			]);
		$this->userSession
			->method('getUser')
			->willReturn(null);
	}

	private function getFederatedCard(): array {
		return [
			'id' => 123,
			'carddata' => <<<VCF
BEGIN:VCARD
VERSION:3.0
PRODID:-//Sabre//Sabre VObject 4.4.2//EN
UID:admin
FN;X-NC-SCOPE=v2-federated:user
END:VCARD
VCF,
		];
	}

	#[DataProvider('federationRestrictionProvider')]
	public function testGetChildForFederationBypassesRestrictions(bool $enumeration, bool $group, bool $phone, bool $ownGroups): void {
		$this->mockFederationWithRestrictions($enumeration, $group, $phone, $ownGroups);
		$this->cardDavBackend->expects(self::once())
			->method('getCard')
			->with(123, 'LDAP:user.vcf')
			->willReturn($this->getFederatedCard());

		$card = $this->addressBook->getChild('LDAP:user.vcf');

		self::assertStringContainsString('FN', $card->get());
	}

	#[DataProvider('federationRestrictionProvider')]
	public function testGetMultipleChildrenForFederationBypassesRestrictions(bool $enumeration, bool $group, bool $phone, bool $ownGroups): void {
		$this->mockFederationWithRestrictions($enumeration, $group, $phone, $ownGroups);
		$this->cardDavBackend->expects(self::once())
			->method('getMultipleCards')
			->with(123, ['LDAP:user.vcf'])
			->willReturn([$this->getFederatedCard()]);

		$cards = $this->addressBook->getMultipleChildren(['LDAP:user.vcf']);

		self::assertCount(1, $cards);
	}

	#[DataProvider('federationRestrictionProvider')]
	public function testGetChangesForFederationBypassesRestrictions(bool $enumeration, bool $group, bool $phone, bool $ownGroups): void {
		$this->mockFederationWithRestrictions($enumeration, $group, $phone, $ownGroups);
		$backend = $this->createMock(CardDavBackend::class);
		$backend->method('getChangesForAddressBook')
			->willReturn([
				'syncToken' => 2,
				'added' => ['LDAP:added.vcf'],
				'modified' => ['LDAP:modified.vcf'],
				'deleted' => ['LDAP:deleted.vcf'],
			]);
		$backend->method('getCard')
			->willReturn($this->getFederatedCard());
		$addressBook = new SystemAddressbook(
			$backend,
			$this->addressBookInfo,
			$this->l10n,
			$this->shareManager,
			$this->userSession,
			$this->request,
			$this->trustedServers,
			$this->groupManager,
		);

		$changes = $addressBook->getChanges(1, 1);

		self::assertSame(['LDAP:added.vcf'], $changes['added']);
		self::assertSame(['LDAP:modified.vcf'], $changes['modified']);
		self::assertSame(['LDAP:deleted.vcf'], $changes['deleted']);
	}

	#[DataProvider('federationGroupRestrictionProvider')]
	public function testGetChildForFederationForbiddenWithGroupRestrictions(bool $enumeration, bool $group, bool $phone, bool $ownGroups): void {
		$this->mockFederationWithRestrictions($enumeration, $group, $phone, $ownGroups);
		$this->cardDavBackend->expects(self::never())
			->method('getCard');
		$this->expectException(Forbidden::class);

		$this->addressBook->getChild('LDAP:user.vcf');
	}

	#[DataProvider('federationGroupRestrictionProvider')]
	public function testGetMultipleChildrenForFederationEmptyWithGroupRestrictions(bool $enumeration, bool $group, bool $phone, bool $ownGroups): void {
		$this->mockFederationWithRestrictions($enumeration, $group, $phone, $ownGroups);
		$this->cardDavBackend->expects(self::never())
			->method('getMultipleCards');

		$cards = $this->addressBook->getMultipleChildren(['LDAP:user.vcf']);

		self::assertSame([], $cards);
	}

	#[DataProvider('federationGroupRestrictionProvider')]
	public function testGetChangesForFederationReportsDeletedWithGroupRestrictions(bool $enumeration, bool $group, bool $phone, bool $ownGroups): void {
		$this->mockFederationWithRestrictions($enumeration, $group, $phone, $ownGroups);
		$backend = $this->createMock(CardDavBackend::class);
		$backend->method('getChangesForAddressBook')
			->willReturn([
				'syncToken' => 2,
				'added' => ['LDAP:added.vcf'],
				'modified' => ['LDAP:modified.vcf'],
				'deleted' => ['LDAP:deleted.vcf'],
			]);
		$backend->expects(self::never())
			->method('getCard');
		$addressBook = new SystemAddressbook(
			$backend,
			$this->addressBookInfo,
			$this->l10n,
			$this->shareManager,
			$this->userSession,
			$this->request,
			$this->trustedServers,
			$this->groupManager,
		);

		$changes = $addressBook->getChanges(1, 1);

		self::assertSame([], $changes['added']);
		self::assertSame([], $changes['modified']);
		self::assertSame(['LDAP:deleted.vcf', 'LDAP:added.vcf', 'LDAP:modified.vcf'], $changes['deleted']);
	}

	public function testGetChildWithoutEnumeration(): void {
		$this->mockShareSettings(false, false, false, false);
		$this->expectException(Forbidden::class);

		$this->addressBook->getChild('LDAP:user.vcf');
	}

	public function testGetChildAsGuest(): void {
		$this->mockShareSettings(true, false, false, false);
		$user = $this->createMock(IUser::class);
		$user->method('getBackendClassName')->willReturn('Guests');
		$this->userSession->expects(self::once())
			->method('getUser')
			->willReturn($user);
		$this->expectException(Forbidden::class);

		$this->addressBook->getChild('LDAP:user.vcf');
	}

	public function testGetChildWithGroupEnumerationRestriction(): void {
		$this->mockShareSettings(true, true, false, false);
		$user = $this->createMock(IUser::class);
		$user->method('getBackendClassName')->willReturn('LDAP');
		$this->userSession->expects(self::once())
			->method('getUser')
			->willReturn($user);
		$otherUser = $this->createMock(IUser::class);
		$user->method('getBackendClassName')->willReturn('LDAP');
		$otherUser->method('getUID')->willReturn('other');
		$group = $this->createMock(IGroup::class);
		$group->method('getGID')->willReturn('sales');
		$this->groupManager->expects(self::once())
			->method('isInGroup')
			->with('other', 'sales')
			->willReturn(true);
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

	public function testGetChildWithOwnGroupsOnlyRestriction(): void {
		$this->mockShareSettings(true, false, false, true);
		$user = $this->createMock(IUser::class);
		$user->method('getBackendClassName')->willReturn('LDAP');
		$this->userSession->expects(self::once())
			->method('getUser')
			->willReturn($user);
		$otherUser = $this->createMock(IUser::class);
		$otherUser->method('getUID')->willReturn('other');
		$otherUser->method('getBackendClassName')->willReturn('LDAP');
		$group = $this->createMock(IGroup::class);
		$group->method('getGID')->willReturn('sales');
		$this->groupManager->expects(self::once())
			->method('isInGroup')
			->with('other', 'sales')
			->willReturn(true);
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

	public function testGetChildForbiddenWithOwnGroupsOnlyRestriction(): void {
		$this->mockShareSettings(true, false, false, true);
		$user = $this->createMock(IUser::class);
		$user->method('getBackendClassName')->willReturn('LDAP');
		$this->userSession->expects(self::once())
			->method('getUser')
			->willReturn($user);
		$this->groupManager->expects(self::once())
			->method('getUserGroups')
			->with($user)
			->willReturn([]);
		$this->expectException(Forbidden::class);

		$this->addressBook->getChild('LDAP:other.vcf');
	}

	public function testGetChildForbiddenThroughExcludedGroupWithOwnGroupsOnlyRestriction(): void {
		$this->mockShareSettings(true, false, false, true, ['everyone']);
		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('user');
		$user->method('getBackendClassName')->willReturn('LDAP');
		$this->userSession->expects(self::once())
			->method('getUser')
			->willReturn($user);
		$excludedGroup = $this->createMock(IGroup::class);
		$excludedGroup->method('getGID')->willReturn('everyone');
		$this->groupManager->expects(self::never())
			->method('isInGroup');
		$this->groupManager->expects(self::once())
			->method('getUserGroups')
			->with($user)
			->willReturn([$excludedGroup]);
		$this->cardDavBackend->expects(self::never())
			->method('getCard');
		$this->expectException(Forbidden::class);

		$this->addressBook->getChild('LDAP:other.vcf');
	}

	public function testGetChildWithGroupEnumerationAndOwnGroupsOnlyRestrictionIgnoresExcludedGroup(): void {
		$this->mockShareSettings(true, true, false, true, ['everyone']);
		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('user');
		$user->method('getBackendClassName')->willReturn('LDAP');
		$this->userSession->expects(self::once())
			->method('getUser')
			->willReturn($user);
		$other = $this->createMock(IUser::class);
		$other->method('getUID')->willReturn('other');
		$other->method('getBackendClassName')->willReturn('LDAP');
		$excludedGroup = $this->createMock(IGroup::class);
		$excludedGroup->method('getGID')->willReturn('everyone');
		$this->groupManager->expects(self::never())
			->method('isInGroup');
		$this->groupManager->expects(self::once())
			->method('getUserGroups')
			->with($user)
			->willReturn([$excludedGroup]);
		$this->cardDavBackend->expects(self::never())
			->method('getCard');
		$this->expectException(Forbidden::class);

		$this->addressBook->getChild(SyncService::getCardUri($other));
	}

	public function testGetChildDoesNotCheckGroupsForOwnCard(): void {
		$this->mockShareSettings(true, false, false, true);
		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('user');
		$user->method('getBackendClassName')->willReturn('LDAP');
		$this->userSession->method('getUser')->willReturn($user);
		$this->groupManager->expects(self::never())->method('isInGroup');
		$this->cardDavBackend->expects(self::once())
			->method('getCard')
			->with(123, 'LDAP:user.vcf')
			->willReturn($this->getFederatedCard());

		$this->addressBook->getChild('LDAP:user.vcf');
	}

	public static function inaccessibleCardUriProvider(): array {
		return [
			'guest' => ['Guests:other.vcf'],
			'no backend separator' => ['other.vcf'],
			'not a card' => ['LDAP:other.txt'],
		];
	}

	#[DataProvider('inaccessibleCardUriProvider')]
	public function testGetChildForbiddenForInaccessibleCardUri(string $cardUri): void {
		$this->mockShareSettings(true, false, false, true);
		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('user');
		$user->method('getBackendClassName')->willReturn('LDAP');
		$this->userSession->method('getUser')->willReturn($user);
		$this->groupManager->method('isInGroup')->willReturn(true);
		$group = $this->createMock(IGroup::class);
		$group->method('getGID')->willReturn('sales');
		$this->groupManager->method('getUserGroups')->willReturn([$group]);
		$this->cardDavBackend->expects(self::never())->method('getCard');
		$this->expectException(Forbidden::class);

		$this->addressBook->getChild($cardUri);
	}

	#[DataProvider('inaccessibleCardUriProvider')]
	public function testGetMultipleChildrenOmitsInaccessibleCardUri(string $cardUri): void {
		$this->mockShareSettings(true, false, false, true);
		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('user');
		$user->method('getBackendClassName')->willReturn('LDAP');
		$this->userSession->method('getUser')->willReturn($user);
		$group = $this->createMock(IGroup::class);
		$group->method('searchUsers')->willReturn(['other' => $this->createMock(IUser::class)]);
		$this->groupManager->method('getUserGroups')->willReturn([$group]);
		$this->cardDavBackend->expects(self::once())
			->method('getMultipleCards')
			->with(123, [])
			->willReturn([]);

		self::assertSame([], $this->addressBook->getMultipleChildren([$cardUri]));
	}

	public static function memberCardUriProvider(): array {
		return [
			'numeric user id' => ['Database:123.vcf', '123'],
			'user id with separator' => ['Database:a:b.vcf', 'a:b'],
		];
	}

	#[DataProvider('memberCardUriProvider')]
	public function testGetMultipleChildrenMatchesUserIdFromCardUri(string $cardUri, string $uid): void {
		$this->mockShareSettings(true, false, false, true);
		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('user');
		$user->method('getBackendClassName')->willReturn('LDAP');
		$this->userSession->method('getUser')->willReturn($user);
		$group = $this->createMock(IGroup::class);
		$group->method('searchUsers')->with('')->willReturn([$uid => $this->createMock(IUser::class)]);
		$this->groupManager->method('getUserGroups')->willReturn([$group]);
		$this->cardDavBackend->expects(self::once())
			->method('getMultipleCards')
			->with(123, [$cardUri])
			->willReturn([$this->getFederatedCard()]);

		self::assertCount(1, $this->addressBook->getMultipleChildren([$cardUri]));
	}

	#[DataProvider('memberCardUriProvider')]
	public function testGetChildMatchesUserIdFromCardUri(string $cardUri, string $uid): void {
		$this->mockShareSettings(true, false, false, true);
		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('user');
		$user->method('getBackendClassName')->willReturn('LDAP');
		$this->userSession->method('getUser')->willReturn($user);
		$group = $this->createMock(IGroup::class);
		$group->method('getGID')->willReturn('sales');
		$this->groupManager->method('getUserGroups')->willReturn([$group]);
		$this->groupManager->expects(self::once())
			->method('isInGroup')
			->with($uid, 'sales')
			->willReturn(true);
		$this->cardDavBackend->expects(self::once())
			->method('getCard')
			->with(123, $cardUri)
			->willReturn($this->getFederatedCard());

		$this->addressBook->getChild($cardUri);
	}

	public function testGetChildWithPhoneNumberEnumerationRestriction(): void {
		$this->mockShareSettings(true, false, true, false);
		$user = $this->createMock(IUser::class);
		$user->method('getBackendClassName')->willReturn('LDAP');
		$this->userSession->expects(self::once())
			->method('getUser')
			->willReturn($user);
		$this->expectException(Forbidden::class);

		$this->addressBook->getChild('LDAP:user.vcf');
	}

	public function testGetOwnChildWithPhoneNumberEnumerationRestriction(): void {
		$this->mockShareSettings(true, false, true, false);
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
		$this->mockShareSettings(true, true, false, false);
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
			->method('searchUsers')
			->with('')
			->willReturn(['user' => $user, 'other1' => $other1]);
		$group2 = $this->createMock(IGroup::class);
		$group2
			->method('searchUsers')
			->with('')
			->willReturn(['other1' => $other1, 'other2' => $other2, 'user' => $user]);
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

	public function testGetMultipleChildrenWithOwnGroupsOnlyRestriction(): void {
		$this->mockShareSettings(true, false, false, true);
		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('user');
		$user->method('getBackendClassName')->willReturn('LDAP');
		$other1 = $this->createMock(IUser::class);
		$other1->method('getUID')->willReturn('other1');
		$other1->method('getBackendClassName')->willReturn('LDAP');
		$other2 = $this->createMock(IUser::class);
		$other2->method('getUID')->willReturn('other2');
		$other2->method('getBackendClassName')->willReturn('LDAP');
		$this->userSession
			->method('getUser')
			->willReturn($user);
		$group1 = $this->createMock(IGroup::class);
		$group1
			->method('searchUsers')
			->with('')
			->willReturn(['user' => $user, 'other1' => $other1]);
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
			SyncService::getCardUri($other2), // No overlapping group with this one
		]);

		self::assertCount(2, $cards);
	}

	public function testGetMultipleChildrenWithOwnGroupsOnlyRestrictionIgnoresExcludedGroup(): void {
		$this->mockShareSettings(true, false, false, true, ['everyone']);
		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('user');
		$user->method('getBackendClassName')->willReturn('LDAP');
		$colleague = $this->createMock(IUser::class);
		$colleague->method('getUID')->willReturn('colleague');
		$colleague->method('getBackendClassName')->willReturn('LDAP');
		$stranger = $this->createMock(IUser::class);
		$stranger->method('getUID')->willReturn('stranger');
		$stranger->method('getBackendClassName')->willReturn('LDAP');
		$this->userSession
			->method('getUser')
			->willReturn($user);
		$excludedGroup = $this->createMock(IGroup::class);
		$excludedGroup->method('getGID')->willReturn('everyone');
		$excludedGroup->expects(self::never())->method('searchUsers');
		$sharedGroup = $this->createMock(IGroup::class);
		$sharedGroup->method('getGID')->willReturn('sales');
		$sharedGroup->method('searchUsers')->with('')->willReturn(['user' => $user, 'colleague' => $colleague]);
		$this->groupManager
			->method('getUserGroups')
			->with($user)
			->willReturn([$excludedGroup, $sharedGroup]);
		$this->cardDavBackend->expects(self::once())
			->method('getMultipleCards')
			->with($this->addressBookInfo['id'], [
				SyncService::getCardUri($user),
				SyncService::getCardUri($colleague),
			])
			->willReturn([
				[],
				[],
			]);

		$cards = $this->addressBook->getMultipleChildren([
			SyncService::getCardUri($user),
			SyncService::getCardUri($colleague),
			SyncService::getCardUri($stranger), // Only shares the excluded group
		]);

		self::assertCount(2, $cards);
	}

	public function testGetChildrenWithOwnGroupsOnlyRestrictionKeepsOwnCardWhenAllGroupsExcluded(): void {
		$this->mockShareSettings(true, false, false, true, ['everyone']);
		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('user');
		$user->method('getBackendClassName')->willReturn('LDAP');
		$this->userSession
			->method('getUser')
			->willReturn($user);
		$excludedGroup = $this->createMock(IGroup::class);
		$excludedGroup->method('getGID')->willReturn('everyone');
		$this->groupManager
			->method('getUserGroups')
			->with($user)
			->willReturn([$excludedGroup]);
		$this->cardDavBackend->expects(self::once())
			->method('getMultipleCards')
			->with($this->addressBookInfo['id'], [SyncService::getCardUri($user)])
			->willReturn([[]]);

		$cards = $this->addressBook->getChildren();

		self::assertCount(1, $cards);
	}

	public function testGetChildrenWithOwnGroupsOnlyRestriction(): void {
		$this->mockShareSettings(true, false, false, true);
		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('user');
		$user->method('getBackendClassName')->willReturn('LDAP');
		$other1 = $this->createMock(IUser::class);
		$other1->method('getUID')->willReturn('other1');
		$other1->method('getBackendClassName')->willReturn('LDAP');
		$this->userSession
			->method('getUser')
			->willReturn($user);
		$group1 = $this->createMock(IGroup::class);
		$group1
			->method('getUsers')
			->willReturn([$user, $other1]);
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

		$cards = $this->addressBook->getChildren();

		self::assertCount(2, $cards);
	}

	public function testGetMultipleChildrenAsGuest(): void {
		$this->mockShareSettings(true, false, false, false);
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
		$this->mockFederatedRequest();
		$this->mockShareSettings(true, false, false, false);
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
