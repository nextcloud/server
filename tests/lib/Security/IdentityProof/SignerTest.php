<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Security\IdentityProof;

use OC\Security\IdentityProof\Key;
use OC\Security\IdentityProof\Manager;
use OC\Security\IdentityProof\Signer;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\IUser;
use OCP\IUserManager;
use Test\TestCase;

class SignerTest extends TestCase {
	/** @var string */
	private $private = '-----BEGIN PRIVATE KEY-----
MIIEvQIBADANBgkqhkiG9w0BAQEFAASCBKcwggSjAgEAAoIBAQDImc6QvHBjBIoo
w9nnywiPNESleUuFJ2yQ3Gd/3w2BkblojlUAJAsUd/bQokjOH9d+7nqyzgSiXjRl
iwKagY6NjcNEEq0X5KOMNwx6uEbtq3+7pA7H2JefNrnAuD+Fhp3Hyo3h1cse6hAq
6Zr8haCiSdFBfelLnx/X3gPgCzgl6GnvSmiqPEPFGng822dlW2RW+IIUv4y2LoIH
2PKZpxottxtFGIpcKSSHGUfNWya7Ih4E6RBgOrpyu4hrkikl4Xdh4RVgAf/GH54F
gQi/AFeRS6llXJhep3lZOtLnFdYNPKFz1i/UvBoyUv8lrsvNa76HIgSMmGQKON4i
QO0P/OaBAgMBAAECggEAdrtCnjxKsPDQ7Yvuf9mWeVxQfTir0GYjRiKOSAs3rUcZ
XJ9SBEFRJY5T0e0b9pS2MfTpPsfdylTD4o5CvjyMqZAM0U/Uj93OR4GVq1VC9g2a
Du/tp6+1HpF/pGfpgRjKbqSfEdo+3U9gvmWCTJCzIRtb9c2WtiG68UQBOyyo0RYQ
F2b4az2BEOa7mATgwwGfdhV4VTQ18+iQKtfVoguw0bi1khDA0t+o8phhhmBHlOOi
lpV5uSnJB7H3s6B01xf1dA44y57bcFNKL4THQv9dlazL2R2DhgxmADWXGPyJs0YM
mhRSB25pEcFvLu//e0fHpO+kmZ+MPsey5blH3D92+QKBgQDzmlYIWSvNWXejKMdH
QGVQmrG9nExld3LhNERONhh4FaxoXOqVZgLqAAUaSMHawYzfYjRaLuW16UTYh0XC
hs2ISE5Oc4abDc6obNs2Xalrxp9stmD/Ti+/aSQifm2SoIeIH2lcPYob5yh/bfqh
AP/Uk9ZdDSnHcsGm6wzhCmS1UwKBgQDSzz0ogjtsmPa14jIHrHZluzbfbqOgaeQi
5WZPPbuEqdS37kaDznt4goDLOywqWUGrmBtBPi2hOqGF0K7qzUvlM0mlvedvjH1l
4JByb6gXwGoZPnnzTCfDx86gKB1+rWzVbo236dHi1oirZ52voKu57TqC8My5MTzW
YFgi872GWwKBgQCkxLd8XhQqiWFKksJ3hy8AHiIqxhVGbEzf1qJ85EoYr1A2JuLk
umMuM2VAKgY1GMVYMuyGM0JckLNoYdblhJhwnbeZiLp7FhO6CCcd1qxJoccjmRhy
l0fkiBFQ44Lpsnr5r4VsRpOr2+agipsDW9Guz3Am8EhaB1zEsie773O+0QKBgFb/
W3fqNufcQIRTMt5j2ACnwD95A2HiEVotXYl6KnbXN4god0VR4zaadNhqNRHNAAL2
pNjJ9j7BWYNF2cngq1+NSOlzc51fVyjCAhqX5cDXkXGVjPJRDWAIh0clBvcOTwnN
tAKgJhP9AS3rdvHR1szGEA2VnocWwMqfu//AowhdAoGACYwuBjfUWc21jcT5yeLZ
ahLp+ImQsKDE0swhmk4uesbLLPRfyvpLca98XbBMuS1iLrVUY3mEfIV7ltaBajE0
l3eB7suqch3WUzH1RMWzwpuUMWV/A8qjPbIrd2QYUFYxJsU88lBqRg92rPnri6Ec
kC6HCb+CXsMRD7yp8KrrYnw=
-----END PRIVATE KEY-----';

	/** @var string */
	private $public = '-----BEGIN PUBLIC KEY-----
MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAyJnOkLxwYwSKKMPZ58sI
jzREpXlLhSdskNxnf98NgZG5aI5VACQLFHf20KJIzh/Xfu56ss4Eol40ZYsCmoGO
jY3DRBKtF+SjjDcMerhG7at/u6QOx9iXnza5wLg/hYadx8qN4dXLHuoQKuma/IWg
oknRQX3pS58f194D4As4Jehp70poqjxDxRp4PNtnZVtkVviCFL+Mti6CB9jymaca
LbcbRRiKXCkkhxlHzVsmuyIeBOkQYDq6cruIa5IpJeF3YeEVYAH/xh+eBYEIvwBX
kUupZVyYXqd5WTrS5xXWDTyhc9Yv1LwaMlL/Ja7LzWu+hyIEjJhkCjjeIkDtD/zm
gQIDAQAB
-----END PUBLIC KEY-----';

