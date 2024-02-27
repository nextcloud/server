<?php
/**
 * @copyright 2017, Roeland Jago Douma <roeland@famdouma.nl>
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Pauli Järvinen <pauli.jarvinen@gmail.com>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
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
		if ($shareType === IShare::TYPE_USER ||
			$shareType === IShare::TYPE_GROUP ||
			$shareType === IShare::TYPE_REMOTE) {
			$sharedWith = $share->getSharedWith();
		}

		$hookParams = [
			'id' => $share->getId(),
			'itemType' => $share->getNodeType(),
			'itemSource' => $share->getNodeId(),
			'shareType' => $shareType,
			'shareWith' => $sharedWith,
			'itemparent' => method_exists($share, 'getParent') ? $share->getParent() : '',
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
