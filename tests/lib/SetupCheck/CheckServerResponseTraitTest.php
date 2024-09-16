<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace Test\SetupCheck;

use OCP\Http\Client\IClientService;
use OCP\IConfig;
use OCP\IL10N;
use OCP\IURLGenerator;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Test\TestCase;

class CheckServerResponseTraitTest extends TestCase {

	protected const BASE_URL = 'https://nextcloud.local';
	
	private IL10N&MockObject $l10n;
	private IConfig&MockObject $config;
	private IURLGenerator&MockObject $urlGenerator;
	private IClientService&MockObject $clientService;
	private LoggerInterface&MockObject $logger;

	private CheckServerResponseTraitImplementation $trait;

	protected function setUp(): void {
		parent::setUp();

		$this->l10n = $this->createMock(IL10N::class);
		$this->l10n->method('t')
			->willReturnArgument(0);
		$this->config = $this->createMock(IConfig::class);
		$this->urlGenerator = $this->createMock(IURLGenerator::class);
		$this->clientService = $this->createMock(IClientService::class);
		$this->logger = $this->createMock(LoggerInterface::class);
		
		$this->trait = new CheckServerResponseTraitImplementation(
			$this->l10n,
			$this->config,
			$this->urlGenerator,
			$this->clientService,
			$this->logger,
		);
	}

	/**
	 * @dataProvider dataNormalizeUrl
	 */
	public function testNormalizeUrl(string $url, bool $isRootRequest, string $expected): void {
		$this->assertEquals($expected, $this->trait->normalizeUrl($url, $isRootRequest));
	}

	public static function dataNormalizeUrl(): array {
		return [
			// untouched web-root
			'valid and nothing to change' => ['http://example.com/root', false, 'http://example.com/root'],
			'valid with port and nothing to change' => ['http://example.com:8081/root', false, 'http://example.com:8081/root'],
			'trailing slash' => ['http://example.com/root/', false, 'http://example.com/root'],
			'deep web root' => ['http://example.com/deep/webroot', false, 'http://example.com/deep/webroot'],
			// removal of the web-root
			'remove web root' => ['http://example.com/root/', true, 'http://example.com'],
			'remove web root but empty' => ['http://example.com', true, 'http://example.com'],
			'remove deep web root' => ['http://example.com/deep/webroot', true, 'http://example.com'],
			'remove web root with port' => ['http://example.com:8081/root', true, 'http://example.com:8081'],
			'remove web root with port but empty' => ['http://example.com:8081', true, 'http://example.com:8081'],
			'remove web root from IP' => ['https://127.0.0.1/root', true, 'https://127.0.0.1'],
			'remove web root from IP with port' => ['https://127.0.0.1:8080/root', true, 'https://127.0.0.1:8080'],
			'remove web root from IPv6' => ['https://[ff02::1]/root', true, 'https://[ff02::1]'],
			'remove web root from IPv6 with port' => ['https://[ff02::1]:8080/root', true, 'https://[ff02::1]:8080'],
		];
	}

	/**
	 * @dataProvider dataGetTestUrls
	 */
	public function testGetTestUrls(
		string $url,
		bool $isRootRequest,
		string $cliUrl,
		string $webRoot,
		array $trustedDomains,
		array $expected,
	): void {
		$this->config->expects(self::atLeastOnce())
			->method('getSystemValueString')
			->with('overwrite.cli.url', '')
			->willReturn($cliUrl);

		$this->config->expects(self::atLeastOnce())
			->method('getSystemValue')
			->with('trusted_domains', [])
			->willReturn($trustedDomains);

		$this->urlGenerator->expects(self::atLeastOnce())
			->method('getWebroot')
			->willReturn($webRoot);

		$this->urlGenerator->expects(self::atLeastOnce())
			->method('getBaseUrl')
			->willReturn(self::BASE_URL . $webRoot);
		
		$result = $this->trait->getTestUrls($url, $isRootRequest);
		$this->assertEquals($expected, $result);
	}

