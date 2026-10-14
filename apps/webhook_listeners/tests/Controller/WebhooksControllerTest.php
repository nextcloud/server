<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\WebhookListeners\Tests\Controller;

use OCA\WebhookListeners\Controller\WebhooksController;
use OCA\WebhookListeners\Db\AuthMethod;
use OCA\WebhookListeners\Db\WebhookListener;
use OCA\WebhookListeners\Db\WebhookListenerMapper;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Files\Events\Node\NodeWrittenEvent;
use OCP\IRequest;
use OCP\ISession;
use OCP\Log\Audit\CriticalActionPerformedEvent;
use OCP\Security\ICrypto;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Test\TestCase;

class WebhooksControllerTest extends TestCase {
	private IRequest&MockObject $request;
	private WebhookListenerMapper&MockObject $mapper;
	private ISession&MockObject $session;
	private IEventDispatcher&MockObject $eventDispatcher;
	private WebhooksController $controller;

	protected function setUp(): void {
		parent::setUp();

		$this->request = $this->createMock(IRequest::class);
		$this->mapper = $this->createMock(WebhookListenerMapper::class);
		$this->session = $this->createMock(ISession::class);
		$this->eventDispatcher = $this->createMock(IEventDispatcher::class);

		$this->controller = new WebhooksController(
			'webhook_listeners',
			$this->request,
			$this->createMock(LoggerInterface::class),
			$this->mapper,
			'admin',
			$this->session,
			$this->eventDispatcher,
		);
	}

	private function buildWebhookListener(?string $appId): WebhookListener {
		$webhookListener = new WebhookListener($this->createMock(ICrypto::class));
		$webhookListener->setId(42);
		$webhookListener->setAppId($appId);
		$webhookListener->setHttpMethod('POST');
		$webhookListener->setUri('https://user:pass@hooks.example.com:8443/services/secret-token?key=secret');
		$webhookListener->setEvent(NodeWrittenEvent::class);
		$webhookListener->setAuthMethod(AuthMethod::Header->value);
		return $webhookListener;
	}

	private function expectAuditEvent(string $operation, string $message, array $parameters): void {
		$this->eventDispatcher->expects($this->once())
			->method('dispatchTyped')
			->with($this->callback(fn (CriticalActionPerformedEvent $event): bool
				=> $event->getOperation() === $operation
				&& $event->getLogMessage() === $message
				&& $event->getParameters() === $parameters));
	}

	public function testCreate(): void {
		$this->mapper->method('addWebhookListener')
			->willReturn($this->buildWebhookListener(null));

		$this->expectAuditEvent(
			'webhook_listeners.webhook.created',
			'Webhook "%s" for event "%s" calling %s %s was created',
			[
				'webhookId' => 42,
				'event' => NodeWrittenEvent::class,
				'httpMethod' => 'POST',
				'uriOrigin' => 'https://hooks.example.com:8443',
			],
		);

		$this->controller->create('POST', 'https://user:pass@hooks.example.com:8443/services/secret-token?key=secret', NodeWrittenEvent::class, null, null, ['X-Secret' => 'secret'], 'header', ['Authorization' => 'secret']);
	}

	public function testUpdateByApp(): void {
		$this->session->method('get')
			->with('app_api')
			->willReturn(true);
		$this->request->method('getHeader')
			->with('ex-app-id')
			->willReturn('my_ex_app');
		$this->mapper->method('updateWebhookListener')
			->willReturn($this->buildWebhookListener('my_ex_app'));

		$this->expectAuditEvent(
			'webhook_listeners.webhook.updated',
			'Webhook "%s" for event "%s" calling %s %s was updated for app "%s"',
			[
				'webhookId' => 42,
				'event' => NodeWrittenEvent::class,
				'httpMethod' => 'POST',
				'uriOrigin' => 'https://hooks.example.com:8443',
				'appId' => 'my_ex_app',
			],
		);

		$this->controller->update(42, 'POST', 'https://user:pass@hooks.example.com:8443/services/secret-token?key=secret', NodeWrittenEvent::class, null, null, ['X-Secret' => 'secret'], 'header', ['Authorization' => 'secret']);
	}

	public function testDestroy(): void {
		$this->mapper->method('deleteById')
			->with(42)
			->willReturn(true);

		$this->expectAuditEvent(
			'webhook_listeners.webhook.deleted',
			'Webhook "%s" was deleted',
			['webhookId' => 42],
		);

		$this->controller->destroy(42);
	}

	public function testDestroyNotFound(): void {
		$this->mapper->method('deleteById')
			->willReturn(false);

		$this->eventDispatcher->expects($this->never())
			->method('dispatchTyped');

		$this->controller->destroy(42);
	}

	public function testDeleteByAppId(): void {
		$this->mapper->method('deleteByAppId')
			->with('my_ex_app')
			->willReturn(3);

		$this->expectAuditEvent(
			'webhook_listeners.webhook.deleted_by_app',
			'Webhooks of app "%s" were deleted, %s removed',
			['appId' => 'my_ex_app', 'count' => 3],
		);

		$this->controller->deleteByAppId('my_ex_app');
	}

	public function testDeleteByAppIdNothingDeleted(): void {
		$this->mapper->method('deleteByAppId')
			->willReturn(0);

		$this->eventDispatcher->expects($this->never())
			->method('dispatchTyped');

		$this->controller->deleteByAppId('my_ex_app');
	}
}
