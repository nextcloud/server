<?php

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

use OCA\Sharing\Controller\ApiV1Controller;
use OCP\AppFramework\Http\DataResponse;
use OCP\IRequest;
use OCP\IURLGenerator;
use OCP\IUserManager;
use OCP\IUserSession;
use OCP\L10N\IFactory;
use OCP\Server;
use OCP\Sharing\ISharingManager;
use OCP\Sharing\ISharingRegistry;
use OCP\Sharing\Permission\SharePermission;
use OCP\Sharing\Permission\SharePermissionPreset;
use OCP\Sharing\Property\ShareProperty;
use OCP\Sharing\Recipient\ShareRecipient;
use OCP\Sharing\ShareAccessContext;
use OCP\Sharing\ShareState;
use OCP\Sharing\Source\ShareSource;
use PHPUnit\Framework\Attributes\Group;
use Test\Sharing\AbstractSharingManagerTests;

#[Group(name: 'DB')]
final class ApiV1ControllerTest extends AbstractSharingManagerTests {
	public function testDefaultShareAccessContext(): void {
		$user = Server::get(IUserManager::class)->createUser('user', 'password');
		$this->assertNotFalse($user);

		self::loginAsUser($user->getUID());

		$controller = new ApiV1Controller(
			'',
			Server::get(IRequest::class),
			Server::get(IUserSession::class),
			Server::get(ISharingManager::class),
			Server::get(ISharingRegistry::class),
			Server::get(IFactory::class),
			Server::get(IURLGenerator::class),
			Server::get(IUserManager::class),
		);

		$this->assertEquals(new ShareAccessContext($user), $controller->accessContext);

		self::logout();
	}

	/**
	 * @param Closure(ApiV1Controller): DataResponse $closure
	 */
	private function executeRequest(ShareAccessContext $accessContext, Closure $closure): array {
		$controller = new ApiV1Controller(
			'',
			Server::get(IRequest::class),
			Server::get(IUserSession::class),
			Server::get(ISharingManager::class),
			Server::get(ISharingRegistry::class),
			Server::get(IFactory::class),
			Server::get(IURLGenerator::class),
			Server::get(IUserManager::class),
		);

		// We have to override the access context because the controller always use the user session, but the tests don't expect that.
		$controller->accessContext = $accessContext;

		$response = $closure($controller);
		if ($response->getStatus() < 400) {
			/** @psalm-suppress MixedReturnStatement */
			return $response->getData();
		}

		/** @psalm-suppress MixedArgument */
		throw new RuntimeException($response->getData());
	}

	#[Override]
	protected function searchRecipients(ShareAccessContext $accessContext, ?array $recipientTypeClasses, string $query, int $limit, int $offset): array {
		/** @psalm-suppress ArgumentTypeCoercion */
		return $this->executeRequest($accessContext, fn (ApiV1Controller $controller): DataResponse => $controller->searchRecipients($recipientTypeClasses, $query, $limit, $offset));
	}

	#[Override]
	protected function createShare(ShareAccessContext $accessContext): array {
		return $this->executeRequest($accessContext, fn (ApiV1Controller $controller): DataResponse => $controller->createShare());
	}

	#[Override]
	protected function updateShareState(ShareAccessContext $accessContext, string $id, ShareState $state): array {
		return $this->executeRequest($accessContext, fn (ApiV1Controller $controller): DataResponse => $controller->updateShareState($id, $state->value));
	}

	#[Override]
	protected function addShareSource(ShareAccessContext $accessContext, string $id, ShareSource $source): array {
		return $this->executeRequest($accessContext, fn (ApiV1Controller $controller): DataResponse => $controller->addShareSource($id, $source->class, $source->value));
	}

	#[Override]
	protected function removeShareSource(ShareAccessContext $accessContext, string $id, ShareSource $source): array {
		return $this->executeRequest($accessContext, fn (ApiV1Controller $controller): DataResponse => $controller->removeShareSource($id, $source->class, $source->value));
	}

	#[Override]
	protected function addShareRecipient(ShareAccessContext $accessContext, string $id, ShareRecipient $recipient): array {
		return $this->executeRequest($accessContext, fn (ApiV1Controller $controller): DataResponse => $controller->addShareRecipient($id, $recipient->class, $recipient->value, $recipient->instance));
	}

	#[Override]
	protected function removeShareRecipient(ShareAccessContext $accessContext, string $id, ShareRecipient $recipient): array {
		return $this->executeRequest($accessContext, fn (ApiV1Controller $controller): DataResponse => $controller->removeShareRecipient($id, $recipient->class, $recipient->value, $recipient->instance));
	}

	#[Override]
	protected function updateShareRecipientSecret(ShareAccessContext $accessContext, string $id, ShareRecipient $recipient, string $secret): array {
		/** @psalm-suppress ArgumentTypeCoercion */
		return $this->executeRequest($accessContext, fn (ApiV1Controller $controller): DataResponse => $controller->updateShareRecipientSecret($id, $recipient->class, $recipient->value, $recipient->instance, $secret));
	}

	#[Override]
	protected function updateShareProperty(ShareAccessContext $accessContext, string $id, ShareProperty $property): array {
		return $this->executeRequest($accessContext, fn (ApiV1Controller $controller): DataResponse => $controller->updateShareProperty($id, $property->class, $property->value));
	}

	#[Override]
	protected function updateSharePermission(ShareAccessContext $accessContext, string $id, SharePermission $permission): array {
		return $this->executeRequest($accessContext, fn (ApiV1Controller $controller): DataResponse => $controller->updateSharePermission($id, $permission->class, $permission->enabled));
	}

	#[Override]
	protected function selectSharePermissionPreset(ShareAccessContext $accessContext, string $id, SharePermissionPreset $permissionPreset): array {
		return $this->executeRequest($accessContext, fn (ApiV1Controller $controller): DataResponse => $controller->selectSharePermissionPreset($id, $permissionPreset->value));
	}

	#[Override]
	protected function deleteShare(ShareAccessContext $accessContext, string $id): void {
		$this->executeRequest($accessContext, fn (ApiV1Controller $controller): DataResponse => $controller->deleteShare($id));
	}

	#[Override]
	protected function getShare(ShareAccessContext $accessContext, string $id): array {
		return $this->executeRequest(new ShareAccessContext($accessContext->currentUser, null, [], $accessContext->overrideChecks), fn (ApiV1Controller $controller): DataResponse => $controller->getShare($id, $accessContext->secret, $accessContext->arguments));
	}

	#[Override]
	protected function listShares(ShareAccessContext $accessContext, ?string $sourceTypeClass, ?string $lastShareID, ?int $limit): array {
		return $this->executeRequest($accessContext, function (ApiV1Controller $controller) use ($sourceTypeClass, $lastShareID, $limit): DataResponse {
			if ($limit !== null) {
				/** @psalm-suppress ArgumentTypeCoercion */
				return $controller->listShares($sourceTypeClass, $lastShareID, $limit);
			}

			/** @psalm-suppress ArgumentTypeCoercion */
			return $controller->listShares($sourceTypeClass, $lastShareID);
		});
	}
}
