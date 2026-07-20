<?php

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace OCA\Files\Sharing\Source;

use Exception;
use OCA\Files\AppInfo\Application;
use OCA\Files_Trashbin\Events\MoveToTrashEvent;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\EventDispatcher\IEventListener;
use OCP\Files\Events\Node\NodeDeletedEvent;
use OCP\Files\IRootFolder;
use OCP\Files\Node;
use OCP\IDBConnection;
use OCP\Interaction\InteractionResource;
use OCP\Interaction\Resources\NodeResource;
use OCP\IURLGenerator;
use OCP\L10N\IFactory;
use OCP\Server;
use OCP\Sharing\Icon\ShareIconURL;
use OCP\Sharing\ISharingManager;
use OCP\Sharing\ShareAccessContext;
use OCP\Sharing\Source\IShareSourceType;
use OCP\Sharing\Source\ShareSource;

/**
 * @template-implements IEventListener<NodeDeletedEvent|MoveToTrashEvent>
 */
final class NodeShareSourceType implements IShareSourceType, IEventListener {
	private ?IRootFolder $rootFolder = null;

	private ?IURLGenerator $urlGenerator = null;

	public function __construct(
		IEventDispatcher $eventDispatcher,
		private readonly IDBConnection $dbConnection,
		private readonly ISharingManager $manager,
	) {
		$eventDispatcher->addServiceListener(NodeDeletedEvent::class, self::class);
		$eventDispatcher->addServiceListener(MoveToTrashEvent::class, self::class);
	}

	private function getRootFolder(): IRootFolder {
		return $this->rootFolder ??= Server::get(IRootFolder::class);
	}

	private function getUrlGenerator(): IURLGenerator {
		return $this->urlGenerator ??= Server::get(IURLGenerator::class);
	}

	#[\Override]
	public function getDisplayName(IFactory $l10nFactory): string {
		return $l10nFactory->get(Application::APP_ID)->t('File');
	}

	#[\Override]
	public function validateSource(string $source): bool {
		return $this->getRootFolder()->getFirstNodeById((int)$source) instanceof Node;
	}

	#[\Override]
	public function getSourceDisplayName(string $source): ?string {
		$displayName = $this->getRootFolder()->getFirstNodeById((int)$source)?->getName();
		if ($displayName === '') {
			return null;
		}

		return $displayName;
	}

	#[\Override]
	public function getSourceIcon(string $source): ShareIconURL {
		$url = $this->getUrlGenerator()->linkToRouteAbsolute('core.Preview.getPreviewByFileId', ['fileId' => $source, 'x' => 64, 'y' => 64]);

		return new ShareIconURL($url, $url);
	}

	#[\Override]
	public function getSourceInteractionResource(string $userId, string $source): InteractionResource {
		return new NodeResource((int)$source, $userId);
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
}
