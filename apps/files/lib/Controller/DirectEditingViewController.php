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
use OCP\Files\IRootFolder;
use OCP\IRequest;
use OCP\IURLGenerator;
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
		private IURLGenerator $urlGenerator
	) {
		parent::__construct($appName, $request);
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
		$token = $this->directEditingManager->getToken($token);
		$token->useTokenScope();
		try {
			$params = ['view' => 'files'];

			$baseFolder = $this->rootFolder->getUserFolder($token->getUser());
			$node = $baseFolder->getFirstNodeById($token->getFile()->getId());

			if ($node) {
				if ($node instanceof Folder) {
					// set the full path to enter the folder
					$params['dir'] = $baseFolder->getRelativePath($node->getPath());
				} else {
					// set parent path as dir
					$params['dir'] = $baseFolder->getRelativePath($node->getParent()->getPath());
					// open the file by default (opening the viewer)
					$params['openfile'] = 'true';
				}
			}
			$params['fileid'] = $token->getFile()->getId();

			return new RedirectResponse($this->urlGenerator->linkToRoute('files.view.indexViewFileid', $params));

		} catch (Exception $e) {
			$this->logger->error($e->getMessage(), ['exception' => $e]);
			return new NotFoundResponse();
		}
	}
}
