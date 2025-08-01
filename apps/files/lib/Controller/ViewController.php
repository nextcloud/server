<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Files\Controller;

use OC\Files\FilenameValidator;
use OC\Files\Filesystem;
use OCA\Files\AppInfo\Application;
use OCA\Files\Event\LoadAdditionalScriptsEvent;
use OCA\Files\Event\LoadSearchPlugins;
use OCA\Files\Event\LoadSidebar;
use OCA\Files\Service\UserConfig;
use OCA\Files\Service\ViewConfig;
use OCA\Viewer\Event\LoadViewer;
use OCP\App\IAppManager;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\Attribute\OpenAPI;
use OCP\AppFramework\Http\ContentSecurityPolicy;
use OCP\AppFramework\Http\RedirectResponse;
use OCP\AppFramework\Http\Response;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Services\IInitialState;
use OCP\Authentication\TwoFactorAuth\IRegistry;
use OCP\Collaboration\Resources\LoadAdditionalScriptsEvent as ResourcesLoadAdditionalScriptsEvent;
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
use OCP\Util;

/**
 * @package OCA\Files\Controller
 */
#[OpenAPI(scope: OpenAPI::SCOPE_IGNORE)]
class ViewController extends Controller {

	public function __construct(
		string $appName,
		IRequest $request,
		private IURLGenerator $urlGenerator,
		private IL10N $l10n,
		private IConfig $config,
		private IEventDispatcher $eventDispatcher,
		private IUserSession $userSession,
		private IAppManager $appManager,
		private IRootFolder $rootFolder,
		private IInitialState $initialState,
		private ITemplateManager $templateManager,
		private UserConfig $userConfig,
		private ViewConfig $viewConfig,
		private FilenameValidator $filenameValidator,
		private IRegistry $twoFactorRegistry,
	) {
		parent::__construct($appName, $request);
	}

	/**
	 * FIXME: Replace with non static code
	 *
	 * @return array
	 * @throws NotFoundException
	 */
	protected function getStorageInfo(string $dir = '/') {
		$rootInfo = Filesystem::getFileInfo('/', false);

		return \OC_Helper::getStorageInfo($dir, $rootInfo ?: null);
	}

	/**
	 * @param string $fileid
	 * @return TemplateResponse|RedirectResponse
	 */
	#[NoAdminRequired]
	#[NoCSRFRequired]
	public function showFile(?string $fileid = null, ?string $opendetails = null, ?string $openfile = null): Response {
		if (!$fileid) {
			return new RedirectResponse($this->urlGenerator->linkToRoute('files.view.index'));
		}

		// This is the entry point from the `/f/{fileid}` URL which is hardcoded in the server.
		try {
			return $this->redirectToFile((int)$fileid, $opendetails, $openfile);
		} catch (NotFoundException $e) {
			// Keep the fileid even if not found, it will be used
			// to detect the file could not be found and warn the user
			return new RedirectResponse($this->urlGenerator->linkToRoute('files.view.indexViewFileid', ['fileid' => $fileid, 'view' => 'files']));
		}
	}


	/**
	 * @param string $dir
	 * @param string $view
	 * @param string $fileid
	 * @return TemplateResponse|RedirectResponse
	 */
	#[NoAdminRequired]
	#[NoCSRFRequired]
	public function indexView($dir = '', $view = '', $fileid = null) {
		return $this->index($dir, $view, $fileid);
	}

	/**
	 * @param string $dir
	 * @param string $view
	 * @param string $fileid
	 * @return TemplateResponse|RedirectResponse
	 */
	#[NoAdminRequired]
	#[NoCSRFRequired]
	public function indexViewFileid($dir = '', $view = '', $fileid = null) {
		return $this->index($dir, $view, $fileid);
	}

