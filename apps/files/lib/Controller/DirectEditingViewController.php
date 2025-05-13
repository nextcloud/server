<?php
/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Files\Controller;

use Exception;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\Attribute\OpenAPI;
use OCP\AppFramework\Http\Attribute\PublicPage;
use OCP\AppFramework\Http\Attribute\UseSession;
use OCP\AppFramework\Http\NotFoundResponse;
use OCP\AppFramework\Http\Response;
use OCP\AppFramework\Http\RedirectResponse;
use OCP\DirectEditing\IManager;
use OCP\DirectEditing\RegisterDirectEditorEvent;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\IRequest;
use OCP\IURLGenerator;
use OC\User\Session;
use Psr\Log\LoggerInterface;

#[OpenAPI(scope: OpenAPI::SCOPE_IGNORE)]
class DirectEditingViewController extends Controller {
	public function __construct(
		$appName,
		IRequest $request,
		private IEventDispatcher $eventDispatcher,
		private IManager $directEditingManager,
		private LoggerInterface $logger,
		private IRootFolder $rootFolder,
		private IURLGenerator $urlGenerator,
		private Session $userSession
	) {
		parent::__construct($appName, $request);
		// check login status
		$this->userIsLoggedIn = $userSession->isLoggedIn()??null;
	}

	/**
	 * @param string $token
	 * @return Response
	 */
	#[PublicPage]
	#[NoCSRFRequired]
	#[UseSession]
	public function edit(string $token): Response {
		$this->eventDispatcher->dispatchTyped(new RegisterDirectEditorEvent($this->directEditingManager));
		try {
			$editorTokenString = $token;
			$token = $this->directEditingManager->getToken($token);

			// try loading a direct file view in case authenticated browser instances are detected
			if ($this->userIsLoggedIn) {
				$baseFolder = $this->rootFolder->getUserFolder($this->userSession->getLoginName());
				$node = $baseFolder->getFirstNodeById($token->getFile()->getId());
				// if the logged in user has reading permissions proceed
				if ($node && $node->isReadable()) {
					$params = ['view' => 'files'];
					if ($node instanceof Folder) {
						// set the full path to enter the folder
						$params['dir'] = $baseFolder->getRelativePath($node->getPath());
					} else {
						// set parent path as dir
						$params['dir'] = $baseFolder->getRelativePath($node->getParent()->getPath());
						// open the file by default (opening the viewer)
						$params['openfile'] = 'true';
					}
					$params['fileid'] = $token->getFile()->getId();

					return new RedirectResponse($this->urlGenerator->linkToRoute('files.view.indexViewFileid', $params));
				} else {
					// otherwise, fall back graciously without disclosing the requested file's path (it appears the files app checks permissions on its own, if active sessions are detected, blocking the direct viewer's content rendering)

					return new NotFoundResponse();
				}
			}
			// return token based view in case logged out/mobile client sessions
			$token->useTokenScope();

			return $this->directEditingManager->edit($editorTokenString);
		} catch (Exception $e) {
			$this->logger->error($e->getMessage(), ['exception' => $e]);
			return new NotFoundResponse();
		}
	}
}
