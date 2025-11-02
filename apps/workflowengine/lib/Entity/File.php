<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\WorkflowEngine\Entity;

use OC\Files\Config\UserMountCache;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\GenericEvent;
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
use OCP\SystemTag\ISystemTag;
use OCP\SystemTag\ISystemTagManager;
use OCP\SystemTag\MapperEvent;
use OCP\WorkflowEngine\EntityContext\IContextPortation;
use OCP\WorkflowEngine\EntityContext\IDisplayText;
use OCP\WorkflowEngine\EntityContext\IIcon;
use OCP\WorkflowEngine\EntityContext\IUrl;
use OCP\WorkflowEngine\GenericEntityEvent;
use OCP\WorkflowEngine\IEntity;
use OCP\WorkflowEngine\IRuleMatcher;

class File implements IEntity, IDisplayText, IUrl, IIcon, IContextPortation {
	private const EVENT_NAMESPACE = '\OCP\Files::';
	/** @var string */
	protected $eventName;
	/** @var Event */
	protected $event;
	/** @var ?Node */
	private $node;
	/** @var ?IUser */
	private $actingUser = null;
	/** @var UserMountCache */
	private $userMountCache;

	public function __construct(
		protected IL10N $l10n,
		protected IURLGenerator $urlGenerator,
		protected IRootFolder $root,
		private IUserSession $userSession,
		private ISystemTagManager $tagManager,
		private IUserManager $userManager,
		UserMountCache $userMountCache,
		private IMountManager $mountManager,
	) {
		$this->userMountCache = $userMountCache;
	}

	public function getName(): string {
		return $this->l10n->t('File');
	}

	public function getIcon(): string {
		return $this->urlGenerator->imagePath('core', 'categories/files.svg');
	}

	public function getEvents(): array {
		return [
			new GenericEntityEvent($this->l10n->t('File created'), self::EVENT_NAMESPACE . 'postCreate'),
			new GenericEntityEvent($this->l10n->t('File updated'), self::EVENT_NAMESPACE . 'postWrite'),
			new GenericEntityEvent($this->l10n->t('File renamed'), self::EVENT_NAMESPACE . 'postRename'),
			new GenericEntityEvent($this->l10n->t('File deleted'), self::EVENT_NAMESPACE . 'postDelete'),
			new GenericEntityEvent($this->l10n->t('File accessed'), self::EVENT_NAMESPACE . 'postTouch'),
			new GenericEntityEvent($this->l10n->t('File copied'), self::EVENT_NAMESPACE . 'postCopy'),
			new GenericEntityEvent($this->l10n->t('Tag assigned'), MapperEvent::EVENT_ASSIGN),
		];
	}

	public function prepareRuleMatcher(IRuleMatcher $ruleMatcher, string $eventName, Event $event): void {
		if (!$event instanceof GenericEvent && !$event instanceof MapperEvent) {
			return;
		}
		$this->eventName = $eventName;
		$this->event = $event;
		$this->actingUser = $this->actingUser ?? $this->userSession->getUser();
		try {
			$node = $this->getNode();
			$ruleMatcher->setEntitySubject($this, $node);
			$ruleMatcher->setFileInfo($node->getStorage(), $node->getInternalPath());
		} catch (NotFoundException $e) {
			// pass
		}
	}

	public function isLegitimatedForUserId(string $userId): bool {
		try {
			$node = $this->getNode();
			if ($node->getOwner()?->getUID() === $userId) {
				return true;
			}

			if ($this->eventName === self::EVENT_NAMESPACE . 'postDelete') {
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
		if (!$this->event instanceof GenericEvent && !$this->event instanceof MapperEvent) {
			throw new NotFoundException();
		}
		switch ($this->eventName) {
			case self::EVENT_NAMESPACE . 'postCreate':
			case self::EVENT_NAMESPACE . 'postWrite':
			case self::EVENT_NAMESPACE . 'postDelete':
			case self::EVENT_NAMESPACE . 'postTouch':
				return $this->event->getSubject();
			case self::EVENT_NAMESPACE . 'postRename':
			case self::EVENT_NAMESPACE . 'postCopy':
				return $this->event->getSubject()[1];
			case MapperEvent::EVENT_ASSIGN:
				if (!$this->event instanceof MapperEvent || $this->event->getObjectType() !== 'files') {
					throw new NotFoundException();
				}
				$nodes = $this->root->getById((int)$this->event->getObjectId());
				if (is_array($nodes) && isset($nodes[0])) {
					$this->node = $nodes[0];
					return $this->node;
				}
				break;
		}
		throw new NotFoundException();
	}

	public function getDisplayText(int $verbosity = 0): string {
		try {
			$node = $this->getNode();
		} catch (NotFoundException $e) {
			return '';
		}

		$options = [
			$this->actingUser ? $this->actingUser->getDisplayName() : $this->l10n->t('Someone'),
			$node->getName()
		];

		switch ($this->eventName) {
			case self::EVENT_NAMESPACE . 'postCreate':
				return $this->l10n->t('%s created %s', $options);
			case self::EVENT_NAMESPACE . 'postWrite':
				return $this->l10n->t('%s modified %s', $options);
			case self::EVENT_NAMESPACE . 'postDelete':
				return $this->l10n->t('%s deleted %s', $options);
			case self::EVENT_NAMESPACE . 'postTouch':
				return $this->l10n->t('%s accessed %s', $options);
			case self::EVENT_NAMESPACE . 'postRename':
				return $this->l10n->t('%s renamed %s', $options);
			case self::EVENT_NAMESPACE . 'postCopy':
				return $this->l10n->t('%s copied %s', $options);
			case MapperEvent::EVENT_ASSIGN:
				$tagNames = [];
				if ($this->event instanceof MapperEvent) {
					$tagIDs = $this->event->getTags();
					$tagObjects = $this->tagManager->getTagsByIds($tagIDs);
					foreach ($tagObjects as $systemTag) {
						/** @var ISystemTag $systemTag */
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
		}
	}

	public function getUrl(): string {
		try {
			return $this->urlGenerator->linkToRouteAbsolute('files.viewcontroller.showFile', ['fileid' => $this->getNode()->getId()]);
		} catch (InvalidPathException $e) {
			return '';
		} catch (NotFoundException $e) {
			return '';
		}
	}

	/**
	 * @inheritDoc
	 */
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
			'nodeOwnerId' => $nodeOwner ? $nodeOwner->getUID() : null,
			'actingUserId' => $actingUserId,
		];
	}

	/**
	 * @inheritDoc
	 */
	public function importContextIDs(array $contextIDs): void {
		$this->eventName = $contextIDs['eventName'];
		if ($contextIDs['nodeOwnerId'] !== null) {
			$userFolder = $this->root->getUserFolder($contextIDs['nodeOwnerId']);
			$nodes = $userFolder->getById($contextIDs['nodeId']);
		} else {
			$nodes = $this->root->getById($contextIDs['nodeId']);
		}
		$this->node = $nodes[0] ?? null;
		if ($contextIDs['actingUserId']) {
			$this->actingUser = $this->userManager->get($contextIDs['actingUserId']);
		}
	}

	/**
	 * @inheritDoc
	 */
	public function getIconUrl(): string {
		return $this->getIcon();
	}
}
