<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\Tests\unit\Connector\Sabre;

use Firebase\JWT\JWT;
use OC\OCM\OCMSignatoryManager;
use OC\User\Session;
use OCA\DAV\Connector\Sabre\BearerAuth;
use OCP\IConfig;
use OCP\IRequest;
use OCP\ISession;
use OCP\IUser;
use OCP\IUserSession;
use OCP\Share\Exceptions\ShareNotFound;
use OCP\Share\IManager as IShareManager;
use OCP\Share\IShare;
use PHPUnit\Framework\MockObject\MockObject;
use Sabre\HTTP\RequestInterface;
use Sabre\HTTP\ResponseInterface;
use Test\TestCase;

#[\PHPUnit\Framework\Attributes\Group(name: 'DB')]
class BearerAuthTest extends TestCase {
	private const KEY_ID = 'https://local.example.com/index.php/ocm#signature';
	private const ISSUER = 'https://local.example.com';

	private IUserSession&MockObject $userSession;
	private ISession&MockObject $session;
	private IRequest&MockObject $request;
	private BearerAuth $bearerAuth;

	private IConfig&MockObject $config;

	private IShareManager&MockObject $shareManager;
	private OCMSignatoryManager&MockObject $ocmSignatoryManager;

	/** Private key matching the JWK Set served by $ocmSignatoryManager. */
	private string $jwksPrivateKeyPem = '';

	protected function setUp(): void {
		parent::setUp();

		$this->userSession = $this->createMock(Session::class);
		$this->session = $this->createMock(ISession::class);
		$this->request = $this->createMock(IRequest::class);
		$this->config = $this->createMock(IConfig::class);
		$this->shareManager = $this->createMock(IShareManager::class);
		$this->ocmSignatoryManager = $this->createMock(OCMSignatoryManager::class);
		$this->ocmSignatoryManager->method('getLocalJwks')->willReturn([$this->generateJwk()]);

		$this->bearerAuth = new BearerAuth(
			$this->userSession,
			$this->session,
			$this->request,
			$this->config,
		);
	}

	protected function tearDown(): void {
		\OC_User::setIncognitoMode(false);
		parent::tearDown();
	}

	/**
	 * Generate the signing keypair and return the matching public JWK, in the
	 * EC P-256 shape OCMSignatoryManager publishes.
	 *
	 * @return array<string, string>
	 */
	private function generateJwk(): array {
		$key = openssl_pkey_new([
			'private_key_type' => OPENSSL_KEYTYPE_EC,
			'curve_name' => 'prime256v1',
		]);
		openssl_pkey_export($key, $this->jwksPrivateKeyPem);
		$details = openssl_pkey_get_details($key);

		return [
			'kty' => 'EC',
			'crv' => 'P-256',
			'kid' => self::KEY_ID,
			'alg' => 'ES256',
			'use' => 'sig',
			'x' => JWT::urlsafeB64Encode(str_pad($details['ec']['x'], 32, "\x00", STR_PAD_LEFT)),
			'y' => JWT::urlsafeB64Encode(str_pad($details['ec']['y'], 32, "\x00", STR_PAD_LEFT)),
		];
	}

	/**
	 * Mint an OCM access token as the /token endpoint would.
	 *
	 * @param array<string, mixed> $claims claims overriding the valid defaults
	 */
	private function createOcmAccessToken(array $claims = [], string $type = 'at+jwt'): string {
		$now = time();
		$payload = array_merge([
			'iss' => self::ISSUER,
			'sub' => 'shareOwner',
			'aud' => 'sharee@remote.example.com',
			'client_id' => '42',
			'iat' => $now,
			'exp' => $now + 3600,
			'jti' => 'fixedjtivalue00',
		], $claims);

		return JWT::encode($payload, $this->jwksPrivateKeyPem, 'ES256', self::KEY_ID, ['typ' => $type]);
	}

	/**
	 * Make the share the access token points at resolvable, both by its id and
	 * by its shared secret.
	 *
	 * @param string|null $secretHolderFullId full id of the share the shared secret
	 *                                        itself resolves to, when that is not the
	 *                                        share the access token names
	 */
	private function mockFederatedShare(
		string $sharedSecret = 'shared-secret',
		int $shareType = IShare::TYPE_REMOTE,
		string $shareOwner = 'shareOwner',
		string $sharedWith = 'sharee@remote.example.com',
		?string $secretHolderFullId = null,
	): IShare&MockObject {
		$share = $this->createMock(IShare::class);
		$share->method('getShareType')->willReturn($shareType);
		$share->method('getShareOwner')->willReturn($shareOwner);
		$share->method('getSharedWith')->willReturn($sharedWith);
		$share->method('getToken')->willReturn($sharedSecret);
		$share->method('getFullId')->willReturn('ocFederatedSharing:42');

		$secretHolder = $share;
		if ($secretHolderFullId !== null) {
			$secretHolder = $this->createMock(IShare::class);
			$secretHolder->method('getFullId')->willReturn($secretHolderFullId);
		}

		$this->shareManager->method('getShareById')
			->willReturnCallback(static fn (string $id): IShare
				=> $id === 'ocFederatedSharing:42' ? $share : throw new ShareNotFound());
		$this->shareManager->method('getShareByToken')
			->willReturnCallback(static fn (string $token): IShare
				=> $token === $sharedSecret ? $secretHolder : throw new ShareNotFound());

		return $share;
	}

