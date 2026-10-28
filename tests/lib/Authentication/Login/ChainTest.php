<?php

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace Test\Authentication\Login;

use OC\Authentication\Login\ALoginCommand;
use OC\Authentication\Login\Chain;
use OC\Authentication\Login\LoginData;
use OC\Authentication\Login\LoginResult;
use OCP\IRequest;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class ChainTest extends TestCase {
	/** @var IRequest|MockObject */
	private $request;

	protected function setUp(): void {
		parent::setUp();
		$this->request = $this->createMock(IRequest::class);
	}

	public function testProcessBuildsExpectedOrderAndTraversesChain(): void {
		$links = [];
		$processed = [];

		$preLogin = new RecordingCommand('preLogin', $links, $processed);
		$userDisabled = new RecordingCommand('userDisabled', $links, $processed);
		$uidLogin = new RecordingCommand('uidLogin', $links, $processed);
		$loggedInCheck = new RecordingCommand('loggedInCheck', $links, $processed);
		$completeLogin = new RecordingCommand('completeLogin', $links, $processed);
		$createSessionToken = new RecordingCommand('createSessionToken', $links, $processed);
		$clearLostPasswordTokens = new RecordingCommand('clearLostPasswordTokens', $links, $processed);
		$updateLastPasswordConfirm = new RecordingCommand('updateLastPasswordConfirm', $links, $processed);
		$setUserTimezone = new RecordingCommand('setUserTimezone', $links, $processed);
		$twoFactor = new RecordingCommand('twoFactor', $links, $processed);
		$finishRememberedLogin = new RecordingCommand('finishRememberedLogin', $links, $processed);
		$flowV2EphemeralSessions = new RecordingCommand('flowV2EphemeralSessions', $links, $processed);

		$chain = new Chain(
			$preLogin,
			$userDisabled,
			$uidLogin,
			$loggedInCheck,
			$completeLogin,
			$createSessionToken,
			$clearLostPasswordTokens,
			$updateLastPasswordConfirm,
			$setUserTimezone,
			$twoFactor,
			$finishRememberedLogin,
			$flowV2EphemeralSessions,
		);

		$loginData = new LoginData($this->request, 'user123', 'secret');
		$result = $chain->process($loginData);

		$this->assertTrue($result->isSuccess());

		$this->assertSame([
			['preLogin', 'userDisabled'],
			['userDisabled', 'uidLogin'],
			['uidLogin', 'loggedInCheck'],
			['loggedInCheck', 'completeLogin'],
			['completeLogin', 'flowV2EphemeralSessions'],
			['flowV2EphemeralSessions', 'createSessionToken'],
			['createSessionToken', 'clearLostPasswordTokens'],
			['clearLostPasswordTokens', 'updateLastPasswordConfirm'],
			['updateLastPasswordConfirm', 'setUserTimezone'],
			['setUserTimezone', 'twoFactor'],
			['twoFactor', 'finishRememberedLogin'],
		], $links);

		$this->assertSame([
			'preLogin',
			'userDisabled',
			'uidLogin',
			'loggedInCheck',
			'completeLogin',
			'flowV2EphemeralSessions',
			'createSessionToken',
			'clearLostPasswordTokens',
			'updateLastPasswordConfirm',
			'setUserTimezone',
			'twoFactor',
			'finishRememberedLogin',
		], $processed);
	}

	public function testProcessReturnsHeadFailureResult(): void {
		$links = [];
		$processed = [];

		$preLogin = new RecordingCommand(
			'preLogin',
			$links,
			$processed,
			LoginResult::failure('boom'),
			false // stop chain here
		);

		// Remaining commands should never process when head short-circuits
		$userDisabled = new RecordingCommand('userDisabled', $links, $processed);
		$uidLogin = new RecordingCommand('uidLogin', $links, $processed);
		$loggedInCheck = new RecordingCommand('loggedInCheck', $links, $processed);
		$completeLogin = new RecordingCommand('completeLogin', $links, $processed);
		$createSessionToken = new RecordingCommand('createSessionToken', $links, $processed);
		$clearLostPasswordTokens = new RecordingCommand('clearLostPasswordTokens', $links, $processed);
		$updateLastPasswordConfirm = new RecordingCommand('updateLastPasswordConfirm', $links, $processed);
		$setUserTimezone = new RecordingCommand('setUserTimezone', $links, $processed);
		$twoFactor = new RecordingCommand('twoFactor', $links, $processed);
		$finishRememberedLogin = new RecordingCommand('finishRememberedLogin', $links, $processed);
		$flowV2EphemeralSessions = new RecordingCommand('flowV2EphemeralSessions', $links, $processed);

		$chain = new Chain(
			$preLogin,
			$userDisabled,
			$uidLogin,
			$loggedInCheck,
			$completeLogin,
			$createSessionToken,
			$clearLostPasswordTokens,
			$updateLastPasswordConfirm,
			$setUserTimezone,
			$twoFactor,
			$finishRememberedLogin,
			$flowV2EphemeralSessions,
		);

		$loginData = new LoginData($this->request, 'user123', 'secret');
		$result = $chain->process($loginData);

		$this->assertFalse($result->isSuccess());
		$this->assertSame('boom', $result->getErrorMessage());
		$this->assertSame(['preLogin'], $processed);
	}
}

/**
 * Small test double for chain orchestration tests.
 */
class RecordingCommand extends ALoginCommand {
	/** @var array<int, array{0:string,1:string}> */
	private array &$links;
	/** @var array<int, string> */
	private array &$processed;

	public function __construct(
		private string $name,
		array &$links,
		array &$processed,
		private ?LoginResult $forcedResult = null,
		private bool $continueChain = true,
	) {
		$this->links = &$links;
		$this->processed = &$processed;
	}

	public function setNext(ALoginCommand $next): ALoginCommand {
		if ($next instanceof self) {
			$this->links[] = [$this->name, $next->name];
		}
		return parent::setNext($next);
	}

	public function process(LoginData $loginData): LoginResult {
		$this->processed[] = $this->name;

		if ($this->forcedResult !== null) {
			return $this->forcedResult;
		}

		if ($this->continueChain) {
			return $this->processNextOrFinishSuccessfully($loginData);
		}

		return LoginResult::success();
	}
}
