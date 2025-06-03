<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\Tests\unit\Provisioning\Apple;

use OCA\DAV\Provisioning\Apple\AppleProvisioningPlugin;
use OCA\Theming\ThemingDefaults;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\MockObject\MockObject;
use Sabre\DAV\Server;
use Sabre\HTTP\RequestInterface;
use Sabre\HTTP\ResponseInterface;
use Test\TestCase;

class AppleProvisioningPluginTest extends TestCase {
	protected Server&MockObject $server;
	protected IUserSession&MockObject $userSession;
	protected IURLGenerator&MockObject $urlGenerator;
	protected ThemingDefaults&MockObject $themingDefaults;
	protected IRequest&MockObject $request;
	protected IL10N&MockObject $l10n;
	protected RequestInterface&MockObject $sabreRequest;
	protected ResponseInterface&MockObject $sabreResponse;
	protected AppleProvisioningPlugin $plugin;

	protected function setUp(): void {
		parent::setUp();

		$this->server = $this->createMock(Server::class);
		$this->userSession = $this->createMock(IUserSession::class);
		$this->urlGenerator = $this->createMock(IURLGenerator::class);
		$this->themingDefaults = $this->createMock(ThemingDefaults::class);
		$this->request = $this->createMock(IRequest::class);
		$this->l10n = $this->createMock(IL10N::class);

		$this->plugin = new AppleProvisioningPlugin($this->userSession,
			$this->urlGenerator,
			$this->themingDefaults,
			$this->request,
			$this->l10n,
			function () {
				return 'generated-uuid';
			}
		);

		$this->sabreRequest = $this->createMock(RequestInterface::class);
		$this->sabreResponse = $this->createMock(ResponseInterface::class);
	}

	public function testInitialize(): void {
		$server = $this->createMock(Server::class);

		$plugin = new AppleProvisioningPlugin($this->userSession,
			$this->urlGenerator, $this->themingDefaults, $this->request, $this->l10n,
			function (): void {
			});

		$server->expects($this->once())
			->method('on')
			->with('method:GET', [$plugin, 'httpGet'], 90);

		$plugin->initialize($server);
	}

	public function testHttpGetOnHttp(): void {
		$this->sabreRequest->expects($this->once())
			->method('getPath')
			->with()
			->willReturn('provisioning/apple-provisioning.mobileconfig');

		$user = $this->createMock(IUser::class);
		$this->userSession->expects($this->once())
			->method('getUser')
			->willReturn($user);

		$this->request->expects($this->once())
			->method('getServerProtocol')
			->wilLReturn('http');

		$this->themingDefaults->expects($this->once())
			->method('getName')
			->willReturn('InstanceName');

		$this->l10n->expects($this->once())
			->method('t')
			->with('Your %s needs to be configured to use HTTPS in order to use CalDAV and CardDAV with iOS/macOS.', ['InstanceName'])
			->willReturn('LocalizedErrorMessage');

		$this->sabreResponse->expects($this->once())
			->method('setStatus')
			->with(200);
		$this->sabreResponse->expects($this->once())
			->method('setHeader')
			->with('Content-Type', 'text/plain; charset=utf-8');
		$this->sabreResponse->expects($this->once())
			->method('setBody')
			->with('LocalizedErrorMessage');

		$returnValue = $this->plugin->httpGet($this->sabreRequest, $this->sabreResponse);

		$this->assertFalse($returnValue);
	}

	public function testHttpGetOnHttps(): void {
		$this->sabreRequest->expects($this->once())
			->method('getPath')
			->with()
			->willReturn('provisioning/apple-provisioning.mobileconfig');

		$user = $this->createMock(IUser::class);
		$user->expects($this->once())
			->method('getUID')
			->willReturn('userName');

		$this->userSession->expects($this->once())
			->method('getUser')
			->willReturn($user);

		$this->request->expects($this->once())
			->method('getServerProtocol')
			->wilLReturn('https');

		$this->urlGenerator->expects($this->once())
			->method('getBaseUrl')
			->willReturn('https://nextcloud.tld/nextcloud');

		$this->themingDefaults->expects($this->once())
			->method('getName')
			->willReturn('InstanceName');

		$this->l10n->expects($this->exactly(2))
			->method('t')
			->willReturnMap([
				['Configures a CalDAV account', [], 'LocalizedConfiguresCalDAV'],
				['Configures a CardDAV account', [], 'LocalizedConfiguresCardDAV'],
			]);

		$this->sabreResponse->expects($this->once())
			->method('setStatus')
			->with(200);

		$calls = [
			['Content-Disposition', 'attachment; filename="userName-apple-provisioning.mobileconfig"'],
			['Content-Type', 'application/xml; charset=utf-8'],
		];
		$this->sabreResponse->expects($this->exactly(2))
			->method('setHeader')
			->willReturnCallback(function () use (&$calls) {
				$expected = array_shift($calls);
				$this->assertEquals($expected, func_get_args());
			});
		$this->sabreResponse->expects($this->once())
			->method('setBody')
			->with(<<<EOF
<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE plist PUBLIC "-//Apple//DTD PLIST 1.0//EN" "http://www.apple.com/DTDs/PropertyList-1.0.dtd">
<plist version="1.0">
<dict>
	<key>PayloadContent</key>
	<array>
		<dict>
			<key>CalDAVAccountDescription</key>
			<string>InstanceName</string>
			<key>CalDAVHostName</key>
			<string>nextcloud.tld</string>
			<key>CalDAVUsername</key>
			<string>userName</string>
			<key>CalDAVUseSSL</key>
			<true/>
			<key>CalDAVPort</key>
			<integer>443</integer>
			<key>PayloadDescription</key>
			<string>LocalizedConfiguresCalDAV</string>
			<key>PayloadDisplayName</key>
			<string>InstanceName CalDAV</string>
			<key>PayloadIdentifier</key>
			<string>tld.nextcloud.generated-uuid</string>
			<key>PayloadType</key>
			<string>com.apple.caldav.account</string>
			<key>PayloadUUID</key>
			<string>generated-uuid</string>
			<key>PayloadVersion</key>
			<integer>1</integer>
		</dict>
		<dict>
			<key>CardDAVAccountDescription</key>
			<string>InstanceName</string>
			<key>CardDAVHostName</key>
			<string>nextcloud.tld</string>
			<key>CardDAVUsername</key>
			<string>userName</string>
			<key>CardDAVUseSSL</key>
			<true/>
			<key>CardDAVPort</key>
			<integer>443</integer>
			<key>PayloadDescription</key>
			<string>LocalizedConfiguresCardDAV</string>
			<key>PayloadDisplayName</key>
			<string>InstanceName CardDAV</string>
			<key>PayloadIdentifier</key>
			<string>tld.nextcloud.generated-uuid</string>
			<key>PayloadType</key>
			<string>com.apple.carddav.account</string>
			<key>PayloadUUID</key>
			<string>generated-uuid</string>
			<key>PayloadVersion</key>
			<integer>1</integer>
		</dict>
	</array>
	<key>PayloadDisplayName</key>
	<string>InstanceName</string>
	<key>PayloadIdentifier</key>
	<string>tld.nextcloud.generated-uuid</string>
	<key>PayloadRemovalDisallowed</key>
	<false/>
	<key>PayloadType</key>
	<string>Configuration</string>
	<key>PayloadUUID</key>
	<string>generated-uuid</string>
	<key>PayloadVersion</key>
	<integer>1</integer>
</dict>
</plist>

EOF
			);

		$returnValue = $this->plugin->httpGet($this->sabreRequest, $this->sabreResponse);

		$this->assertFalse($returnValue);
	}
}
