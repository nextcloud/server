<?php

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace OCA\Files\Sharing\Source;

use Exception;
use NCU\Sharing\ISharingManager;
use NCU\Sharing\ShareAccessContext;
use NCU\Sharing\Source\IShareSourceMetadata;
use NCU\Sharing\Source\IShareSourceType;
use NCU\Sharing\Source\ShareSource;
use OCA\Files\AppInfo\Application;
use OCA\Files_Trashbin\Events\MoveToTrashEvent;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\EventDispatcher\IEventListener;
use OCP\Files\Cache\ICacheEntry;
use OCP\Files\Cache\IFileAccess;
use OCP\Files\Events\Node\NodeDeletedEvent;
use OCP\Files\IRootFolder;
use OCP\Files\Mount\IShareOwnerlessMount;
use OCP\Files\Node;
use OCP\Files\Storage\ISharedStorage;
use OCP\IDBConnection;
use OCP\Interaction\InteractionResource;
use OCP\Interaction\Resources\NodeResource;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\L10N\IFactory;

/**
 * @template-implements IEventListener<NodeDeletedEvent|MoveToTrashEvent>
 */
final readonly class NodeShareSourceType implements IShareSourceType, IEventListener {
	public function __construct(
		IEventDispatcher $eventDispatcher,
		private IDBConnection $dbConnection,
		private IRootFolder $rootFolder,
		private IURLGenerator $urlGenerator,
		private ISharingManager $manager,
		private IFileAccess $fileAccess,
	) {
		$eventDispatcher->addServiceListener(NodeDeletedEvent::class, self::class);
		$eventDispatcher->addServiceListener(MoveToTrashEvent::class, self::class);
	}

	#[\Override]
	public function getDisplayName(IFactory $l10nFactory): string {
		return $l10nFactory->get(Application::APP_ID)->t('File');
	}

	#[\Override]
	public function validateSource(string $source): bool {
		return $this->rootFolder->getFirstNodeById((int)$source) instanceof Node;
	}

	#[\Override]
	public function getSourceMetadata(string $source): ?IShareSourceMetadata {
		$cacheEntry = $this->fileAccess->getByFileId((int)$source);
		if ($cacheEntry instanceof ICacheEntry) {
			return new NodeShareSourceMetadata($this->urlGenerator, $cacheEntry);
		}

		return null;
	}

	#[\Override]
	public function getSourcesMetadata(array $sources): array {
		$sources = array_map(intval(...), $sources);
		$cacheEntries = $this->fileAccess->getByFileIds($sources);
		// we actually have an `array<int, IShareSourceMetadata>` instead of an `array<non-empty-string, IShareSourceMetadata>` here,
		// but since numeric string array keys are automatically casted to ints anyway they are functionally equivalent
		/** @var array<non-empty-string, IShareSourceMetadata> $metadata */
		$metadata = array_map(fn (ICacheEntry $cacheEntry): NodeShareSourceMetadata => new NodeShareSourceMetadata($this->urlGenerator, $cacheEntry), $cacheEntries);
		return $metadata;
	}

	#[\Override]
	public function getSourceInteractionResource(IUser $user, string $source): InteractionResource {
		return new NodeResource((int)$source, $user->getUID());
	}

	#[\Override]
	public function handle(Event $event): void {
		try {
			$this->dbConnection->beginTransaction();
			$this->manager->onSourceDeleted(new ShareAccessContext(overrideChecks: true), new ShareSource(self::class, (string)$event->getNode()->getId()));
			$this->dbConnection->commit();
		} catch (Exception $exception) {
			$this->dbConnection->rollBack();
			throw $exception;
		}
	}

	#[\Override]
	public function userHasDirectSharingAccessToSource(IUser $user, string $source): bool {
		// TODO: cache nodes by id?
		$userFolder = $this->rootFolder->getUserFolder($user->getUID());
		$nodes = $userFolder->getById((int)$source);
		foreach ($nodes as $node) {
			if (!$node->getStorage() instanceof ISharedStorage && $node->isShareable() && $node->getMountPoint() instanceof IShareOwnerlessMount) {
				return true;
			}
		}

		return false;
	}
}
