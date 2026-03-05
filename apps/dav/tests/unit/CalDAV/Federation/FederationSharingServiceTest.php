<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\Tests\unit\CalDAV\Federation;

use OCA\DAV\CalDAV\Calendar;
use OCA\DAV\CalDAV\Federation\FederationSharingService;
use OCA\DAV\DAV\Sharing\IShareable;
use OCA\DAV\DAV\Sharing\SharingMapper;
use OCP\Federation\ICloudFederationFactory;
use OCP\Federation\ICloudFederationProviderManager;
use OCP\Federation\ICloudFederationShare;
use OCP\Http\Client\IResponse;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\IUserManager;
use OCP\OCM\Exceptions\OCMProviderException;
use OCP\Security\ISecureRandom;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Sabre\CalDAV\Xml\Property\SupportedCalendarComponentSet;
use Test\TestCase;

class FederationSharingServiceTest extends TestCase {
	private FederationSharingService $federationSharingService;

	private readonly ICloudFederationProviderManager&MockObject $federationManager;
	private readonly ICloudFederationFactory&MockObject $federationFactory;
	private readonly IUserManager&MockObject $userManager;
	private readonly IURLGenerator&MockObject $url;
	private readonly LoggerInterface&MockObject $logger;
	private readonly ISecureRandom&MockObject $random;
	private readonly SharingMapper&MockObject $sharingMapper;

	protected function setUp(): void {
		parent::setUp();

		$this->federationManager = $this->createMock(ICloudFederationProviderManager::class);
		$this->federationFactory = $this->createMock(ICloudFederationFactory::class);
		$this->userManager = $this->createMock(IUserManager::class);
		$this->url = $this->createMock(IURLGenerator::class);
		$this->logger = $this->createMock(LoggerInterface::class);
		$this->random = $this->createMock(ISecureRandom::class);
		$this->sharingMapper = $this->createMock(SharingMapper::class);

		$this->federationSharingService = new FederationSharingService(
			$this->federationManager,
			$this->federationFactory,
			$this->userManager,
			$this->url,
			$this->logger,
			$this->random,
			$this->sharingMapper,
		);
	}