	private function newOcmBearerAuth(bool $allowOcmAccessToken = true): BearerAuth {
		return new BearerAuth(
			$this->userSession,
			$this->session,
			$this->request,
			$this->config,
			allowOcmAccessToken: $allowOcmAccessToken,
			shareManager: $this->shareManager,
			ocmSignatoryManager: $this->ocmSignatoryManager,
		);
	}

	public function testValidateBearerTokenNotLoggedIn(): void {
		$this->userSession
			->expects($this->once())
			->method('tryTokenLogin')
			->with($this->request)
			->willReturn(false);

		$this->assertFalse($this->bearerAuth->validateBearerToken('Token'));
	}

	public function testValidateBearerToken(): void {
		$this->userSession
			->expects($this->once())
			->method('tryTokenLogin')
			->with($this->request)
			->willReturn(true);
		$user = $this->createMock(IUser::class);
		$user
			->expects($this->once())
			->method('getUID')
			->willReturn('admin');
		$this->userSession
			->expects($this->once())
			->method('getUser')
			->willReturn($user);

		$this->assertSame('principals/users/admin', $this->bearerAuth->validateBearerToken('Token'));
	}

	public function testValidateBearerTokenAcceptsOcmAccessToken(): void {
		$bearerAuth = $this->newOcmBearerAuth();
		$share = $this->mockFederatedShare();
		$this->userSession->expects($this->never())->method('tryTokenLogin');

		$result = $bearerAuth->validateBearerToken($this->createOcmAccessToken());

		$this->assertSame('principals/users/shared-secret', $result);
		// The share is served under its shared secret, not the access token.
		$this->assertSame($share, $bearerAuth->getShare());
	}

	public function testValidateBearerTokenAcceptsOcmAccessTokenForRemoteGroupShare(): void {
		$bearerAuth = $this->newOcmBearerAuth();
		$this->mockFederatedShare(shareType: IShare::TYPE_REMOTE_GROUP);
		$this->userSession->expects($this->never())->method('tryTokenLogin');

		$this->assertSame('principals/users/shared-secret', $bearerAuth->validateBearerToken($this->createOcmAccessToken()));
	}

	public function testValidateBearerTokenIgnoresOcmAccessTokenWhenNotAllowed(): void {
		$bearerAuth = $this->newOcmBearerAuth(allowOcmAccessToken: false);
		$this->mockFederatedShare();
		$this->ocmSignatoryManager->expects($this->never())->method('getLocalJwks');
		$this->userSession
			->expects($this->once())
			->method('tryTokenLogin')
			->with($this->request)
			->willReturn(false);

		$this->assertFalse($bearerAuth->validateBearerToken($this->createOcmAccessToken()));
	}

	public static function dataValidateBearerTokenRejectsOcmAccessToken(): array {
		return [
			'issuer does not match the signing key' => [['iss' => 'https://evil.example.com'], 'at+jwt', []],
			'subject is not the share owner' => [['sub' => 'someoneElse'], 'at+jwt', []],
			'audience is not the share recipient' => [['aud' => 'attacker@evil.example.com'], 'at+jwt', []],
			'unknown share' => [['client_id' => '1337'], 'at+jwt', []],
			'missing expiry' => [['exp' => null], 'at+jwt', []],
			'wrong token type' => [[], 'JWT', []],
			'share is not federated' => [[], 'at+jwt', ['shareType' => IShare::TYPE_LINK]],
			'share secret resolves to another share' => [[], 'at+jwt', ['secretHolderFullId' => 'ocFederatedSharing:1337']],
		];
	}

	/**
	 * @param array<string, mixed> $claims
	 * @param array<string, mixed> $shareOptions
	 */
	#[\PHPUnit\Framework\Attributes\DataProvider(methodName: 'dataValidateBearerTokenRejectsOcmAccessToken')]
	public function testValidateBearerTokenRejectsOcmAccessToken(array $claims, string $type, array $shareOptions): void {
		$bearerAuth = $this->newOcmBearerAuth();
		$this->mockFederatedShare(...$shareOptions);
		// Rejected tokens must not short-circuit the regular bearer token login.
		$this->userSession
			->expects($this->once())
			->method('tryTokenLogin')
			->with($this->request)
			->willReturn(false);

		$this->assertFalse($bearerAuth->validateBearerToken($this->createOcmAccessToken($claims, $type)));
	}

	public function testChallenge(): void {
		/** @var RequestInterface&MockObject $request */
		$request = $this->createMock(RequestInterface::class);
		/** @var ResponseInterface&MockObject $response */
		$response = $this->createMock(ResponseInterface::class);
		$this->bearerAuth->challenge($request, $response);
		$this->assertTrue(true);
	}
}
