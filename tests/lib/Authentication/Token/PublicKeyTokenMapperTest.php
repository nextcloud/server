<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Authentication\Token;

use OC\Authentication\Token\PublicKeyToken;
use OC\Authentication\Token\PublicKeyTokenMapper;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\Authentication\Token\IToken;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use OCP\IUser;
use OCP\Server;
use Test\TestCase;

#[\PHPUnit\Framework\Attributes\Group('DB')]
class PublicKeyTokenMapperTest extends TestCase {
	/** @var PublicKeyTokenMapper */
	private $mapper;

	/** @var IDBConnection */
	private $dbConnection;

	/** @var int */
	private $time;

	#[\Override]
	protected function setUp(): void {
		parent::setUp();

		$this->dbConnection = Server::get(IDBConnection::class);
		$this->time = time();
		$this->resetDatabase();

		$this->mapper = new PublicKeyTokenMapper($this->dbConnection);
	}

	private function resetDatabase() {
		$qb = $this->dbConnection->getQueryBuilder();
		$qb->delete('authtoken')->executeStatement();
		$qb->insert('authtoken')->values([
			'uid' => $qb->createNamedParameter('user1'),
			'login_name' => $qb->createNamedParameter('User1'),
			'password' => $qb->createNamedParameter('a75c7116460c082912d8f6860a850904|3nz5qbG1nNSLLi6V|c55365a0e54cfdfac4a175bcf11a7612aea74492277bba6e5d96a24497fa9272488787cb2f3ad34d8b9b8060934fce02f008d371df3ff3848f4aa61944851ff0'),
			'name' => $qb->createNamedParameter('Firefox on Linux'),
			'token' => $qb->createNamedParameter('9c5a2e661482b65597408a6bb6c4a3d1af36337381872ac56e445a06cdb7fea2b1039db707545c11027a4966919918b19d875a8b774840b18c6cbb7ae56fe206'),
			'type' => $qb->createNamedParameter(IToken::TEMPORARY_TOKEN),
			'last_activity' => $qb->createNamedParameter($this->time - 120, IQueryBuilder::PARAM_INT), // Two minutes ago
			'last_check' => $this->time - 60 * 10, // 10mins ago
			'public_key' => $qb->createNamedParameter('public key'),
			'private_key' => $qb->createNamedParameter('private key'),
			'version' => $qb->createNamedParameter(2),
		])->executeStatement();
		$qb->insert('authtoken')->values([
			'uid' => $qb->createNamedParameter('user2'),
			'login_name' => $qb->createNamedParameter('User2'),
			'password' => $qb->createNamedParameter('971a337057853344700bbeccf836519f|UwOQwyb34sJHtqPV|036d4890f8c21d17bbc7b88072d8ef049a5c832a38e97f3e3d5f9186e896c2593aee16883f617322fa242728d0236ff32d163caeb4bd45e14ca002c57a88665f'),
			'name' => $qb->createNamedParameter('Firefox on Android'),
			'token' => $qb->createNamedParameter('1504445f1524fc801035448a95681a9378ba2e83930c814546c56e5d6ebde221198792fd900c88ed5ead0555780dad1ebce3370d7e154941cd5de87eb419899b'),
			'type' => $qb->createNamedParameter(IToken::TEMPORARY_TOKEN),
			'last_activity' => $qb->createNamedParameter($this->time - 60 * 60 * 24 * 3, IQueryBuilder::PARAM_INT), // Three days ago
			'last_check' => $this->time - 10, // 10secs ago
			'public_key' => $qb->createNamedParameter('public key'),
			'private_key' => $qb->createNamedParameter('private key'),
			'version' => $qb->createNamedParameter(2),
		])->executeStatement();
		$qb->insert('authtoken')->values([
			'uid' => $qb->createNamedParameter('user1'),
			'login_name' => $qb->createNamedParameter('User1'),
			'password' => $qb->createNamedParameter('063de945d6f6b26862d9b6f40652f2d5|DZ/z520tfdXPtd0T|395f6b89be8d9d605e409e20b9d9abe477fde1be38a3223f9e508f979bf906e50d9eaa4dca983ca4fb22a241eb696c3f98654e7775f78c4caf13108f98642b53'),
			'name' => $qb->createNamedParameter('Iceweasel on Linux'),
			'token' => $qb->createNamedParameter('47af8697ba590fb82579b5f1b3b6e8066773a62100abbe0db09a289a62f5d980dc300fa3d98b01d7228468d1ab05c1aa14c8d14bd5b6eee9cdf1ac14864680c3'),
			'type' => $qb->createNamedParameter(IToken::TEMPORARY_TOKEN),
			'last_activity' => $qb->createNamedParameter($this->time - 120, IQueryBuilder::PARAM_INT), // Two minutes ago
			'last_check' => $this->time - 60 * 10, // 10mins ago
			'public_key' => $qb->createNamedParameter('public key'),
			'private_key' => $qb->createNamedParameter('private key'),
			'version' => $qb->createNamedParameter(2),
		])->executeStatement();
		$qb->insert('authtoken')->values([
			'uid' => $qb->createNamedParameter('user3'),
			'login_name' => $qb->createNamedParameter('User3'),
			'password' => $qb->createNamedParameter('063de945d6f6b26862d9b6f40652f2d5|DZ/z520tfdXPtd0T|395f6b89be8d9d605e409e20b9d9abe477fde1be38a3223f9e508f979bf906e50d9eaa4dca983ca4fb22a241eb696c3f98654e7775f78c4caf13108f98642b53'),
			'name' => $qb->createNamedParameter('Iceweasel on Linux'),
			'token' => $qb->createNamedParameter('6d9a290d239d09f2cc33a03cc54cccd46f7dc71630dcc27d39214824bd3e093f1feb4e2b55eb159d204caa15dee9556c202a5aa0b9d67806c3f4ec2cde11af67'),
			'type' => $qb->createNamedParameter(IToken::TEMPORARY_TOKEN),
			'last_activity' => $qb->createNamedParameter($this->time - 120, IQueryBuilder::PARAM_INT), // Two minutes ago
			'last_check' => $this->time - 60 * 10, // 10mins ago
			'public_key' => $qb->createNamedParameter('public key'),
			'private_key' => $qb->createNamedParameter('private key'),
			'version' => $qb->createNamedParameter(2),
			'password_invalid' => $qb->createNamedParameter(1),
		])->executeStatement();
		$qb->insert('authtoken')->values([
			'uid' => $qb->createNamedParameter('user3'),
			'login_name' => $qb->createNamedParameter('User3'),
			'password' => $qb->createNamedParameter('063de945d6f6b26862d9b6f40652f2d5|DZ/z520tfdXPtd0T|395f6b89be8d9d605e409e20b9d9abe477fde1be38a3223f9e508f979bf906e50d9eaa4dca983ca4fb22a241eb696c3f98654e7775f78c4caf13108f98642b53'),
			'name' => $qb->createNamedParameter('Iceweasel on Linux'),
			'token' => $qb->createNamedParameter('84c5808c6445b6d65b8aa5b03840f09b27de603f0fb970906fb14ea4b115b7bf5ec53fada5c093fe46afdcd7bbc9617253a4d105f7dfb32719f9973d72412f31'),
			'type' => $qb->createNamedParameter(IToken::PERMANENT_TOKEN),
			'last_activity' => $qb->createNamedParameter($this->time - 60 * 3, IQueryBuilder::PARAM_INT), // Three minutes ago
			'last_check' => $this->time - 60 * 10, // 10mins ago
			'public_key' => $qb->createNamedParameter('public key'),
			'private_key' => $qb->createNamedParameter('private key'),
			'version' => $qb->createNamedParameter(2),
			'password_invalid' => $qb->createNamedParameter(1),
		])->executeStatement();
	}

