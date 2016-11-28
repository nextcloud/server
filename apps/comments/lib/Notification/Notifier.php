<?php
/**
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 *
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OCA\Comments\Notification;

use OCP\Comments\ICommentsManager;
use OCP\Comments\NotFoundException;
use OCP\Files\Folder;
use OCP\IURLGenerator;
use OCP\IUserManager;
use OCP\L10N\IFactory;
use OCP\Notification\INotification;
use OCP\Notification\INotifier;

class Notifier implements INotifier {

	/** @var IFactory */
	protected $l10nFactory;

	/** @var Folder  */
	protected $userFolder;

	/** @var ICommentsManager  */
	protected $commentsManager;

	/** @var IURLGenerator */
	protected $url;

	/** @var IUserManager */
	protected $userManager;

	public function __construct(
		IFactory $l10nFactory,
		Folder $userFolder,
		ICommentsManager $commentsManager,
		IURLGenerator $url,
		IUserManager $userManager
	) {
		$this->l10nFactory = $l10nFactory;
		$this->userFolder = $userFolder;
		$this->commentsManager = $commentsManager;
		$this->url = $url;
		$this->userManager = $userManager;
	}

	/**
	 * @param INotification $notification
	 * @param string $languageCode The code of the language that should be used to prepare the notification
	 * @return INotification
	 * @throws \InvalidArgumentException When the notification was not prepared by a notifier
	 */
	public function prepare(INotification $notification, $languageCode) {
		if($notification->getApp() !== 'comments') {
			throw new \InvalidArgumentException();
		}
		try {
			$comment = $this->commentsManager->get($notification->getObjectId());
		} catch(NotFoundException $e) {
			// needs to be converted to InvalidArgumentException, otherwise none Notifications will be shown at all
			throw new \InvalidArgumentException('Comment not found', 0, $e);
		}
		$l = $this->l10nFactory->get('comments', $languageCode);
		$displayName = $comment->getActorId();
		$isDeletedActor = $comment->getActorType() === ICommentsManager::DELETED_USER;
		if($comment->getActorType() === 'users') {
			$commenter = $this->userManager->get($comment->getActorId());
			if(!is_null($commenter)) {
				$displayName = $commenter->getDisplayName();
			}
		}

		switch($notification->getSubject()) {
			case 'mention':
				$parameters = $notification->getSubjectParameters();
				if($parameters[0] !== 'files') {
					throw new \InvalidArgumentException('Unsupported comment object');
				}
				$nodes = $this->userFolder->getById($parameters[1]);
				if(empty($nodes)) {
					throw new \InvalidArgumentException('Cannot resolve file id to Node instance');
				}
				$node = $nodes[0];

				if ($isDeletedActor) {
					$notification->setParsedSubject($l->t(
							'A (now) deleted user mentioned you in a comment on “%s”',
							[$node->getName()]
						))
						->setRichSubject(
							$l->t('A (now) deleted user mentioned you in a comment on “{file}”'),
							[
								'file' => [
									'type' => 'file',
									'id' => $comment->getObjectId(),
									'name' => $node->getName(),
									'path' => $node->getPath(),
									'link' => $this->url->linkToRouteAbsolute('files.viewcontroller.showFile', ['fileid' => $comment->getObjectId()]),
								],
							]
						);
				} else {
					$notification->setParsedSubject($l->t(
							'%1$s mentioned you in a comment on “%2$s”',
							[$displayName, $node->getName()]
						))
						->setRichSubject(
							$l->t('{user} mentioned you in a comment on “{file}”'),
							[
								'user' => [
									'type' => 'user',
									'id' => $comment->getActorId(),
									'name' => $displayName,
								],
								'file' => [
									'type' => 'file',
									'id' => $comment->getObjectId(),
									'name' => $node->getName(),
									'path' => $node->getPath(),
									'link' => $this->url->linkToRouteAbsolute('files.viewcontroller.showFile', ['fileid' => $comment->getObjectId()]),
								],
							]
						);
				}
				$notification->setIcon($this->url->getAbsoluteURL($this->url->imagePath('core', 'actions/comment.svg')));

				return $notification;
				break;

			default:
				throw new \InvalidArgumentException('Invalid subject');
		}

	}
}
