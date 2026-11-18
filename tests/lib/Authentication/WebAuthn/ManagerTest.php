<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Authentication\WebAuthn;

use CBOR\ByteStringObject;
use CBOR\MapObject;
use CBOR\NegativeIntegerObject;
use CBOR\UnsignedIntegerObject;
use Cose\Algorithm\Signature\EdDSA\EdDSA;
use Cose\Algorithm\Signature\RSA\PS256;
use Cose\Algorithms;
use Cose\Key\Key;
use Cose\Key\RsaKey;
use OC\Authentication\WebAuthn\CredentialRepository;
use OC\Authentication\WebAuthn\Db\PublicKeyCredentialEntity;
use OC\Authentication\WebAuthn\Db\PublicKeyCredentialMapper;
use OC\Authentication\WebAuthn\Manager;
use OCP\IConfig;
use OCP\IUser;
use ParagonIE\ConstantTime\Base64UrlSafe;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Symfony\Component\Uid\Uuid;
use Test\TestCase;
use Webauthn\CredentialRecord;
use Webauthn\Exception\AuthenticatorResponseVerificationException;
use Webauthn\TrustPath\EmptyTrustPath;

class ManagerTest extends TestCase {
	private const CREDENTIAL_ID = 'credential-id';

	private CredentialRepository&MockObject $repository;
	private PublicKeyCredentialMapper&MockObject $mapper;
	private Manager $manager;

	protected function setUp(): void {
		parent::setUp();

		$this->repository = $this->createMock(CredentialRepository::class);
		$this->mapper = $this->createMock(PublicKeyCredentialMapper::class);
		$this->manager = new Manager(
			$this->repository,
			$this->mapper,
			$this->createMock(LoggerInterface::class),
			$this->createMock(IConfig::class),
		);
	}

	public function testStartRegistration(): void {
		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('jane');
		$user->method('getDisplayName')->willReturn('Jane Doe');

		$options = json_decode($this->manager->startRegistration($user, 'cloud.example.com:8443'), true, flags: JSON_THROW_ON_ERROR);

		$this->assertEquals(['name' => 'Nextcloud', 'id' => 'cloud.example.com'], $options['rp']);
		$this->assertEquals(['name' => 'jane', 'id' => Base64UrlSafe::encodeUnpadded('jane'), 'displayName' => 'Jane Doe'], $options['user']);
		$this->assertSame(32, strlen(Base64UrlSafe::decodeNoPadding($options['challenge'])));
		$this->assertSame('none', $options['attestation']);
		$this->assertSame(60000, $options['timeout']);

		$expectedAlgorithms = EdDSA::isSupported()
			? [Algorithms::COSE_ALGORITHM_ES256, Algorithms::COSE_ALGORITHM_EDDSA, Algorithms::COSE_ALGORITHM_PS256, Algorithms::COSE_ALGORITHM_RS256]
			: [Algorithms::COSE_ALGORITHM_ES256, Algorithms::COSE_ALGORITHM_PS256, Algorithms::COSE_ALGORITHM_RS256];
		$this->assertSame(
			array_map(fn (int $alg) => ['type' => 'public-key', 'alg' => $alg], $expectedAlgorithms),
			$options['pubKeyCredParams'],
		);
	}

	public function testFinishRegisterInvalidData(): void {
		$this->expectException(\RuntimeException::class);
		$this->manager->finishRegister('not json', 'My key', '{}');
	}

	public static function dataStartAuthenticationUserVerification(): array {
		return [
			'all devices verify the user' => [[true, true], 'required'],
			'one device without user verification' => [[true, false], 'discouraged'],
		];
	}

	#[\PHPUnit\Framework\Attributes\DataProvider('dataStartAuthenticationUserVerification')]
	public function testStartAuthentication(array $userVerification, string $expected): void {
		$entities = array_map(function (bool $uv) {
			$entity = new PublicKeyCredentialEntity();
			$entity->setUserVerification($uv);
			return $entity;
		}, $userVerification);
		$this->mapper->method('findAllForUid')
			->with('jane')
			->willReturn($entities);
		$this->repository->method('mapToCredentialRecord')
			->willReturn($this->createRecord('public-key'));

		$options = json_decode($this->manager->startAuthentication('jane', 'cloud.example.com:8443'), true, flags: JSON_THROW_ON_ERROR);

		$this->assertSame('cloud.example.com', $options['rpId']);
		$this->assertSame($expected, $options['userVerification']);
		$this->assertSame(60000, $options['timeout']);
		$this->assertCount(count($userVerification), $options['allowCredentials']);
		$this->assertSame(['type' => 'public-key', 'id' => Base64UrlSafe::encodeUnpadded(self::CREDENTIAL_ID)], $options['allowCredentials'][0]);
	}