	/** @var Key */
	private $key;

	/** @var Manager|\PHPUnit\Framework\MockObject\MockObject */
	private $keyManager;

	/** @var ITimeFactory|\PHPUnit\Framework\MockObject\MockObject */
	private $timeFactory;

	/** @var IUserManager|\PHPUnit\Framework\MockObject\MockObject */
	private $userManager;

	/** @var Signer */
	private $signer;

	protected function setUp(): void {
		parent::setUp();

		$this->key = new Key($this->public, $this->private);

		$this->keyManager = $this->createMock(Manager::class);
		$this->timeFactory = $this->createMock(ITimeFactory::class);
		$this->userManager = $this->createMock(IUserManager::class);

		$this->signer = new Signer(
			$this->keyManager,
			$this->timeFactory,
			$this->userManager
		);
	}

	public function testSign(): void {
		$user = $this->createMock(IUser::class);
		$user->method('getCloudId')
			->willReturn('foo@example.com');

		$this->timeFactory->method('getTime')
			->willReturn(42);

		$this->keyManager->method('getKey')
			->with($this->equalTo($user))
			->willReturn($this->key);

		$data = [
			'foo' => 'bar',
			'abc' => 'def',
			'xyz' => 123,
		];

		$expects = [
			'message' => [
				'data' => $data,
				'type' => 'myType',
				'signer' => 'foo@example.com',
				'timestamp' => 42,
			],
			'signature' => 'E1fNdoWMX1QmSyKv+S3FtOgLe9niYGQFWOKGaMLxc2h7s3V++EIqJvw/NCLBfrUowzWkTzhkjfbHaf88Hz34WAn4sAwXYAO8cnboQs6SClKRzQ/nvbtLgd2wm9RQ8UTOM7wR6C7HpIn4qqJ4BTQ1bAwYAiJ2GoK+W8wC0o0Gpub2906j3JJ4cbc9lufd5ohWKCev8Ubem/EEKaRIZA7qHh+Q1MKXTaJQJlCjTMe5PyGy0fsmtVxsPls3/Fkd9sVeHEHSYHzOiF6ttlxWou4TdRbq3WSEVpt1DOOvkKI9w2+zBJ7IPH8CnVpXcdIzWDctUygZKzNMUQnweDOOziEdUw=='
		];

		$result = $this->signer->sign('myType', $data, $user);

		$this->assertEquals($expects, $result);
	}

	public function testVerifyValid(): void {
		$data = [
			'message' => [
				'data' => [
					'foo' => 'bar',
					'abc' => 'def',
					'xyz' => 123,
				],
				'type' => 'myType',
				'signer' => 'foo@example.com',
				'timestamp' => 42,
			],
			'signature' => 'E1fNdoWMX1QmSyKv+S3FtOgLe9niYGQFWOKGaMLxc2h7s3V++EIqJvw/NCLBfrUowzWkTzhkjfbHaf88Hz34WAn4sAwXYAO8cnboQs6SClKRzQ/nvbtLgd2wm9RQ8UTOM7wR6C7HpIn4qqJ4BTQ1bAwYAiJ2GoK+W8wC0o0Gpub2906j3JJ4cbc9lufd5ohWKCev8Ubem/EEKaRIZA7qHh+Q1MKXTaJQJlCjTMe5PyGy0fsmtVxsPls3/Fkd9sVeHEHSYHzOiF6ttlxWou4TdRbq3WSEVpt1DOOvkKI9w2+zBJ7IPH8CnVpXcdIzWDctUygZKzNMUQnweDOOziEdUw=='
		];

		$user = $this->createMock(IUser::class);

		$this->keyManager->method('getKey')
			->with($this->equalTo($user))
			->willReturn($this->key);

		$this->userManager->method('get')
			->with('foo')
			->willReturn($user);

		$this->assertTrue($this->signer->verify($data));
	}

	public function testVerifyInvalid(): void {
		$data = [
			'message' => [
				'data' => [
					'foo' => 'bar',
					'abc' => 'def',
					'xyz' => 123,
				],
				'type' => 'myType',
				'signer' => 'foo@example.com',
				'timestamp' => 42,
			],
			'signature' => 'Invalid sig'
		];

		$user = $this->createMock(IUser::class);

		$this->keyManager->method('getKey')
			->with($this->equalTo($user))
			->willReturn($this->key);

		$this->userManager->method('get')
			->with('foo')
			->willReturn($user);

		$this->assertFalse($this->signer->verify($data));
	}

	public function testVerifyInvalidData(): void {
		$data = [
		];

		$this->assertFalse($this->signer->verify($data));
	}
}
