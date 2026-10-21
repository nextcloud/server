<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Settings\Tests\Controller;

use OC\Authentication\WebAuthn\Db\PublicKeyCredentialEntity;
use OC\Authentication\WebAuthn\Manager;
use OCA\Settings\Controller\WebAuthnController;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IRequest;
use OCP\ISession;
use OCP\IUser;
use OCP\IUserSession;
use OCP\Log\Audit\CriticalActionPerformedEvent;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Test\TestCase;
use Webauthn\PublicKeyCredentialCreationOptions;
use Webauthn\PublicKeyCredentialRpEntity;
use Webauthn\PublicKeyCredentialUserEntity;

class WebAuthnControllerTest extends TestCase {
	private Manager&MockObject $manager;
	private IUserSession&MockObject $userSession;
	private ISession&MockObject $session;
	private IEventDispatcher&MockObject $eventDispatcher;
	private WebAuthnController $controller;

	protected function setUp(): void {
		parent::setUp();

		$this->manager = $this->createMock(Manager::class);
		$this->userSession = $this->createMock(IUserSession::class);
		$this->session = $this->createMock(ISession::class);
		$this->eventDispatcher = $this->createMock(IEventDispatcher::class);
		$this->controller = new WebAuthnController(
			$this->createMock(IRequest::class),
			$this->createMock(LoggerInterface::class),
			$this->manager,
			$this->userSession,
			$this->session,
			$this->eventDispatcher,
		);
	}

	public function testFinishRegistrationIsAudited(): void {
		$options = PublicKeyCredentialCreationOptions::create(
			PublicKeyCredentialRpEntity::create('Nextcloud'),
			PublicKeyCredentialUserEntity::create('jane', 'jane', 'Jane'),
			random_bytes(32),
			attestation: PublicKeyCredentialCreationOptions::ATTESTATION_CONVEYANCE_PREFERENCE_NONE,
		);
		$this->session->method('exists')->willReturn(true);
		$this->session->method('get')->willReturn(json_decode(json_encode($options), true));

		$credential = new PublicKeyCredentialEntity();
		$credential->setId(17);
		$this->manager->expects($this->once())
			->method('finishRegister')
			->willReturn($credential);

		$this->eventDispatcher->expects($this->once())
			->method('dispatchTyped')
			->with($this->callback(fn (CriticalActionPerformedEvent $event): bool => $event->getOperation() === 'auth.webauthn_device.added'
				&& $event->getParameters() === ['deviceId' => 17]
				&& $event->getLogMessage() === 'WebAuthn device with ID "%s" was added'));

		$this->controller->finishRegistration('My key', '{}');
	}

	public function testFinishRegistrationWithoutSessionIsNotAudited(): void {
		$this->session->method('exists')->willReturn(false);
		$this->eventDispatcher->expects($this->never())
			->method('dispatchTyped');

		$this->controller->finishRegistration('My key', '{}');
	}

	public function testDeleteRegistrationIsAudited(): void {
		$user = $this->createMock(IUser::class);
		$this->userSession->method('getUser')->willReturn($user);
		$this->manager->expects($this->once())
			->method('deleteRegistration')
			->with($user, 17);

		$this->eventDispatcher->expects($this->once())
			->method('dispatchTyped')
			->with($this->callback(fn (CriticalActionPerformedEvent $event): bool => $event->getOperation() === 'auth.webauthn_device.removed'
				&& $event->getParameters() === ['deviceId' => 17]
				&& $event->getLogMessage() === 'WebAuthn device with ID "%s" was removed'));

		$this->controller->deleteRegistration(17);
	}
}