	/**
	 * A PS256 credential must be verifiable on login, and plain HTTP is accepted for localhost.
	 */
	public function testFinishAuthenticationPS256OnLocalhost(): void {
		[$requestOptions, $data] = $this->preparePS256Authentication('localhost', 'http://localhost:8080');

		$this->repository->expects($this->once())
			->method('saveCredentialSource')
			->with($this->callback(fn (CredentialRecord $record) => $record->counter === 1));

		$authenticatorData = $this->manager->finishAuthentication($requestOptions, $data, 'jane');
		$this->assertTrue($authenticatorData->isUserVerified());
	}

	public function testFinishAuthenticationRequiresHttps(): void {
		[$requestOptions, $data] = $this->preparePS256Authentication('cloud.example.com', 'http://cloud.example.com');

		$this->repository->expects($this->never())
			->method('saveCredentialSource');

		$this->expectException(AuthenticatorResponseVerificationException::class);
		$this->manager->finishAuthentication($requestOptions, $data, 'jane');
	}

	/**
	 * @return array{string, string} The serialized request options and the client response
	 */
	private function preparePS256Authentication(string $rpId, string $origin): array {
		$rsa = openssl_pkey_get_details(openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]))['rsa'];
		$privateKey = RsaKey::create([
			Key::TYPE => Key::TYPE_RSA,
			RsaKey::DATA_N => $rsa['n'],
			RsaKey::DATA_E => $rsa['e'],
			RsaKey::DATA_D => $rsa['d'],
			RsaKey::DATA_P => $rsa['p'],
			RsaKey::DATA_Q => $rsa['q'],
			RsaKey::DATA_DP => $rsa['dmp1'],
			RsaKey::DATA_DQ => $rsa['dmq1'],
			RsaKey::DATA_QI => $rsa['iqmp'],
		]);
		$coseKey = MapObject::create()
			->add(UnsignedIntegerObject::create(Key::TYPE), UnsignedIntegerObject::create(Key::TYPE_RSA))
			->add(UnsignedIntegerObject::create(Key::ALG), NegativeIntegerObject::create(Algorithms::COSE_ALGORITHM_PS256))
			->add(NegativeIntegerObject::create(RsaKey::DATA_N), ByteStringObject::create($rsa['n']))
			->add(NegativeIntegerObject::create(RsaKey::DATA_E), ByteStringObject::create($rsa['e']));
		$record = $this->createRecord((string)$coseKey);

		$entity = new PublicKeyCredentialEntity();
		$entity->setUserVerification(true);
		$this->mapper->method('findAllForUid')->willReturn([$entity]);
		$this->repository->method('mapToCredentialRecord')->willReturn($record);
		$this->repository->method('findOneByCredentialId')
			->with(self::CREDENTIAL_ID)
			->willReturn($record);

		$requestOptions = $this->manager->startAuthentication('jane', $rpId);
		$challenge = json_decode($requestOptions, true, flags: JSON_THROW_ON_ERROR)['challenge'];

		$clientDataJSON = json_encode([
			'type' => 'webauthn.get',
			'challenge' => $challenge,
			'origin' => $origin,
			'crossOrigin' => false,
		], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
		// RP ID hash, flags (user present + user verified) and signature counter
		$authenticatorData = hash('sha256', $rpId, true) . chr(0x05) . pack('N', 1);
		$signature = PS256::create()->sign($authenticatorData . hash('sha256', $clientDataJSON, true), $privateKey);

		$data = json_encode([
			'id' => Base64UrlSafe::encodeUnpadded(self::CREDENTIAL_ID),
			'rawId' => Base64UrlSafe::encodeUnpadded(self::CREDENTIAL_ID),
			'type' => 'public-key',
			'response' => [
				'clientDataJSON' => Base64UrlSafe::encodeUnpadded($clientDataJSON),
				'authenticatorData' => Base64UrlSafe::encodeUnpadded($authenticatorData),
				'signature' => Base64UrlSafe::encodeUnpadded($signature),
				'userHandle' => Base64UrlSafe::encodeUnpadded('jane'),
			],
			'clientExtensionResults' => [],
		], JSON_THROW_ON_ERROR);

		return [$requestOptions, $data];
	}

	private function createRecord(string $credentialPublicKey): CredentialRecord {
		return CredentialRecord::create(
			self::CREDENTIAL_ID,
			'public-key',
			[],
			'none',
			EmptyTrustPath::create(),
			Uuid::fromString('00000000-0000-0000-0000-000000000000'),
			$credentialPublicKey,
			'jane',
			0,
		);
	}
}
