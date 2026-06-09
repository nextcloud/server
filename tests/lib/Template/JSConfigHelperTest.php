<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Template;

use bantu\IniGetWrapper\IniGetWrapper;
use OC\Authentication\Token\IProvider;
use OC\CapabilitiesManager;
use OC\Files\FilenameValidator;
use OC\Template\JSConfigHelper;
use OCP\App\AppPathNotFoundException;
use OCP\App\IAppManager;
use OCP\Authentication\Exceptions\ExpiredTokenException;
use OCP\Authentication\Token\IToken;
use OCP\Defaults;
use OCP\IAppConfig;
use OCP\IConfig;
use OCP\IGroupManager;
use OCP\IInitialStateService;
use OCP\IL10N;
use OCP\ISession;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\ServerVersion;
use OCP\Share\IManager as IShareManager;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class JSConfigHelperTest extends TestCase {
	private ServerVersion&MockObject $serverVersion;
	private IL10N&MockObject $l10n;
	private Defaults&MockObject $defaults;
	private IAppManager&MockObject $appManager;
	private ISession&MockObject $session;
	private IConfig&MockObject $config;
	private IAppConfig&MockObject $appConfig;
	private IGroupManager&MockObject $groupManager;
	private IniGetWrapper&MockObject $iniWrapper;
	private IURLGenerator&MockObject $urlGenerator;
	private CapabilitiesManager&MockObject $capabilitiesManager;
	private IInitialStateService&MockObject $initialStateService;
	private IProvider&MockObject $tokenProvider;
	private FilenameValidator&MockObject $filenameValidator;
	private IShareManager&MockObject $shareManager;

	#[\Override]
	protected function setUp(): void {
		parent::setUp();

		$this->serverVersion = $this->createMock(ServerVersion::class);
		$this->l10n = $this->createMock(IL10N::class);
		$this->defaults = $this->createMock(Defaults::class);
		$this->appManager = $this->createMock(IAppManager::class);
		$this->session = $this->createMock(ISession::class);
		$this->config = $this->createMock(IConfig::class);
		$this->appConfig = $this->createMock(IAppConfig::class);
		$this->groupManager = $this->createMock(IGroupManager::class);
		$this->iniWrapper = $this->createMock(IniGetWrapper::class);
		$this->urlGenerator = $this->createMock(IURLGenerator::class);
		$this->capabilitiesManager = $this->createMock(CapabilitiesManager::class);
		$this->initialStateService = $this->createMock(IInitialStateService::class);
		$this->tokenProvider = $this->createMock(IProvider::class);
		$this->filenameValidator = $this->createMock(FilenameValidator::class);
		$this->shareManager = $this->createMock(IShareManager::class);

		$this->overwriteService(IShareManager::class, $this->shareManager);

		$this->serverVersion->method('getVersion')->willReturn([31, 0, 0, 0]);
		$this->serverVersion->method('getVersionString')->willReturn('31.0.0');

		$this->l10n->method('l')->willReturnCallback(
			static fn (string $key, mixed $value = null): string => match ($key) {
				'firstday' => '1',
				'jsdate' => 'dd.MM.yyyy',
				default => '',
			}
		);
		$this->l10n->method('t')->willReturnCallback(static fn (string $text): string => $text);

		$this->defaults->method('getEntity')->willReturn('Nextcloud');
		$this->defaults->method('getName')->willReturn('Nextcloud');
		$this->defaults->method('getProductName')->willReturn('Nextcloud');
		$this->defaults->method('getTitle')->willReturn('Nextcloud');
		$this->defaults->method('getBaseUrl')->willReturn('https://example.com');
		$this->defaults->method('getSyncClientUrl')->willReturn('https://example.com/desktop');
		$this->defaults->method('getDocBaseUrl')->willReturn('https://docs.example.com');
		$this->defaults->method('buildDocLinkToKey')->willReturn('https://docs.example.com/PLACEHOLDER');
		$this->defaults->method('getSlogan')->willReturn('A safe home for all your data');

		$this->iniWrapper->method('getNumeric')->with('session.gc_maxlifetime')->willReturn(1440);

		$this->urlGenerator->method('linkToDocs')
			->with('user-sharing-federated')
			->willReturn('https://docs.example.com/user-sharing-federated');

		$this->capabilitiesManager->method('getCapabilities')
			->with(false, true)
			->willReturn(['files' => ['chunked_upload' => true]]);

		$this->filenameValidator->method('getForbiddenCharacters')
			->willReturn(["\0", '/']);

		$this->shareManager->method('sharingDisabledForUser')->willReturn(false);
		$this->shareManager->method('allowGroupSharing')->willReturn(true);

		$this->appConfig->method('getValueBool')->willReturnCallback(
			static function (string $app, string $key, bool $default = false): bool {
				return match ([$app, $key, $default]) {
					['core', 'shareapi_link_default_password', false] => false,
					['core', 'shareapi_default_expire_date', false] => false,
					['core', 'shareapi_enforce_expire_date', false] => false,
					['core', 'shareapi_allow_resharing', true] => true,
					default => $default,
				};
			}
		);

		$this->config->method('getAppValue')->willReturnCallback(
			static function (string $app, string $key, string $default): string {
				return match ([$app, $key, $default]) {
					['files_sharing', 'outgoing_server2server_share_enabled', 'yes'] => 'yes',
					['core', 'shareapi_default_internal_expire_date', 'no'] => 'no',
					['core', 'shareapi_default_remote_expire_date', 'no'] => 'no',
					default => $default,
				};
			}
		);

		$this->config->method('getSystemValue')->willReturnCallback(
			static function (string $key, mixed $default = null): mixed {
				return match ($key) {
					'datadirectory' => \OC::$SERVERROOT . '/data',
					'loglevel_frontend' => $default,
					'loglevel' => 2,
					'lost_password_link' => null,
					'htaccess.IgnoreFrontController' => false,
					'no_unsupported_browser_warning' => false,
					'session_keepalive' => true,
					'session_lifetime' => 900,
					'debug' => false,
					'auto_logout' => false,
					default => $default,
				};
			}
		);

		$this->config->method('getSystemValueBool')->willReturnCallback(
			static function (string $key, bool $default = false): bool {
				return match ([$key, $default]) {
					['projects.enabled', false] => false,
					['enable_non-accessible_features', true] => true,
					default => $default,
				};
			}
		);

		$this->config->method('getSystemValueInt')->willReturnCallback(
			static function (string $key, int $default): int {
				return $default;
			}
		);

		$this->config->method('getUserValue')->willReturnCallback(
			static function (?string $uid, string $app, string $key, string $default): string {
				return match ([$uid, $app, $key]) {
					['alice', 'core', 'first_day_of_week'] => '',
					['alice', 'avatar', 'version'] => '7',
					['alice', 'avatar', 'generated'] => 'false',
					default => $default,
				};
			}
		);

		$this->session->method('get')
			->with('last-password-confirm')
			->willReturn(123456);
	}

	public function testAnonymousUserUsesGlobalEnabledAppsAndPublishesInitialState(): void {
		$this->appManager->expects(self::once())
			->method('getEnabledApps')
			->willReturn(['files']);

		$this->appManager->expects(self::never())
			->method('getEnabledAppsForUser');

		$this->appManager->expects(self::once())
			->method('getAppWebPath')
			->with('files')
			->willReturn('/apps/files');

		$calls = [];
		$this->initialStateService->expects(self::exactly(3))
			->method('provideInitialState')
			->willReturnCallback(function (string $app, string $key, mixed $value) use (&$calls): void {
				$calls[] = [$app, $key, $value];
			});

		$helper = $this->createHelper(null);

		$result = $helper->getConfig();

		self::assertSame('core', $calls[0][0]);
		self::assertSame('projects_enabled', $calls[0][1]);
		self::assertFalse($calls[0][2]);
		self::assertSame(['core', 'config'], array_slice($calls[1], 0, 2));
		self::assertIsArray($calls[1][2]);
		self::assertSame(['core', 'capabilities', ['files' => ['chunked_upload' => true]]], $calls[2]);
		self::assertIsString($result);
		self::assertStringContainsString('var _oc_appswebroots=', $result);
		self::assertStringContainsString('/apps/files', $result);
		self::assertStringContainsString('var _oc_config=', $result);
		self::assertStringContainsString('var _theme=', $result);
		self::assertStringNotContainsString('var oc_userconfig=', $result);
	}

	public function testLoggedInUserUsesUserEnabledAppsAndIncludesUserConfig(): void {
		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('alice');

		$this->appManager->expects(self::never())
			->method('getEnabledApps');

		$this->appManager->expects(self::once())
			->method('getEnabledAppsForUser')
			->with($user)
			->willReturn(['files']);

		$this->appManager->expects(self::once())
			->method('getAppWebPath')
			->with('files')
			->willReturn('/apps/files');

		$this->groupManager->expects(self::once())
			->method('isAdmin')
			->with('alice')
			->willReturn(false);

		$this->session->expects(self::exactly(2))
			->method('getId')
			->willReturn('session-id');

		$token = $this->createToken([]);

		$this->tokenProvider->expects(self::once())
			->method('getToken')
			->with('session-id')
			->willReturn($token);

		$this->initialStateService->expects(self::exactly(3))
			->method('provideInitialState');

		$helper = $this->createHelper($user);

		$result = $helper->getConfig();

		self::assertStringContainsString('var oc_userconfig=', $result);
		self::assertStringContainsString('"version":7', $result);
		self::assertStringContainsString('"generated":false', $result);
	}

	public function testTokenLookupExceptionFallsBackToPasswordConfirmationEnabled(): void {
		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('alice');
		$user->method('getBackend')->willReturn(new class {
		});
		$user->method('getBackendClassName')->willReturn('some_backend');

		$this->appManager->method('getEnabledAppsForUser')
			->with($user)
			->willReturn([]);

		$this->groupManager->method('isAdmin')
			->with('alice')
			->willReturn(false);

		$this->session->expects(self::once())
			->method('getId')
			->willReturn('session-id');

		$expiredToken = $this->createMock(IToken::class);
		$this->tokenProvider->expects(self::once())
			->method('getToken')
			->with('session-id')
			->willThrowException(new ExpiredTokenException($expiredToken));

		$helper = $this->createHelper($user);

		$result = $helper->getConfig();

		self::assertStringContainsString('var backendAllowsPasswordConfirmation=true;', $result);
	}

	public function testMissingAppPathIsSerializedAsFalse(): void {
		$this->appManager->expects(self::once())
			->method('getEnabledApps')
			->willReturn(['files', 'broken']);

		$this->appManager->expects(self::exactly(2))
			->method('getAppWebPath')
			->willReturnCallback(static function (string $app): string {
				return match ($app) {
					'files' => '/apps/files',
					'broken' => throw new AppPathNotFoundException('broken'),
				};
			});

		$helper = $this->createHelper(null);

		$result = $helper->getConfig();

		self::assertStringContainsString('"files":"/apps/files"', $result);
		self::assertStringContainsString('"broken":false', $result);
	}

	private function createHelper(?IUser $currentUser): JSConfigHelper {
		return new JSConfigHelper(
			$this->serverVersion,
			$this->l10n,
			$this->defaults,
			$this->appManager,
			$this->session,
			$currentUser,
			$this->config,
			$this->appConfig,
			$this->groupManager,
			$this->iniWrapper,
			$this->urlGenerator,
			$this->capabilitiesManager,
			$this->initialStateService,
			$this->tokenProvider,
			$this->filenameValidator,
		);
	}

	/**
	 * Creates an IToken mock exposing the requested scope array.
	 */
	private function createToken(array $scope): IToken&MockObject {
		$token = $this->createMock(IToken::class);
		$token->method('getScopeAsArray')
			->willReturn($scope);

		return $token;
	}
}