	public function testShareWith(): void {
		$shareable = $this->createMock(Calendar::class);
		$shareable->method('getOwner')
			->willReturn('principals/users/host1');
		$shareable->method('getName')
			->willReturn('cal1');
		$shareable->method('getResourceId')
			->willReturn(10);
		$shareable->method('getProperties')
			->willReturnCallback(static fn (array $props) => match ($props[0]) {
				'{DAV:}displayname' => ['{DAV:}displayname' => 'Calendar 1'],
				'{http://apple.com/ns/ical/}calendar-color' => [
					'{http://apple.com/ns/ical/}calendar-color' => '#ff0000',
				],
				'{urn:ietf:params:xml:ns:caldav}supported-calendar-component-set' => [
					'{urn:ietf:params:xml:ns:caldav}supported-calendar-component-set' => new SupportedCalendarComponentSet([
						'VEVENT',
						'VTODO',
					]),
				]
			});

		$hostUser = $this->createMock(IUser::class);
		$hostUser->method('getCloudId')
			->willReturn('host1@nextcloud.host');
		$hostUser->method('getDisplayName')
			->willReturn('Host 1');
		$hostUser->method('getUID')
			->willReturn('host1');
		$this->userManager->expects(self::once())
			->method('get')
			->with('host1')
			->willReturn($hostUser);

		$this->random->expects(self::once())
			->method('generate')
			->with(32)
			->willReturn('token');

		$share = $this->createMock(ICloudFederationShare::class);
		$share->expects(self::once())
			->method('getProtocol')
			->willReturn([
				'preservedValue1' => 'foobar',
				'preservedValue2' => 'baz',
			]);
		$share->expects(self::once())
			->method('setProtocol')
			->with([
				'preservedValue1' => 'foobar',
				'preservedValue2' => 'baz',
				'version' => 'v1',
				'url' => 'https://nextcloud.host/remote.php/dav/remote-calendars/cmVtb3RlMUBuZXh0Y2xvdWQucmVtb3Rl/cal1_shared_by_host1',
				'displayName' => 'Calendar 1',
				'color' => '#ff0000',
				'access' => 3,
				'components' => 'VEVENT,VTODO',
			]);
		$this->federationFactory->expects(self::once())
			->method('getCloudFederationShare')
			->with(
				'remote1@nextcloud.remote',
				'cal1',
				'Calendar 1',
				'calendar',
				'host1@nextcloud.host',
				'Host 1',
				'host1@nextcloud.host',
				'Host 1',
				'token',
				'user',
				'calendar',
			)
			->willReturn($share);

		$this->url->expects(self::once())
			->method('linkTo')
			->with('', 'remote.php')
			->willReturn('/remote.php');
		$this->url->expects(self::once())
			->method('getAbsoluteURL')
			->with('/remote.php/dav/remote-calendars/cmVtb3RlMUBuZXh0Y2xvdWQucmVtb3Rl/cal1_shared_by_host1')
			->willReturn('https://nextcloud.host/remote.php/dav/remote-calendars/cmVtb3RlMUBuZXh0Y2xvdWQucmVtb3Rl/cal1_shared_by_host1');

		$response = $this->createMock(IResponse::class);
		$response->method('getStatusCode')
			->willReturn(201);
		$this->federationManager->expects(self::once())
			->method('sendCloudShare')
			->with($share)
			->willReturn($response);

		$this->sharingMapper->expects(self::once())
			->method('deleteShare')
			->with(10, 'calendar', 'principals/remote-users/cmVtb3RlMUBuZXh0Y2xvdWQucmVtb3Rl');
		$this->sharingMapper->expects(self::once())
			->method('shareWithToken')
			->with(
				10,
				'calendar',
				3,
				'principals/remote-users/cmVtb3RlMUBuZXh0Y2xvdWQucmVtb3Rl',
				'token',
			);

		$this->federationSharingService->shareWith(
			$shareable,
			'principals/remote-users/cmVtb3RlMUBuZXh0Y2xvdWQucmVtb3Rl',
			3, // Read-only
		);
	}

	public function testShareWithWithFailingFederationManager(): void {
		$shareable = $this->createMock(Calendar::class);
		$shareable->method('getOwner')
			->willReturn('principals/users/host1');
		$shareable->method('getName')
			->willReturn('cal1');
		$shareable->method('getResourceId')
			->willReturn(10);
		$shareable->method('getProperties')
			->willReturnCallback(static fn (array $props) => match ($props[0]) {
				'{DAV:}displayname' => ['{DAV:}displayname' => 'Calendar 1'],
				'{http://apple.com/ns/ical/}calendar-color' => [
					'{http://apple.com/ns/ical/}calendar-color' => '#ff0000',
				],
				'{urn:ietf:params:xml:ns:caldav}supported-calendar-component-set' => [
					'{urn:ietf:params:xml:ns:caldav}supported-calendar-component-set' => new SupportedCalendarComponentSet([
						'VEVENT',
						'VTODO',
					]),
				]
			});

		$hostUser = $this->createMock(IUser::class);
		$hostUser->method('getCloudId')
			->willReturn('host1@nextcloud.host');
		$hostUser->method('getDisplayName')
			->willReturn('Host 1');
		$hostUser->method('getUID')
			->willReturn('host1');
		$this->userManager->expects(self::once())
			->method('get')
			->with('host1')
			->willReturn($hostUser);

		$this->random->expects(self::once())
			->method('generate')
			->with(32)
			->willReturn('token');

		$share = $this->createMock(ICloudFederationShare::class);
		$share->expects(self::once())
			->method('getProtocol')
			->willReturn([
				'preservedValue1' => 'foobar',
				'preservedValue2' => 'baz',
			]);
		$share->expects(self::once())
			->method('setProtocol')
			->with([
				'preservedValue1' => 'foobar',
				'preservedValue2' => 'baz',
				'version' => 'v1',
				'url' => 'https://nextcloud.host/remote.php/dav/remote-calendars/cmVtb3RlMUBuZXh0Y2xvdWQucmVtb3Rl/cal1_shared_by_host1',
				'displayName' => 'Calendar 1',
				'color' => '#ff0000',
				'access' => 3,
				'components' => 'VEVENT,VTODO',
			]);
		$this->federationFactory->expects(self::once())
			->method('getCloudFederationShare')
			->with(
				'remote1@nextcloud.remote',
				'cal1',
				'Calendar 1',
				'calendar',
				'host1@nextcloud.host',
				'Host 1',
				'host1@nextcloud.host',
				'Host 1',
				'token',
				'user',
				'calendar',
			)
			->willReturn($share);

		$this->url->expects(self::once())
			->method('linkTo')
			->with('', 'remote.php')
			->willReturn('/remote.php');
		$this->url->expects(self::once())
			->method('getAbsoluteURL')
			->with('/remote.php/dav/remote-calendars/cmVtb3RlMUBuZXh0Y2xvdWQucmVtb3Rl/cal1_shared_by_host1')
			->willReturn('https://nextcloud.host/remote.php/dav/remote-calendars/cmVtb3RlMUBuZXh0Y2xvdWQucmVtb3Rl/cal1_shared_by_host1');

		$response = $this->createMock(IResponse::class);
		$response->method('getStatusCode')
			->willReturn(201);
		$this->federationManager->expects(self::once())
			->method('sendCloudShare')
			->with($share)
			->willThrowException(new OCMProviderException());

		$this->sharingMapper->expects(self::never())
			->method('deleteShare');
		$this->sharingMapper->expects(self::never())
			->method('shareWithToken');

		$this->federationSharingService->shareWith(
			$shareable,
			'principals/remote-users/cmVtb3RlMUBuZXh0Y2xvdWQucmVtb3Rl',
			3, // Read-only
		);
	}