	private function getNumberOfTokens() {
		$qb = $this->dbConnection->getQueryBuilder();
		$result = $qb->select($qb->func()->count('*', 'count'))
			->from('authtoken')
			->executeQuery()
			->fetchAssociative();
		return (int)$result['count'];
	}

	public function testInvalidate(): void {
		$token = '9c5a2e661482b65597408a6bb6c4a3d1af36337381872ac56e445a06cdb7fea2b1039db707545c11027a4966919918b19d875a8b774840b18c6cbb7ae56fe206';

		$this->mapper->invalidate($token);

		$this->assertSame(4, $this->getNumberOfTokens());
	}

	public function testInvalidateInvalid(): void {
		$token = 'youwontfindthisoneinthedatabase';

		$this->mapper->invalidate($token);

		$this->assertSame(5, $this->getNumberOfTokens());
	}

	public function testInvalidateOld(): void {
		$olderThan = $this->time - 60 * 60; // One hour

		$this->mapper->invalidateOld($olderThan);

		$this->assertSame(4, $this->getNumberOfTokens());
	}

	public function testInvalidateLastUsedBefore(): void {
		$before = $this->time - 60 * 2; // Two minutes

		$this->mapper->invalidateLastUsedBefore('user3', $before);

		$this->assertSame(4, $this->getNumberOfTokens());
	}

