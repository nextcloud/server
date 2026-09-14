<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Sharing\Tests\Controller;

use Closure;
use NCU\Sharing\ISharingManager;
use NCU\Sharing\Permission\SharePermission;
use NCU\Sharing\Property\ShareProperty;
use NCU\Sharing\Recipient\ShareRecipient;
use NCU\Sharing\Share;
use NCU\Sharing\ShareAccessContext;
use NCU\Sharing\ShareState;
use NCU\Sharing\ShareUserStatus;
use NCU\Sharing\Source\ShareSource;
use OCA\Sharing\Controller\ApiV1Controller;
use OCP\AppFramework\Http\DataResponse;
use OCP\HintException;
use OCP\IDBConnection;
use OCP\IRequest;
use OCP\IURLGenerator;
use OCP\IUserManager;
use OCP\IUserSession;
use OCP\L10N\IFactory;
use OCP\Server;
use Override;
use PHPUnit\Framework\Attributes\Group;
use Test\Sharing\AbstractSharingManagerTests;

// TODO: Use Dispatcher

/**
 * @psalm-import-type SharingShare from Share
 */
#[Group(name: 'DB')]
final class ApiV1ControllerTest extends AbstractSharingManagerTests {
	public function testDefaultShareAccessContext(): void {
		$user = Server::get(IUserManager::class)->createUser('user', 'password');
		$this->assertNotFalse($user);

		try {
			self::loginAsUser($user->getUID());

			$controller = new ApiV1Controller(
				'',
				Server::get(IRequest::class),
				Server::get(IUserSession::class),
				Server::get(ISharingManager::class),
				$this->registry,
				Server::get(IFactory::class),
				Server::get(IURLGenerator::class),
				Server::get(IUserManager::class),
				Server::get(IDBConnection::class),
			);

			$this->assertEquals(new ShareAccessContext($user), $controller->accessContext);
		} finally {
			self::logout();
			$user->delete();
		}
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
			$this->registry,
			Server::get(IFactory::class),
			Server::get(IURLGenerator::class),
			Server::get(IUserManager::class),
			Server::get(IDBConnection::class),
		);

		// We have to override the access context because the controller always use the user session, but the tests don't expect that.
		$controller->accessContext = $accessContext;

		$response = $closure($controller);
		if ($response->getStatus() < 400) {
			/** @psalm-suppress MixedReturnStatement */
			return $response->getData();
		}

