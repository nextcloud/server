<?php

/**
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU AFFERO GENERAL PUBLIC LICENSE for more details.
 *
 * You should have received a copy of the GNU Affero General Public
 * License along with this library.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\Comments\Controller;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\NotFoundResponse;
use OCP\AppFramework\Http\RedirectResponse;
use OCP\AppFramework\Http\Response;
use OCP\Comments\IComment;
use OCP\Comments\ICommentsManager;
use OCP\Files\Folder;
use OCP\IRequest;
use OCP\IURLGenerator;
use OCP\IUserSession;
use OCP\Notification\IManager;

/**
 * Class Notifications
 *
 * @package OCA\Comments\Controller
 */
class Notifications extends Controller {
	/** @var Folder  */
	protected $folder;

	/** @var ICommentsManager  */
	protected $commentsManager;

	/** @var IURLGenerator  */
	protected $urlGenerator;

	/** @var IManager  */
	protected $notificationManager;

	/** @var IUserSession  */
	protected $userSession;

	/**
	 * Notifications constructor.
	 *
	 * @param string $appName
	 * @param IRequest $request
	 * @param ICommentsManager $commentsManager
	 * @param Folder $folder
	 * @param IURLGenerator $urlGenerator
	 * @param IManager $notificationManager
	 * @param IUserSession $userSession
	 */
	public function __construct(
		$appName,
		IRequest $request,
		ICommentsManager $commentsManager,
		Folder $folder,
		IURLGenerator $urlGenerator,
		IManager $notificationManager,
		IUserSession $userSession
	) {
		parent::__construct($appName, $request);
		$this->commentsManager = $commentsManager;
		$this->folder = $folder;
		$this->urlGenerator = $urlGenerator;
		$this->notificationManager = $notificationManager;
		$this->userSession = $userSession;
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 *
	 * @param string $id		the comment ID
	 * @return Response
	 */
	public function view($id) {
		try {
			$comment = $this->commentsManager->get($id);
			if($comment->getObjectType() !== 'files') {
				return new NotFoundResponse();
			}
			$files = $this->folder->getById($comment->getObjectId());
			if(count($files) === 0) {
				$this->markProcessed($comment);
				return new NotFoundResponse();
			}

			$dir = $this->folder->getRelativePath($files[0]->getParent()->getPath());
			$url = $this->urlGenerator->linkToRouteAbsolute(
				'files.view.index',
				[
					'dir'      => $dir,
					'scrollto' => $files[0]->getName()
				]
			);

			$this->markProcessed($comment);

			return new RedirectResponse($url);
		} catch (\Exception $e) {
			return new NotFoundResponse();
		}
	}

	/**
	 * Marks the notification about a comment as processed
	 * @param IComment $comment
	 */
	protected function markProcessed(IComment $comment) {
		$user = $this->userSession->getUser();
		if(is_null($user)) {
			return;
		}
		$notification = $this->notificationManager->createNotification();
		$notification->setApp('comments')
			->setObject('comment', $comment->getId())
			->setSubject('mention')
			->setUser($user->getUID());
		$this->notificationManager->markProcessed($notification);
	}
}