	/**
	 * @return array<string, list{string, bool, string, string, list<string>, list<string>}>
	 */
	public static function dataGetTestUrls(): array {
		return [
			'same cli and base URL' => [
				'/apps/files/js/example.js', false, 'https://nextcloud.local', '', ['nextcloud.local'], [
					// from cli url
					'https://nextcloud.local/apps/files/js/example.js',
					// http variant from trusted domains
					'http://nextcloud.local/apps/files/js/example.js',
				]
			],
			'different cli and base URL' => [
				'/apps/files/js/example.js', false, 'https://example.com', '', ['nextcloud.local'], [
					// from cli url
					'https://example.com/apps/files/js/example.js',
					// from base url
					'https://nextcloud.local/apps/files/js/example.js',
					// http variant from trusted domains
					'http://nextcloud.local/apps/files/js/example.js',
				]
			],
			'different cli and base URL and trusted domains' => [
				'/apps/files/js/example.js', false, 'https://example.com', '', ['nextcloud.local', 'example.com', '127.0.0.1'], [
					// from cli url
					'https://example.com/apps/files/js/example.js',
					// from base url
					'https://nextcloud.local/apps/files/js/example.js',
					// http variant from trusted domains
					'http://nextcloud.local/apps/files/js/example.js',
					'http://example.com/apps/files/js/example.js',
					// trusted domains
					'https://127.0.0.1/apps/files/js/example.js',
					'http://127.0.0.1/apps/files/js/example.js',
				]
			],
			'wildcard trusted domains' => [
				'/apps/files/js/example.js', false, '', '', ['nextcloud.local', '*.example.com'], [
					// from base url
					'https://nextcloud.local/apps/files/js/example.js',
					// http variant from trusted domains
					'http://nextcloud.local/apps/files/js/example.js',
					// trusted domains with wild card are skipped
				]
			],
			'missing leading slash' => [
				'apps/files/js/example.js', false, 'https://nextcloud.local', '', ['nextcloud.local'], [
					// from cli url
					'https://nextcloud.local/apps/files/js/example.js',
					// http variant from trusted domains
					'http://nextcloud.local/apps/files/js/example.js',
				]
			],
			'keep web-root' => [
				'/apps/files/js/example.js', false, 'https://example.com', '/nextcloud', ['nextcloud.local', 'example.com', '192.168.100.1'], [
					// from cli url (note that the CLI url has NO web root)
					'https://example.com/apps/files/js/example.js',
					// from base url
					'https://nextcloud.local/nextcloud/apps/files/js/example.js',
					// http variant from trusted domains
					'http://nextcloud.local/nextcloud/apps/files/js/example.js',
					// trusted domains with web-root
					'https://example.com/nextcloud/apps/files/js/example.js',
					'http://example.com/nextcloud/apps/files/js/example.js',
					'https://192.168.100.1/nextcloud/apps/files/js/example.js',
					'http://192.168.100.1/nextcloud/apps/files/js/example.js',
				]
			],
			// example if the URL is generated by the URL generator
			'keep web-root and web root in url' => [
				'/nextcloud/apps/files/js/example.js', false, 'https://example.com', '/nextcloud', ['nextcloud.local', 'example.com', '192.168.100.1'], [
					// from cli url (note that the CLI url has NO web root)
					'https://example.com/apps/files/js/example.js',
					// from base url
					'https://nextcloud.local/nextcloud/apps/files/js/example.js',
					// http variant from trusted domains
					'http://nextcloud.local/nextcloud/apps/files/js/example.js',
					// trusted domains with web-root
					'https://example.com/nextcloud/apps/files/js/example.js',
					'http://example.com/nextcloud/apps/files/js/example.js',
					'https://192.168.100.1/nextcloud/apps/files/js/example.js',
					'http://192.168.100.1/nextcloud/apps/files/js/example.js',
				]
			],
			'remove web-root' => [
				'/.well-known/caldav', true, 'https://example.com', '/nextcloud', ['nextcloud.local', 'example.com', '192.168.100.1'], [
					// from cli url (note that the CLI url has NO web root)
					'https://example.com/.well-known/caldav',
					// from base url
					'https://nextcloud.local/.well-known/caldav',
					// http variant from trusted domains
					'http://nextcloud.local/.well-known/caldav',
					'http://example.com/.well-known/caldav',
					// trusted domains with web-root
					'https://192.168.100.1/.well-known/caldav',
					'http://192.168.100.1/.well-known/caldav',
				]
			],
		];
	}

}
