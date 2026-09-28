<?php

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Share20;

use OCP\EventDispatcher\IEventDispatcher;
use OCP\Files\File;
use OCP\Share;
use OCP\Share\Events\BeforeShareCreatedEvent;
use OCP\Share\Events\BeforeShareDeletedEvent;
use OCP\Share\Events\ShareCreatedEvent;
use OCP\Share\Events\ShareDeletedEvent;
use OCP\Share\Events\ShareDeletedFromSelfEvent;
use OCP\Share\IShare;

class LegacyHooks {
	/** @var IEventDispatcher */
	private $eventDispatcher;

	public function __construct(IEventDispatcher $eventDispatcher) {
		$this->eventDispatcher = $eventDispatcher;

		$this->eventDispatcher->addListener(BeforeShareDeletedEvent::class, function (BeforeShareDeletedEvent $event) {
			$this->preUnshare($event);
		});
		$this->eventDispatcher->addListener(ShareDeletedEvent::class, function (ShareDeletedEvent $event) {
			$this->postUnshare($event);
		});
		$this->eventDispatcher->addListener(ShareDeletedFromSelfEvent::class, function (ShareDeletedFromSelfEvent $event) {
			$this->postUnshareFromSelf($event);
		});
		$this->eventDispatcher->addListener(BeforeShareCreatedEvent::class, function (BeforeShareCreatedEvent $event) {
			$this->preShare($event);
		});
		$this->eventDispatcher->addListener(ShareCreatedEvent::class, function (ShareCreatedEvent $event) {
			$this->postShare($event);
		});
	}

	public function preUnshare(BeforeShareDeletedEvent $e) {
		$share = $e->getShare();

		$formatted = $this->formatHookParams($share);
		\OC_Hook::emit(Share::class, 'pre_unshare', $formatted);
	}

	public function postUnshare(ShareDeletedEvent $e) {
		$share = $e->getShare();

		$formatted = $this->formatHookParams($share);
		$formatted['deletedShares'] = [$formatted];

		\OC_Hook::emit(Share::class, 'post_unshare', $formatted);
	}

	public function postUnshareFromSelf(ShareDeletedFromSelfEvent $e) {
		$share = $e->getShare();

		$formatted = $this->formatHookParams($share);
		$formatted['itemTarget'] = $formatted['fileTarget'];
		$formatted['unsharedItems'] = [$formatted];

		\OC_Hook::emit(Share::class, 'post_unshareFromSelf', $formatted);
	}

	private function formatHookParams(IShare $share) {
		// Prepare hook
		$shareType = $share->getShareType();
		$sharedWith = '';
		if ($shareType === IShare::TYPE_USER
			|| $shareType === IShare::TYPE_GROUP
			|| $shareType === IShare::TYPE_REMOTE) {
			$sharedWith = $share->getSharedWith();
		}

		$hookParams = [
			'id' => $share->getId(),
			'itemType' => $share->getNodeType(),
			'itemSource' => $share->getNodeId(),
			'shareType' => $shareType,
			'shareWith' => $sharedWith,
			'itemparent' => $share->getParent(),
			'uidOwner' => $share->getSharedBy(),
			'fileSource' => $share->getNodeId(),
			'fileTarget' => $share->getTarget()
		];
		return $hookParams;
	}

	public function preShare(BeforeShareCreatedEvent $e) {
		$share = $e->getShare();

		// Pre share hook
		$run = true;
		$error = '';
		$preHookData = [
			'itemType' => $share->getNode() instanceof File ? 'file' : 'folder',
			'itemSource' => $share->getNode()->getId(),
			'shareType' => $share->getShareType(),
			'uidOwner' => $share->getSharedBy(),
			'permissions' => $share->getPermissions(),
			'fileSource' => $share->getNode()->getId(),
			'expiration' => $share->getExpirationDate(),
			'token' => $share->getToken(),
			'itemTarget' => $share->getTarget(),
			'shareWith' => $share->getSharedWith(),
			'run' => &$run,
			'error' => &$error,
		];
		\OC_Hook::emit(Share::class, 'pre_shared', $preHookData);

		if ($run === false) {
			$e->setError($error);
			$e->stopPropagation();
		}

		return $e;
	}

	public function postShare(ShareCreatedEvent $e) {
		$share = $e->getShare();

		$postHookData = [
			'itemType' => $share->getNode() instanceof File ? 'file' : 'folder',
			'itemSource' => $share->getNode()->getId(),
			'shareType' => $share->getShareType(),
			'uidOwner' => $share->getSharedBy(),
			'permissions' => $share->getPermissions(),
			'fileSource' => $share->getNode()->getId(),
			'expiration' => $share->getExpirationDate(),
			'token' => $share->getToken(),
			'id' => $share->getId(),
			'shareWith' => $share->getSharedWith(),
			'itemTarget' => $share->getTarget(),
			'fileTarget' => $share->getTarget(),
			'path' => $share->getNode()->getPath(),
		];

		\OC_Hook::emit(Share::class, 'post_shared', $postHookData);
	}
}