	public function testShareWithWithUnsuccessfulResponse(): void {
		$shareable = $this->createMock(Calendar::class);
		$shareable->method('getOwner')
			->willReturn('principals/users/host1');
		$shareable->method('getName')
			->willReturn('cal1');
		$shareable->method('getResourceId')
			->willReturn(10);
		$shareable->method('getProperties')
			->willReturnCallback(static fn (array $props) => match ($props[0]) {
				'{DAV:}displayname' => ['{DAV:}displayname' => 'Calendar 1'],
				'{http://apple.com/ns/ical/}calendar-color' => [
					'{http://apple.com/ns/ical/}calendar-color' => '#ff0000',
				],
				'{urn:ietf:params:xml:ns:caldav}supported-calendar-component-set' => [
					'{urn:ietf:params:xml:ns:caldav}supported-calendar-component-set' => new SupportedCalendarComponentSet([
						'VEVENT',
						'VTODO',
					]),
				]
			});

		$hostUser = $this->createMock(IUser::class);
		$hostUser->method('getCloudId')
			->willReturn('host1@nextcloud.host');
		$hostUser->method('getDisplayName')
			->willReturn('Host 1');
		$hostUser->method('getUID')
			->willReturn('host1');
		$this->userManager->expects(self::once())
			->method('get')
			->with('host1')
			->willReturn($hostUser);

		$this->random->expects(self::once())
			->method('generate')
			->with(32)
			->willReturn('token');

		$share = $this->createMock(ICloudFederationShare::class);
		$share->expects(self::once())
			->method('getProtocol')
			->willReturn([
				'preservedValue1' => 'foobar',
				'preservedValue2' => 'baz',
			]);
		$share->expects(self::once())
			->method('setProtocol')
			->with([
				'preservedValue1' => 'foobar',
				'preservedValue2' => 'baz',
				'version' => 'v1',
				'url' => 'https://nextcloud.host/remote.php/dav/remote-calendars/cmVtb3RlMUBuZXh0Y2xvdWQucmVtb3Rl/cal1_shared_by_host1',
				'displayName' => 'Calendar 1',
				'color' => '#ff0000',
				'access' => 3,
				'components' => 'VEVENT,VTODO',
			]);
		$this->federationFactory->expects(self::once())
			->method('getCloudFederationShare')
			->with(
				'remote1@nextcloud.remote',
				'cal1',
				'Calendar 1',
				'calendar',
				'host1@nextcloud.host',
				'Host 1',
				'host1@nextcloud.host',
				'Host 1',
				'token',
				'user',
				'calendar',
			)
			->willReturn($share);

		$this->url->expects(self::once())
			->method('linkTo')
			->with('', 'remote.php')
			->willReturn('/remote.php');
		$this->url->expects(self::once())
			->method('getAbsoluteURL')
			->with('/remote.php/dav/remote-calendars/cmVtb3RlMUBuZXh0Y2xvdWQucmVtb3Rl/cal1_shared_by_host1')
			->willReturn('https://nextcloud.host/remote.php/dav/remote-calendars/cmVtb3RlMUBuZXh0Y2xvdWQucmVtb3Rl/cal1_shared_by_host1');

		$response = $this->createMock(IResponse::class);
		$response->method('getStatusCode')
			->willReturn(400);
		$this->federationManager->expects(self::once())
			->method('sendCloudShare')
			->with($share)
			->willReturn($response);

		$this->sharingMapper->expects(self::never())
			->method('deleteShare');
		$this->sharingMapper->expects(self::never())
			->method('shareWithToken');

		$this->federationSharingService->shareWith(
			$shareable,
			'principals/remote-users/cmVtb3RlMUBuZXh0Y2xvdWQucmVtb3Rl',
			3, // Read-only
		);
	}

