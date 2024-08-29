<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Daniel Kesselberg <mail@danielkesselberg.de>
 * @author fnuesse <felix.nuesse@t-online.de>
 * @author fnuesse <fnuesse@techfak.uni-bielefeld.de>
 * @author Joas Schilling <coding@schilljs.com>
 * @author John Molakvoæ <skjnldsv@protonmail.com>
 * @author Julius Härtl <jus@bitgrid.net>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Max Kovalenko <mxss1998@yandex.ru>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Nina Pypchenko <22447785+nina-py@users.noreply.github.com>
 * @author Robin Appelman <robin@icewind.nl>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author Vincent Petry <vincent@nextcloud.com>
 *
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 */
namespace OCA\Files\Controller;

use OCA\Files\AppInfo\Application;
use OCA\Files\Event\LoadAdditionalScriptsEvent;
use OCA\Files\Event\LoadSearchPlugins;
use OCA\Files\Event\LoadSidebar;
use OCA\Files\Service\UserConfig;
use OCA\Files\Service\ViewConfig;
use OCA\Viewer\Event\LoadViewer;
use OCP\App\IAppManager;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Attribute\OpenAPI;
use OCP\AppFramework\Http\ContentSecurityPolicy;
use OCP\AppFramework\Http\RedirectResponse;
use OCP\AppFramework\Http\Response;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Services\IInitialState;
use OCP\Collaboration\Resources\LoadAdditionalScriptsEvent as ResourcesLoadAdditionalScriptsEvent;
use OCP\Constants;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\Files\NotFoundException;
use OCP\Files\Template\ITemplateManager;
use OCP\IConfig;
use OCP\IRequest;
use OCP\IURLGenerator;
use OCP\IUserSession;

/**
 * @package OCA\Files\Controller
 */
#[OpenAPI(scope: OpenAPI::SCOPE_IGNORE)]
class ViewController extends Controller {
	private IURLGenerator $urlGenerator;
	private IConfig $config;
	private IEventDispatcher $eventDispatcher;
	private IUserSession $userSession;
	private IAppManager $appManager;
	private IRootFolder $rootFolder;
	private IInitialState $initialState;
	private ITemplateManager $templateManager;
	private UserConfig $userConfig;
	private ViewConfig $viewConfig;

	public function __construct(string $appName,
		IRequest $request,
		IURLGenerator $urlGenerator,
		IConfig $config,
		IEventDispatcher $eventDispatcher,
		IUserSession $userSession,
		IAppManager $appManager,
		IRootFolder $rootFolder,
		IInitialState $initialState,
		ITemplateManager $templateManager,
		UserConfig $userConfig,
		ViewConfig $viewConfig,
	) {
		parent::__construct($appName, $request);
		$this->urlGenerator = $urlGenerator;
		$this->config = $config;
		$this->eventDispatcher = $eventDispatcher;
		$this->userSession = $userSession;
		$this->appManager = $appManager;
		$this->rootFolder = $rootFolder;
		$this->initialState = $initialState;
		$this->templateManager = $templateManager;
		$this->userConfig = $userConfig;
		$this->viewConfig = $viewConfig;
	}

	/**
	 * FIXME: Replace with non static code
	 *
	 * @return array
	 * @throws \OCP\Files\NotFoundException
	 */
	protected function getStorageInfo(string $dir = '/') {
		$rootInfo = \OC\Files\Filesystem::getFileInfo('/', false);

		return \OC_Helper::getStorageInfo($dir, $rootInfo ?: null);
	}

	/**
	 * @NoCSRFRequired
	 * @NoAdminRequired
	 *
	 * @param string $fileid
	 * @return TemplateResponse|RedirectResponse
	 */
	public function showFile(?string $fileid = null, ?string $openfile = null): Response {
		if (!$fileid) {
			return new RedirectResponse($this->urlGenerator->linkToRoute('files.view.index'));
		}

		// This is the entry point from the `/f/{fileid}` URL which is hardcoded in the server.
		try {
			return $this->redirectToFile((int) $fileid, $openfile);
		} catch (NotFoundException $e) {
			// Keep the fileid even if not found, it will be used
			// to detect the file could not be found and warn the user
			return new RedirectResponse($this->urlGenerator->linkToRoute('files.view.indexViewFileid', ['fileid' => $fileid, 'view' => 'files']));
		}
	}


	/**
	 * @NoCSRFRequired
	 * @NoAdminRequired
	 *
	 * @param string $dir
	 * @param string $view
	 * @param string $fileid
	 * @return TemplateResponse|RedirectResponse
	 */
	public function indexView($dir = '', $view = '', $fileid = null) {
		return $this->index($dir, $view, $fileid);
	}

	/**
	 * @NoCSRFRequired
	 * @NoAdminRequired
	 *
	 * @param string $dir
	 * @param string $view
	 * @param string $fileid
	 * @return TemplateResponse|RedirectResponse
	 */
	public function indexViewFileid($dir = '', $view = '', $fileid = null) {
		return $this->index($dir, $view, $fileid);
	}