	/**
	 * @param string $dir
	 * @param string $view
	 * @param string $fileid
	 * @return TemplateResponse|RedirectResponse
	 */
	#[NoAdminRequired]
	#[NoCSRFRequired]
	public function index($dir = '', $view = '', $fileid = null) {
		if ($fileid !== null && $view !== 'trashbin') {
			try {
				return $this->redirectToFileIfInTrashbin((int)$fileid);
			} catch (NotFoundException $e) {
			}
		}

		// Load the files we need
		Util::addInitScript('files', 'init');
		Util::addScript('files', 'main');

		$user = $this->userSession->getUser();
		$userId = $user->getUID();

		// If the file doesn't exists in the folder and
		// exists in only one occurrence, redirect to that file
		// in the correct folder
		if ($fileid && $dir !== '') {
			$baseFolder = $this->rootFolder->getUserFolder($userId);
			$nodes = $baseFolder->getById((int)$fileid);
			if (!empty($nodes)) {
				$nodePath = $baseFolder->getRelativePath($nodes[0]->getPath());
				$relativePath = $nodePath ? dirname($nodePath) : '';
				// If the requested path does not contain the file id
				// or if the requested path is not the file id itself
				if (count($nodes) === 1 && $relativePath !== $dir && $nodePath !== $dir) {
					return $this->redirectToFile((int)$fileid);
				}
			}
		}

		try {
			// If view is files, we use the directory, otherwise we use the root storage
			$storageInfo = $this->getStorageInfo(($view === 'files' && $dir) ? $dir : '/');
		} catch (\Exception $e) {
			$storageInfo = $this->getStorageInfo();
		}

		$this->initialState->provideInitialState('storageStats', $storageInfo);
		$this->initialState->provideInitialState('config', $this->userConfig->getConfigs());
		$this->initialState->provideInitialState('viewConfigs', $this->viewConfig->getConfigs());

		// File sorting user config
		$filesSortingConfig = json_decode($this->config->getUserValue($userId, 'files', 'files_sorting_configs', '{}'), true);
		$this->initialState->provideInitialState('filesSortingConfig', $filesSortingConfig);

		// Forbidden file characters (deprecated use capabilities)
		// TODO: Remove with next release of `@nextcloud/files`
		$forbiddenCharacters = $this->filenameValidator->getForbiddenCharacters();
		$this->initialState->provideInitialState('forbiddenCharacters', $forbiddenCharacters);

		$event = new LoadAdditionalScriptsEvent();
		$this->eventDispatcher->dispatchTyped($event);
		$this->eventDispatcher->dispatchTyped(new ResourcesLoadAdditionalScriptsEvent());
		$this->eventDispatcher->dispatchTyped(new LoadSidebar());
		$this->eventDispatcher->dispatchTyped(new LoadSearchPlugins());
		// Load Viewer scripts
		if (class_exists(LoadViewer::class)) {
			$this->eventDispatcher->dispatchTyped(new LoadViewer());
		}

		$this->initialState->provideInitialState('templates_enabled', ($this->config->getSystemValueString('skeletondirectory', \OC::$SERVERROOT . '/core/skeleton') !== '') || ($this->config->getSystemValueString('templatedirectory', \OC::$SERVERROOT . '/core/skeleton/Templates') !== ''));
		$this->initialState->provideInitialState('templates_path', $this->templateManager->hasTemplateDirectory() ? $this->templateManager->getTemplatePath() : false);
		$this->initialState->provideInitialState('templates', $this->templateManager->listCreators());

		$isTwoFactorEnabled = false;
		foreach ($this->twoFactorRegistry->getProviderStates($user) as $providerId => $providerState) {
			if ($providerId !== 'backup_codes' && $providerState === true) {
				$isTwoFactorEnabled = true;
			}
		}

		$this->initialState->provideInitialState('isTwoFactorEnabled', $isTwoFactorEnabled);

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
	 * @param string|null $openDetails open details parameter
	 * @param string|null $openFile open file parameter
	 * @return RedirectResponse redirect response or not found response
	 * @throws NotFoundException
	 */
	private function redirectToFile(int $fileId, ?string $openDetails = null, ?string $openFile = null): RedirectResponse {
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

			// Forward open parameters if any.
			// - openfile is true by default
			// - opendetails is undefined by default
			// - both will be evaluated as truthy
			if ($openDetails !== null) {
				$params['opendetails'] = $openDetails !== 'false' ? 'true' : 'false';
			}

			if ($openFile !== null) {
				$params['openfile'] = $openFile !== 'false' ? 'true' : 'false';
			}

			return new RedirectResponse($this->urlGenerator->linkToRoute('files.view.indexViewFileid', $params));
		}

		throw new NotFoundException();
	}
}
