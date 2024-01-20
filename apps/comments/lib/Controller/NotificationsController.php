<?php
/**
 * @copyright Copyright (c) 2016 Arthur Schiwon <blizzz@arthur-schiwon.de>
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Joas Schilling <coding@schilljs.com>
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
namespace OCA\Comments\Controller;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\OpenAPI;
use OCP\AppFramework\Http\NotFoundResponse;
use OCP\AppFramework\Http\RedirectResponse;
use OCP\Comments\IComment;
use OCP\Comments\ICommentsManager;
use OCP\Files\IRootFolder;
use OCP\IRequest;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\IUserSession;
use OCP\Notification\IManager;

/**
 * @package OCA\Comments\Controller
 */
#[OpenAPI(scope: OpenAPI::SCOPE_IGNORE)]
class NotificationsController extends Controller {
	public function __construct(
		string $appName,
		IRequest $request,
		protected ICommentsManager $commentsManager,
		protected IRootFolder $rootFolder,
		protected IURLGenerator $urlGenerator,
		protected IManager $notificationManager,
		protected IUserSession $userSession
	) {
		parent::__construct($appName, $request);
	}

	/**
	 * @PublicPage
	 * @NoCSRFRequired
	 *
	 * View a notification
	 *
	 * @param string $id ID of the notification
	 *
	 * @return RedirectResponse<Http::STATUS_SEE_OTHER, array{}>|NotFoundResponse<Http::STATUS_NOT_FOUND, array{}>
	 *
	 * 303: Redirected to notification
	 * 404: Notification not found
	 */
	public function view(string $id): RedirectResponse|NotFoundResponse {
		$currentUser = $this->userSession->getUser();
		if (!$currentUser instanceof IUser) {
			return new RedirectResponse(
				$this->urlGenerator->linkToRoute('core.login.showLoginForm', [
					'redirect_url' => $this->urlGenerator->linkToRoute(
						'comments.Notifications.view',
						['id' => $id]
					),
				])
			);
		}

		try {
			$comment = $this->commentsManager->get($id);
			if ($comment->getObjectType() !== 'files') {
				return new NotFoundResponse();
			}
			$userFolder = $this->rootFolder->getUserFolder($currentUser->getUID());
			$files = $userFolder->getById((int)$comment->getObjectId());

			$this->markProcessed($comment, $currentUser);

			if (empty($files)) {
				return new NotFoundResponse();
			}

			$url = $this->urlGenerator->linkToRouteAbsolute(
				'files.viewcontroller.showFile',
				[ 'fileid' => $comment->getObjectId() ]
			);

			return new RedirectResponse($url);
		} catch (\Exception $e) {
			return new NotFoundResponse();
		}
	}

	/**
	 * Marks the notification about a comment as processed
	 */
	protected function markProcessed(IComment $comment, IUser $currentUser): void {
		$notification = $this->notificationManager->createNotification();
		$notification->setApp('comments')
			->setObject('comment', $comment->getId())
			->setSubject('mention')
			->setUser($currentUser->getUID());
		$this->notificationManager->markProcessed($notification);
	}
}