		/** @psalm-suppress MixedArgument */
		throw new HintException($response->getData());
	}

	#[Override]
	protected function searchRecipients(ShareAccessContext $accessContext, ?array $filterRecipientTypeClasses, string $query, int $limit, int $offset, ?Share $forShare = null): array {
		/** @psalm-suppress ArgumentTypeCoercion */
		return $this->executeRequest($accessContext, fn (ApiV1Controller $controller): DataResponse => $controller->searchRecipients($filterRecipientTypeClasses, $query, $limit, $offset, $forShare?->id));
	}

	#[Override]
	protected function createShare(ShareAccessContext $accessContext): array {
		/** @var SharingShare */
		return $this->executeRequest($accessContext, fn (ApiV1Controller $controller): DataResponse => $controller->createShare());
	}

	#[Override]
	protected function updateShareState(ShareAccessContext $accessContext, Share $share, ShareState $state): array {
		/** @var SharingShare */
		return $this->executeRequest($accessContext, fn (ApiV1Controller $controller): DataResponse => $controller->updateShareState($share->id, $state->value));
	}

	#[Override]
	protected function updateShareUserStatus(ShareAccessContext $accessContext, Share $share, ShareUserStatus $userStatus): array {
		/** @var SharingShare */
		return $this->executeRequest($accessContext, fn (ApiV1Controller $controller): DataResponse => $controller->updateShareUserStatus($share->id, $userStatus->value));
	}

	#[Override]
	protected function addShareSource(ShareAccessContext $accessContext, Share $share, ShareSource $source): array {
		/** @var SharingShare */
		return $this->executeRequest($accessContext, fn (ApiV1Controller $controller): DataResponse => $controller->addShareSource($share->id, $source->class, $source->value));
	}

	#[Override]
	protected function removeShareSource(ShareAccessContext $accessContext, Share $share, ShareSource $source): array {
		/** @var SharingShare */
		return $this->executeRequest($accessContext, fn (ApiV1Controller $controller): DataResponse => $controller->removeShareSource($share->id, $source->class, $source->value));
	}

	#[Override]
	protected function addShareRecipient(ShareAccessContext $accessContext, Share $share, ShareRecipient $recipient): array {
		/** @var SharingShare */
		return $this->executeRequest($accessContext, fn (ApiV1Controller $controller): DataResponse => $controller->addShareRecipient($share->id, $recipient->class, $recipient->value, $recipient->instance));
	}

	#[Override]
	protected function removeShareRecipient(ShareAccessContext $accessContext, Share $share, ShareRecipient $recipient): array {
		/** @var SharingShare */
		return $this->executeRequest($accessContext, fn (ApiV1Controller $controller): DataResponse => $controller->removeShareRecipient($share->id, $recipient->class, $recipient->value, $recipient->instance));
	}

	#[Override]
	protected function updateShareRecipientSecret(ShareAccessContext $accessContext, Share $share, ShareRecipient $recipient, string $secret): array {
		/** @psalm-suppress ArgumentTypeCoercion */
		/** @var SharingShare */
		return $this->executeRequest($accessContext, fn (ApiV1Controller $controller): DataResponse => $controller->updateShareRecipientSecret($share->id, $recipient->class, $recipient->value, $recipient->instance, $secret));
	}

	#[Override]
	protected function updateShareProperty(ShareAccessContext $accessContext, Share $share, ShareProperty $property): array {
		/** @var SharingShare */
		return $this->executeRequest($accessContext, fn (ApiV1Controller $controller): DataResponse => $controller->updateShareProperty($share->id, $property->class, $property->value));
	}

	#[Override]
	protected function updateSharePermission(ShareAccessContext $accessContext, Share $share, SharePermission $permission): array {
		/** @var SharingShare */
		return $this->executeRequest($accessContext, fn (ApiV1Controller $controller): DataResponse => $controller->updateSharePermission($share->id, $permission->class, $permission->enabled));
	}

	#[Override]
	protected function updateShareRecipientPermission(ShareAccessContext $accessContext, Share $share, ShareRecipient $recipient, SharePermission $permission): array {
		/** @var SharingShare */
		return $this->executeRequest($accessContext, fn (ApiV1Controller $controller): DataResponse => $controller->updateShareRecipientPermission($share->id, $recipient->class, $recipient->value, $recipient->instance, $permission->class, $permission->enabled));
	}

	#[Override]
	protected function selectSharePermissionPreset(ShareAccessContext $accessContext, Share $share, string $permissionPresetClass): array {
		/** @psalm-suppress ArgumentTypeCoercion */
		/** @var SharingShare */
		return $this->executeRequest($accessContext, fn (ApiV1Controller $controller): DataResponse => $controller->selectSharePermissionPreset($share->id, $permissionPresetClass));
	}

	#[Override]
	protected function deleteShare(ShareAccessContext $accessContext, Share $share): void {
		$this->executeRequest($accessContext, fn (ApiV1Controller $controller): DataResponse => $controller->deleteShare($share->id));
	}

	#[Override]
	protected function getShare(ShareAccessContext $accessContext, string $id): array {
		/** @var SharingShare */
		return $this->executeRequest(new ShareAccessContext($accessContext->currentUser, null, [], $accessContext->overrideChecks), fn (ApiV1Controller $controller): DataResponse => $controller->getShare($id, $accessContext->secret, $accessContext->arguments));
	}

	/**
	 * @psalm-suppress MixedReturnTypeCoercion
	 */
	#[Override]
	protected function getShares(ShareAccessContext $accessContext, ?string $filterSourceTypeClass, ?string $filterSourceTypeValue, ?ShareState $filterState, ?ShareUserStatus $filterUserStatus, ?string $lastShareID, ?int $limit): array {
		return $this->executeRequest($accessContext, function (ApiV1Controller $controller) use ($filterSourceTypeClass, $filterSourceTypeValue, $filterState, $filterUserStatus, $lastShareID, $limit): DataResponse {
			if ($limit !== null) {
				/** @psalm-suppress ArgumentTypeCoercion */
				return $controller->getShares($filterSourceTypeClass, $filterSourceTypeValue, $filterState?->value, $filterUserStatus?->value, $lastShareID, $limit);
			}

			/** @psalm-suppress ArgumentTypeCoercion */
			return $controller->getShares($filterSourceTypeClass, $filterSourceTypeValue, $filterState?->value, $filterUserStatus?->value, $lastShareID);
		});
	}
}
