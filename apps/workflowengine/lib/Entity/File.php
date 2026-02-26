<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\WorkflowEngine\Entity;

use OC\Files\Config\UserMountCache;
use OC\SystemTag\Events\SingleTagAssignedEvent;
use OCP\EventDispatcher\Event;
use OCP\Files\Events\Node\AbstractNodeEvent;
use OCP\Files\Events\Node\NodeCopiedEvent;
use OCP\Files\Events\Node\NodeCreatedEvent;
use OCP\Files\Events\Node\NodeDeletedEvent;
use OCP\Files\Events\Node\NodeRenamedEvent;
use OCP\Files\Events\Node\NodeTouchedEvent;
use OCP\Files\Events\Node\NodeUpdatedEvent;
use OCP\Files\InvalidPathException;
use OCP\Files\IRootFolder;
use OCP\Files\Mount\IMountManager;
use OCP\Files\Node;
use OCP\Files\NotFoundException;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\IUserManager;
use OCP\IUserSession;
use OCP\SystemTag\ISystemTagManager;
use OCP\WorkflowEngine\EntityContext\IContextPortation;
use OCP\WorkflowEngine\EntityContext\IDisplayText;
use OCP\WorkflowEngine\EntityContext\IIcon;
use OCP\WorkflowEngine\EntityContext\IUrl;
use OCP\WorkflowEngine\GenericEntityEvent;
use OCP\WorkflowEngine\IEntity;
use OCP\WorkflowEngine\IRuleMatcher;
use Override;

class File implements IEntity, IDisplayText, IUrl, IIcon, IContextPortation {
	/** @var ?class-string<Event> $eventName */
	protected ?string $eventName = null;
	protected ?Event $event = null;
	private ?Node $node = null;
	private ?IUser $actingUser = null;

	public function __construct(
		protected readonly IL10N $l10n,
		protected readonly IURLGenerator $urlGenerator,
		protected readonly IRootFolder $root,
		private readonly IUserSession $userSession,
		private readonly ISystemTagManager $tagManager,
		private readonly IUserManager $userManager,
		private readonly UserMountCache $userMountCache,
		private readonly IMountManager $mountManager,
	) {
	}

	#[Override]
	public function getName(): string {
		return $this->l10n->t('File');
	}

	#[Override]
	public function getIcon(): string {
		return $this->urlGenerator->imagePath('core', 'categories/files.svg');
	}

	#[Override]
	public function getEvents(): array {
		return [
			new GenericEntityEvent($this->l10n->t('File created'), NodeCreatedEvent::class),
			new GenericEntityEvent($this->l10n->t('File updated'), NodeUpdatedEvent::class),
			new GenericEntityEvent($this->l10n->t('File renamed'), NodeRenamedEvent::class),
			new GenericEntityEvent($this->l10n->t('File deleted'), NodeDeletedEvent::class),
			new GenericEntityEvent($this->l10n->t('File accessed'), NodeTouchedEvent::class),
			new GenericEntityEvent($this->l10n->t('File copied'), NodeCopiedEvent::class),
			new GenericEntityEvent($this->l10n->t('Tag assigned'), SingleTagAssignedEvent::class),
		];
	}

	#[Override]
	public function prepareRuleMatcher(IRuleMatcher $ruleMatcher, string $eventName, Event $event): void {
		$isSupported = array_any($this->getEvents(), static fn (GenericEntityEvent $genericEvent): bool => is_a($event, $genericEvent->getEventName()));
		if (!$isSupported) {
			return;
		}

		$this->eventName = $eventName;
		$this->event = $event;
		$this->actingUser = $this->actingUser ?? $this->userSession->getUser();
		try {
			$node = $this->getNode();
			$ruleMatcher->setEntitySubject($this, $node);
			$ruleMatcher->setFileInfo($node->getStorage(), $node->getInternalPath());
		} catch (NotFoundException) {
			// pass
		}
	}