	public function testGetToken(): void {
		$token = new PublicKeyToken();
		$token->setUid('user2');
		$token->setLoginName('User2');
		$token->setPassword('971a337057853344700bbeccf836519f|UwOQwyb34sJHtqPV|036d4890f8c21d17bbc7b88072d8ef049a5c832a38e97f3e3d5f9186e896c2593aee16883f617322fa242728d0236ff32d163caeb4bd45e14ca002c57a88665f');
		$token->setName('Firefox on Android');
		$token->setToken('1504445f1524fc801035448a95681a9378ba2e83930c814546c56e5d6ebde221198792fd900c88ed5ead0555780dad1ebce3370d7e154941cd5de87eb419899b');
		$token->setType(IToken::TEMPORARY_TOKEN);
		$token->setRemember(IToken::DO_NOT_REMEMBER);
		$token->setLastActivity($this->time - 60 * 60 * 24 * 3);
		$token->setLastCheck($this->time - 10);
		$token->setPublicKey('public key');
		$token->setPrivateKey('private key');
		$token->setVersion(PublicKeyToken::VERSION);

		$dbToken = $this->mapper->getToken($token->getToken());

		$token->setId($dbToken->getId()); // We don't know the ID
		$token->resetUpdatedFields();

		$this->assertEquals($token, $dbToken);
	}

	public function testGetInvalidToken(): void {
		$this->expectException(DoesNotExistException::class);

		$token = 'thisisaninvalidtokenthatisnotinthedatabase';

		$this->mapper->getToken($token);
	}

	public function testGetTokenById(): void {
		$token = new PublicKeyToken();
		$token->setUid('user2');
		$token->setLoginName('User2');
		$token->setPassword('971a337057853344700bbeccf836519f|UwOQwyb34sJHtqPV|036d4890f8c21d17bbc7b88072d8ef049a5c832a38e97f3e3d5f9186e896c2593aee16883f617322fa242728d0236ff32d163caeb4bd45e14ca002c57a88665f');
		$token->setName('Firefox on Android');
		$token->setToken('1504445f1524fc801035448a95681a9378ba2e83930c814546c56e5d6ebde221198792fd900c88ed5ead0555780dad1ebce3370d7e154941cd5de87eb419899b');
		$token->setType(IToken::TEMPORARY_TOKEN);
		$token->setRemember(IToken::DO_NOT_REMEMBER);
		$token->setLastActivity($this->time - 60 * 60 * 24 * 3);
		$token->setLastCheck($this->time - 10);
		$token->setPublicKey('public key');
		$token->setPrivateKey('private key');
		$token->setVersion(PublicKeyToken::VERSION);

		$dbToken = $this->mapper->getToken($token->getToken());
		$token->setId($dbToken->getId()); // We don't know the ID
		$token->resetUpdatedFields();

		$dbToken = $this->mapper->getTokenById($token->getId());
		$this->assertEquals($token, $dbToken);
	}

	public function testGetTokenByIdNotFound(): void {
		$this->expectException(DoesNotExistException::class);

		$this->mapper->getTokenById(-1);
	}

	public function testGetInvalidTokenById(): void {
		$this->expectException(DoesNotExistException::class);

		$id = '42';

		$this->mapper->getToken($id);
	}

	public function testGetTokenByUser(): void {
		$this->assertCount(2, $this->mapper->getTokenByUser('user1'));
	}

	public function testGetTokenByUserNotFound(): void {
		$this->assertCount(0, $this->mapper->getTokenByUser('user1000'));
	}

	public function testGetById(): void {
		/** @var IUser|\PHPUnit\Framework\MockObject\MockObject $user */
		$user = $this->createMock(IUser::class);
		$qb = $this->dbConnection->getQueryBuilder();
		$qb->select('id')
			->from('authtoken')
			->where($qb->expr()->eq('token', $qb->createNamedParameter('9c5a2e661482b65597408a6bb6c4a3d1af36337381872ac56e445a06cdb7fea2b1039db707545c11027a4966919918b19d875a8b774840b18c6cbb7ae56fe206')));
		$result = $qb->executeQuery();
		$id = $result->fetchAssociative()['id'];

		$token = $this->mapper->getTokenById((int)$id);
		$this->assertEquals('user1', $token->getUID());
	}