	/**
	 * @NoCSRFRequired
	 * @NoAdminRequired
	 *
	 * @param string $dir
	 * @param string $view
	 * @param string $fileid
	 * @return TemplateResponse|RedirectResponse
	 */
	public function index($dir = '', $view = '', $fileid = null) {
		if ($fileid !== null && $view !== 'trashbin') {
			try {
				return $this->redirectToFileIfInTrashbin((int) $fileid);
			} catch (NotFoundException $e) {
			}
		}

		// Load the files we need
		\OCP\Util::addInitScript('files', 'init');
		\OCP\Util::addStyle('files', 'merged');
		\OCP\Util::addScript('files', 'main');

		$userId = $this->userSession->getUser()->getUID();

		// If the file doesn't exists in the folder and
		// exists in only one occurrence, redirect to that file
		// in the correct folder
		if ($fileid && $dir !== '') {
			$baseFolder = $this->rootFolder->getUserFolder($userId);
			$nodes = $baseFolder->getById((int) $fileid);
			if (!empty($nodes)) {
				$nodePath = $baseFolder->getRelativePath($nodes[0]->getPath());
				$relativePath = $nodePath ? dirname($nodePath) : '';
				// If the requested path does not contain the file id
				// or if the requested path is not the file id itself
				if (count($nodes) === 1 && $relativePath !== $dir && $nodePath !== $dir) {
					return $this->redirectToFile((int) $fileid);
				}
			}
		}

		try {
			// If view is files, we use the directory, otherwise we use the root storage
			$storageInfo = $this->getStorageInfo(($view === 'files' && $dir) ? $dir : '/');
		} catch(\Exception $e) {
			$storageInfo = $this->getStorageInfo();
		}

		$this->initialState->provideInitialState('storageStats', $storageInfo);
		$this->initialState->provideInitialState('config', $this->userConfig->getConfigs());
		$this->initialState->provideInitialState('viewConfigs', $this->viewConfig->getConfigs());

		// File sorting user config
		$filesSortingConfig = json_decode($this->config->getUserValue($userId, 'files', 'files_sorting_configs', '{}'), true);
		$this->initialState->provideInitialState('filesSortingConfig', $filesSortingConfig);

		// Forbidden file characters
		/** @var string[] */
		$forbiddenCharacters = $this->config->getSystemValue('forbidden_chars', []);
		$this->initialState->provideInitialState('forbiddenCharacters', Constants::FILENAME_INVALID_CHARS . implode('', $forbiddenCharacters));

		$event = new LoadAdditionalScriptsEvent();
		$this->eventDispatcher->dispatchTyped($event);
		$this->eventDispatcher->dispatchTyped(new ResourcesLoadAdditionalScriptsEvent());
		$this->eventDispatcher->dispatchTyped(new LoadSidebar());
		$this->eventDispatcher->dispatchTyped(new LoadSearchPlugins());
		// Load Viewer scripts
		if (class_exists(LoadViewer::class)) {
			$this->eventDispatcher->dispatchTyped(new LoadViewer());
		}

		$this->initialState->provideInitialState('templates_path', $this->templateManager->hasTemplateDirectory() ? $this->templateManager->getTemplatePath() : false);
		$this->initialState->provideInitialState('templates', $this->templateManager->listCreators());

		$response = new TemplateResponse(
			Application::APP_ID,
			'index',
		);
		$policy = new ContentSecurityPolicy();
		$policy->addAllowedFrameDomain('\'self\'');
		// Allow preview service worker
		$policy->addAllowedWorkerSrcDomain('\'self\'');
		$response->setContentSecurityPolicy($policy);

		return $response;
	}

	/**
	 * Redirects to the trashbin file list and highlight the given file id
	 *
	 * @param int $fileId file id to show
	 * @return RedirectResponse redirect response or not found response
	 * @throws NotFoundException
	 */
	private function redirectToFileIfInTrashbin($fileId): RedirectResponse {
		$uid = $this->userSession->getUser()->getUID();
		$baseFolder = $this->rootFolder->getUserFolder($uid);
		$node = $baseFolder->getFirstNodeById($fileId);
		$params = [];

		if (!$node && $this->appManager->isEnabledForUser('files_trashbin')) {
			/** @var Folder */
			$baseFolder = $this->rootFolder->get($uid . '/files_trashbin/files/');
			$node = $baseFolder->getFirstNodeById($fileId);
			$params['view'] = 'trashbin';

			if ($node) {
				$params['fileid'] = $fileId;
				if ($node instanceof Folder) {
					// set the full path to enter the folder
					$params['dir'] = $baseFolder->getRelativePath($node->getPath());
				} else {
					// set parent path as dir
					$params['dir'] = $baseFolder->getRelativePath($node->getParent()->getPath());
				}
				return new RedirectResponse($this->urlGenerator->linkToRoute('files.view.indexViewFileid', $params));
			}
		}
		throw new NotFoundException();
	}

	/**
	 * Redirects to the file list and highlight the given file id
	 *
	 * @param int $fileId file id to show
	 * @param string|null $openFile open file parameter
	 * @return RedirectResponse redirect response or not found response
	 * @throws NotFoundException
	 */
	private function redirectToFile(int $fileId, ?string $openFile = null): RedirectResponse {
		$uid = $this->userSession->getUser()->getUID();
		$baseFolder = $this->rootFolder->getUserFolder($uid);
		$node = $baseFolder->getFirstNodeById($fileId);
		$params = ['view' => 'files'];

		try {
			$this->redirectToFileIfInTrashbin($fileId);
		} catch (NotFoundException $e) {
		}

		if ($node) {
			$params['fileid'] = $fileId;
			if ($node instanceof Folder) {
				// set the full path to enter the folder
				$params['dir'] = $baseFolder->getRelativePath($node->getPath());
			} else {
				// set parent path as dir
				$params['dir'] = $baseFolder->getRelativePath($node->getParent()->getPath());
				// open the file by default (opening the viewer)
				$params['openfile'] = 'true';
			}

			// Forward openfile parameters if any.
			// will be evaluated as truthy
			if ($openFile !== null) {
				$params['openfile'] = $openFile !== 'false' ? 'true' : 'false';
			}

			return new RedirectResponse($this->urlGenerator->linkToRoute('files.view.indexViewFileid', $params));
		}

		throw new NotFoundException();
	}
}
