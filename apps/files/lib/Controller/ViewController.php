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

use OCA\Files\Activity\Helper;
use OCA\Files\AppInfo\Application;
use OCA\Files\Event\LoadAdditionalScriptsEvent;
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
use OCP\IL10N;
use OCP\IRequest;
use OCP\IURLGenerator;
use OCP\IUserSession;
use OCP\Share\IManager;

/**
 * @package OCA\Files\Controller
 */
#[OpenAPI(scope: OpenAPI::SCOPE_IGNORE)]
class ViewController extends Controller {
	private IURLGenerator $urlGenerator;
	private IL10N $l10n;
	private IConfig $config;
	private IEventDispatcher $eventDispatcher;
	private IUserSession $userSession;
	private IAppManager $appManager;
	private IRootFolder $rootFolder;
	private Helper $activityHelper;
	private IInitialState $initialState;
	private ITemplateManager $templateManager;
	private IManager $shareManager;
	private UserConfig $userConfig;
	private ViewConfig $viewConfig;

	public function __construct(string $appName,
		IRequest $request,
		IURLGenerator $urlGenerator,
		IL10N $l10n,
		IConfig $config,
		IEventDispatcher $eventDispatcher,
		IUserSession $userSession,
		IAppManager $appManager,
		IRootFolder $rootFolder,
		Helper $activityHelper,
		IInitialState $initialState,
		ITemplateManager $templateManager,
		IManager $shareManager,
		UserConfig $userConfig,
		ViewConfig $viewConfig
	) {
		parent::__construct($appName, $request);
		$this->urlGenerator = $urlGenerator;
		$this->l10n = $l10n;
		$this->config = $config;
		$this->eventDispatcher = $eventDispatcher;
		$this->userSession = $userSession;
		$this->appManager = $appManager;
		$this->rootFolder = $rootFolder;
		$this->activityHelper = $activityHelper;
		$this->initialState = $initialState;
		$this->templateManager = $templateManager;
		$this->shareManager = $shareManager;
		$this->userConfig = $userConfig;
		$this->viewConfig = $viewConfig;
	}

	/**
	 * @param string $appName
	 * @param string $scriptName
	 * @return string
	 */
	protected function renderScript($appName, $scriptName) {
		$content = '';
		$appPath = \OC_App::getAppPath($appName);
		$scriptPath = $appPath . '/' . $scriptName;
		if (file_exists($scriptPath)) {
			// TODO: sanitize path / script name ?
			ob_start();
			include $scriptPath;
			$content = ob_get_contents();
			@ob_end_clean();
		}

		return $content;
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
	public function showFile(string $fileid = null): Response {
		if (!$fileid) {
			return new RedirectResponse($this->urlGenerator->linkToRoute('files.view.index'));
		}

		// This is the entry point from the `/f/{fileid}` URL which is hardcoded in the server.
		try {
			return $this->redirectToFile((int) $fileid);
		} catch (NotFoundException $e) {
			return new RedirectResponse($this->urlGenerator->linkToRoute('files.view.index', ['fileNotFound' => true]));
		}
	}


	/**
	 * @NoCSRFRequired
	 * @NoAdminRequired
	 * @UseSession
	 *
	 * @param string $dir
	 * @param string $view
	 * @param string $fileid
	 * @param bool $fileNotFound
	 * @return TemplateResponse|RedirectResponse
	 */
	public function indexView($dir = '', $view = '', $fileid = null, $fileNotFound = false) {
		return $this->index($dir, $view, $fileid, $fileNotFound);
	}

	/**
	 * @NoCSRFRequired
	 * @NoAdminRequired
	 * @UseSession
	 *
	 * @param string $dir
	 * @param string $view
	 * @param string $fileid
	 * @param bool $fileNotFound
	 * @return TemplateResponse|RedirectResponse
	 */
	public function indexViewFileid($dir = '', $view = '', $fileid = null, $fileNotFound = false) {
		return $this->index($dir, $view, $fileid, $fileNotFound);
	}

	/**
	 * @NoCSRFRequired
	 * @NoAdminRequired
	 * @UseSession
	 *
	 * @param string $dir
	 * @param string $view
	 * @param string $fileid
	 * @param bool $fileNotFound
	 * @return TemplateResponse|RedirectResponse
	 */
	public function index($dir = '', $view = '', $fileid = null, $fileNotFound = false) {
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

		// Get all the user favorites to create a submenu
		try {
			$userFolder = $this->rootFolder->getUserFolder($userId);
			$favElements = $this->activityHelper->getFavoriteNodes($userId, true);
			$favElements = array_map(fn (Folder $node) => [
				'fileid' => $node->getId(),
				'path' => $userFolder->getRelativePath($node->getPath()),
			], $favElements);
		} catch (\RuntimeException $e) {
			$favElements = [];
		}

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
			} else { // fileid does not exist anywhere
				$fileNotFound = true;
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
		$this->initialState->provideInitialState('favoriteFolders', $favElements);

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

		$this->provideInitialState($dir, $fileid);

		return $response;
	}

	/**
	 * Add openFileInfo in initialState.
	 * @param string $dir - the ?dir= URL param
	 * @param string $fileid - the fileid URL param
	 * @return void
	 */
	private function provideInitialState(string $dir, ?string $fileid): void {
		if ($fileid === null) {
			return;
		}

		$user = $this->userSession->getUser();

		if ($user === null) {
			return;
		}

		$uid = $user->getUID();
		$userFolder = $this->rootFolder->getUserFolder($uid);
		$nodes = $userFolder->getById((int) $fileid);
		$node = array_shift($nodes);

		if ($node === null) {
			return;
		}

		// properly format full path and make sure
		// we're relative to the user home folder
		$isRoot = $node === $userFolder;
		$path = $userFolder->getRelativePath($node->getPath());
		$directory = $userFolder->getRelativePath($node->getParent()->getPath());

		// Prevent opening a file from another folder.
		if ($dir !== $directory) {
			return;
		}

		$this->initialState->provideInitialState(
			'fileInfo', [
				'id' => $node->getId(),
				'name' => $isRoot ? '' : $node->getName(),
				'path' => $path,
				'directory' => $directory,
				'mime' => $node->getMimetype(),
				'type' => $node->getType(),
				'permissions' => $node->getPermissions(),
			]
		);
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
		$nodes = $baseFolder->getById($fileId);
		$params = [];

		if (empty($nodes) && $this->appManager->isEnabledForUser('files_trashbin')) {
			/** @var Folder */
			$baseFolder = $this->rootFolder->get($uid . '/files_trashbin/files/');
			$nodes = $baseFolder->getById($fileId);
			$params['view'] = 'trashbin';

			if (!empty($nodes)) {
				$node = current($nodes);
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
	 * @return RedirectResponse redirect response or not found response
	 * @throws NotFoundException
	 */
	private function redirectToFile(int $fileId) {
		$uid = $this->userSession->getUser()->getUID();
		$baseFolder = $this->rootFolder->getUserFolder($uid);
		$nodes = $baseFolder->getById($fileId);
		$params = ['view' => 'files'];

		try {
			$this->redirectToFileIfInTrashbin($fileId);
		} catch (NotFoundException $e) {
		}

		if (!empty($nodes)) {
			$node = current($nodes);
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
			return new RedirectResponse($this->urlGenerator->linkToRoute('files.view.indexViewFileid', $params));
		}

		throw new NotFoundException();
	}
}