	public function testDeleteByName(): void {
		$qb = $this->dbConnection->getQueryBuilder();
		$qb->select('name')
			->from('authtoken')
			->where($qb->expr()->eq('token', $qb->createNamedParameter('9c5a2e661482b65597408a6bb6c4a3d1af36337381872ac56e445a06cdb7fea2b1039db707545c11027a4966919918b19d875a8b774840b18c6cbb7ae56fe206')));
		$result = $qb->executeQuery();
		$name = $result->fetchAssociative()['name'];
		$this->mapper->deleteByName($name);
		$this->assertEquals(4, $this->getNumberOfTokens());
	}

	public function testHasExpiredTokens(): void {
		$this->assertFalse($this->mapper->hasExpiredTokens('user1'));
		$this->assertTrue($this->mapper->hasExpiredTokens('user3'));
	}

	public function testGetTokensOfLogin(): void {
		$first = $this->insertToken('user1', IToken::TEMPORARY_TOKEN, null);
		$renewed = $this->insertToken('user1', IToken::TEMPORARY_TOKEN, $first);
		$copy = $this->insertToken('user1', IToken::TEMPORARY_TOKEN, $first);
		$this->insertToken('user1', IToken::TEMPORARY_TOKEN, null);
		$this->insertToken('user1', IToken::PERMANENT_TOKEN, null);
		// Only the tokens of the user count, whatever they name as their login
		$this->insertToken('user2', IToken::TEMPORARY_TOKEN, $first);

		$this->assertSame([$first, $renewed, $copy], $this->getIds($this->mapper->getTokensOfLogin('user1', $first)));
		$this->assertSame([], $this->mapper->getTokensOfLogin('user2', $renewed));

		$this->mapper->deleteSessionToken($first);
		$this->assertSame([$renewed, $copy], $this->getIds($this->mapper->getTokensOfLogin('user1', $first)));
	}

	public function testGetTokensOfLoginForUpdate(): void {
		$first = $this->insertToken('user1', IToken::TEMPORARY_TOKEN, null);
		$copy = $this->insertToken('user1', IToken::TEMPORARY_TOKEN, $first);

		$this->dbConnection->beginTransaction();
		try {
			$this->assertSame([$first, $copy], $this->getIds($this->mapper->getTokensOfLogin('user1', $first, true)));
			$this->assertSame($copy, $this->mapper->getTokenById($copy, true)->getId());
		} finally {
			$this->dbConnection->rollBack();
		}
	}

	public function testDeleteSessionToken(): void {
		$sessionToken = $this->insertToken('user1', IToken::TEMPORARY_TOKEN, null);
		$appPassword = $this->insertToken('user1', IToken::PERMANENT_TOKEN, null);

		$this->assertTrue($this->mapper->deleteSessionToken($sessionToken));
		// A concurrent request deleted it first
		$this->assertFalse($this->mapper->deleteSessionToken($sessionToken));
		$this->assertFalse($this->mapper->deleteSessionToken($appPassword));
		$this->assertSame($appPassword, $this->mapper->getTokenById($appPassword)->getId());
	}

	/**
	 * Store a token of $uid that belongs to the login $loginId, or starts its own login if null
	 *
	 * @return int The id of the token
	 */
	private function insertToken(string $uid, int $type, ?int $loginId): int {
		$qb = $this->dbConnection->getQueryBuilder();
		$qb->insert('authtoken')->values([
			'uid' => $qb->createNamedParameter($uid),
			'login_name' => $qb->createNamedParameter($uid),
			'name' => $qb->createNamedParameter('Firefox on Linux'),
			'token' => $qb->createNamedParameter(bin2hex(random_bytes(32))),
			'type' => $qb->createNamedParameter($type, IQueryBuilder::PARAM_INT),
			'remember' => $qb->createNamedParameter($type === IToken::TEMPORARY_TOKEN ? IToken::REMEMBER : IToken::DO_NOT_REMEMBER, IQueryBuilder::PARAM_INT),
			'last_activity' => $qb->createNamedParameter($this->time, IQueryBuilder::PARAM_INT),
			'last_check' => $qb->createNamedParameter($this->time, IQueryBuilder::PARAM_INT),
			'public_key' => $qb->createNamedParameter('public key'),
			'private_key' => $qb->createNamedParameter('private key'),
			'version' => $qb->createNamedParameter(2),
			'login_id' => $qb->createNamedParameter($loginId, $loginId === null ? IQueryBuilder::PARAM_NULL : IQueryBuilder::PARAM_INT),
		])->executeStatement();
		return $qb->getLastInsertId();
	}

	/**
	 * @param list<PublicKeyToken> $tokens
	 * @return list<int>
	 */
	private function getIds(array $tokens): array {
		return array_map(fn (PublicKeyToken $token): int => $token->getId(), $tokens);
	}
}