	public static function provideInvalidRemoteUserPrincipalData(): array {
		return [
			['principals/users/foobar'],
			['remote-users/remote1'],
			['foobar/remote-users/remote1'],
			['principals/remote-groups/group1'],
		];
	}

	#[DataProvider(methodName: 'provideInvalidRemoteUserPrincipalData')]
	public function testShareWithWithInvalidRemoteUserPrincipal(string $remoteUserPrincipal): void {
		$shareable = $this->createMock(Calendar::class);
		$shareable->method('getOwner')
			->willReturn('principals/users/host1');

		$this->userManager->expects(self::never())
			->method('get');

		$this->federationManager->expects(self::never())
			->method('sendCloudShare');
		$this->sharingMapper->expects(self::never())
			->method('deleteShare');
		$this->sharingMapper->expects(self::never())
			->method('shareWithToken');

		$this->federationSharingService->shareWith(
			$shareable,
			$remoteUserPrincipal,
			3, // Read-only
		);
	}

	public function testShareWithWithUnknownUser(): void {
		$shareable = $this->createMock(Calendar::class);
		$shareable->method('getOwner')
			->willReturn('principals/users/host1');

		$this->userManager->expects(self::once())
			->method('get')
			->with('host1')
			->willReturn(null);

		$this->federationManager->expects(self::never())
			->method('sendCloudShare');
		$this->sharingMapper->expects(self::never())
			->method('deleteShare');
		$this->sharingMapper->expects(self::never())
			->method('shareWithToken');

		$this->federationSharingService->shareWith(
			$shareable,
			'principals/remote-users/cmVtb3RlMUBuZXh0Y2xvdWQucmVtb3Rl',
			3, // Read-only
		);
	}

	public function testShareWithWithInvalidShareable(): void {
		$shareable = $this->createMock(IShareable::class);
		$shareable->method('getOwner')
			->willReturn('principals/users/host1');

		$this->userManager->expects(self::once())
			->method('get')
			->with('host1')
			->willReturn(null);

		$this->federationManager->expects(self::never())
			->method('sendCloudShare');
		$this->sharingMapper->expects(self::never())
			->method('deleteShare');
		$this->sharingMapper->expects(self::never())
			->method('shareWithToken');

		$this->federationSharingService->shareWith(
			$shareable,
			'principals/remote-users/cmVtb3RlMUBuZXh0Y2xvdWQucmVtb3Rl',
			3, // Read-only
		);
	}
}