	#[Override]
	public function isLegitimatedForUserId(string $userId): bool {
		try {
			$node = $this->getNode();
			if ($node->getOwner()?->getUID() === $userId) {
				return true;
			}

			if ($this->eventName === NodeDeletedEvent::class) {
				// At postDelete, the file no longer exists. Check for parent folder instead.
				$fileId = $node->getParentId();
			} else {
				$fileId = $node->getId();
			}

			$mountInfos = $this->userMountCache->getMountsForFileId($fileId, $userId);
			foreach ($mountInfos as $mountInfo) {
				$mount = $this->mountManager->getMountFromMountInfo($mountInfo);
				if ($mount && $mount->getStorage() && !empty($mount->getStorage()->getCache()->get($fileId))) {
					return true;
				}
			}
			return false;
		} catch (NotFoundException $e) {
			return false;
		}
	}

	/**
	 * @throws NotFoundException
	 */
	protected function getNode(): Node {
		if ($this->node) {
			return $this->node;
		}

		if ($this->event instanceof AbstractNodeEvent) {
			return $this->event->getNode();
		}

		if (!$this->event instanceof SingleTagAssignedEvent || $this->event->getObjectType() !== 'files') {
			throw new NotFoundException();
		}

		$this->node = $this->root->getFirstNodeById((int)$this->event->getObjectId());
		if ($this->node === null) {
			throw new NotFoundException();
		}

		return $this->node;
	}

	public function getDisplayText(int $verbosity = 0): string {
		try {
			$node = $this->getNode();
		} catch (NotFoundException) {
			return '';
		}

		$options = [
			$this->actingUser ? $this->actingUser->getDisplayName() : $this->l10n->t('Someone'),
			$node->getName()
		];

		switch ($this->eventName) {
			case NodeCreatedEvent::class:
				return $this->l10n->t('%s created %s', $options);
			case NodeUpdatedEvent::class:
				return $this->l10n->t('%s modified %s', $options);
			case NodeDeletedEvent::class:
				return $this->l10n->t('%s deleted %s', $options);
			case NodeTouchedEvent::class:
				return $this->l10n->t('%s accessed %s', $options);
			case NodeRenamedEvent::class:
				return $this->l10n->t('%s renamed %s', $options);
			case NodeCopiedEvent::class:
				return $this->l10n->t('%s copied %s', $options);
			case SingleTagAssignedEvent::class:
				$tagNames = [];
				if ($this->event instanceof SingleTagAssignedEvent) {
					$tagIDs = $this->event->getTags();
					$tagObjects = $this->tagManager->getTagsByIds($tagIDs);
					foreach ($tagObjects as $systemTag) {
						if ($systemTag->isUserVisible()) {
							$tagNames[] = $systemTag->getName();
						}
					}
				}
				$filename = array_pop($options);
				$tagString = implode(', ', $tagNames);
				if ($tagString === '') {
					return '';
				}
				array_push($options, $tagString, $filename);
				return $this->l10n->t('%1$s assigned %2$s to %3$s', $options);
			default:
				return '';
		}
	}

	public function getUrl(): string {
		try {
			return $this->urlGenerator->linkToRouteAbsolute('files.viewcontroller.showFile', ['fileid' => $this->getNode()->getId()]);
		} catch (InvalidPathException|NotFoundException) {
			return '';
		}
	}

	#[Override]
	public function exportContextIDs(): array {
		$nodeOwner = $this->getNode()->getOwner();
		$actingUserId = null;
		if ($this->actingUser instanceof IUser) {
			$actingUserId = $this->actingUser->getUID();
		} elseif ($this->userSession->getUser() instanceof IUser) {
			$actingUserId = $this->userSession->getUser()->getUID();
		}
		return [
			'eventName' => $this->eventName,
			'nodeId' => $this->getNode()->getId(),
			'nodeOwnerId' => $nodeOwner?->getUID(),
			'actingUserId' => $actingUserId,
		];
	}

	#[Override]
	public function importContextIDs(array $contextIDs): void {
		$this->eventName = $contextIDs['eventName'];
		if ($contextIDs['nodeOwnerId'] !== null) {
			$userFolder = $this->root->getUserFolder($contextIDs['nodeOwnerId']);
			$node = $userFolder->getFirstNodeById($contextIDs['nodeId']);
		} else {
			$node = $this->root->getFirstNodeById($contextIDs['nodeId']);
		}
		$this->node = $node;
		if ($contextIDs['actingUserId']) {
			$this->actingUser = $this->userManager->get($contextIDs['actingUserId']);
		}
	}

	#[Override]
	public function getIconUrl(): string {
		return $this->getIcon();
	}
}
