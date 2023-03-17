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
use OCA\Viewer\Event\LoadViewer;
use OCP\App\IAppManager;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\ContentSecurityPolicy;
use OCP\AppFramework\Http\RedirectResponse;
use OCP\AppFramework\Http\Response;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Services\IInitialState;
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
use OCP\Share\IManager;

/**
 * Class ViewController
 *
 * @package OCA\Files\Controller
 */
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
		UserConfig $userConfig
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
	 * @throws NotFoundException
	 */
	public function showFile(string $fileid = null, int $openfile = 1): Response {
		// This is the entry point from the `/f/{fileid}` URL which is hardcoded in the server.
		try {
			return $this->redirectToFile($fileid, $openfile !== 0);
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
	 * @param string $openfile - the openfile URL parameter if it was present in the initial request
	 * @return TemplateResponse|RedirectResponse
	 * @throws NotFoundException
	 */
	public function index($dir = '', $view = '', $fileid = null, $fileNotFound = false, $openfile = null) {

		if ($fileid !== null && $dir === '') {
			try {
				return $this->redirectToFile($fileid);
			} catch (NotFoundException $e) {
				return new RedirectResponse($this->urlGenerator->linkToRoute('files.view.index', ['fileNotFound' => true]));
			}
		}

		$nav = new \OCP\Template('files', 'appnavigation', '');

		// Load the files we need
		\OCP\Util::addStyle('files', 'merged');
		\OCP\Util::addScript('files', 'merged-index', 'files');
		\OCP\Util::addScript('files', 'main');

		$userId = $this->userSession->getUser()->getUID();

		// Get all the user favorites to create a submenu
		try {
			$favElements = $this->activityHelper->getFavoriteFilePaths($userId);
		} catch (\RuntimeException $e) {
			$favElements['folders'] = [];
		}

		$collapseClasses = '';
		if (count($favElements['folders']) > 0) {
			$collapseClasses = 'collapsible';
		}

		$favoritesSublistArray = [];

		$navBarPositionPosition = 6;
		foreach ($favElements['folders'] as $favElement) {
			$element = [
				'id' => str_replace('/', '-', $favElement),
				'dir' => $favElement,
				'order' => $navBarPositionPosition,
				'name' => basename($favElement),
				'icon' => 'folder',
				'params' => [
					'view' => 'files',
					'dir' => $favElement,
				],
			];

			array_push($favoritesSublistArray, $element);
			$navBarPositionPosition++;
		}

		$navItems = \OCA\Files\App::getNavigationManager()->getAll();

		// add the favorites entry in menu
		$navItems['favorites']['sublist'] = $favoritesSublistArray;
		$navItems['favorites']['classes'] = $collapseClasses;

		// parse every menu and add the expanded user value
		foreach ($navItems as $key => $item) {
			$navItems[$key]['expanded'] = $this->config->getUserValue($userId, 'files', 'show_' . $item['id'], '0') === '1';
		}

		$nav->assign('navigationItems', $navItems);

		$contentItems = [];

		try {
			// If view is files, we use the directory, otherwise we use the root storage
			$storageInfo =  $this->getStorageInfo(($view === 'files' && $dir) ? $dir : '/');
		} catch(\Exception $e) {
			$storageInfo = $this->getStorageInfo();
		}

		$this->initialState->provideInitialState('storageStats', $storageInfo);
		$this->initialState->provideInitialState('navigation', $navItems);
		$this->initialState->provideInitialState('config', $this->userConfig->getConfigs());

		// render the container content for every navigation item
		foreach ($navItems as $item) {
			$content = '';
			if (isset($item['script'])) {
				$content = $this->renderScript($item['appname'], $item['script']);
			}
			// parse submenus
			if (isset($item['sublist'])) {
				foreach ($item['sublist'] as $subitem) {
					$subcontent = '';
					if (isset($subitem['script'])) {
						$subcontent = $this->renderScript($subitem['appname'], $subitem['script']);
					}
					$contentItems[$subitem['id']] = [
						'id' => $subitem['id'],
						'content' => $subcontent
					];
				}
			}
			$contentItems[$item['id']] = [
				'id' => $item['id'],
				'content' => $content
			];
		}

		$this->eventDispatcher->dispatchTyped(new ResourcesLoadAdditionalScriptsEvent());
		$event = new LoadAdditionalScriptsEvent();
		$this->eventDispatcher->dispatchTyped($event);
		$this->eventDispatcher->dispatchTyped(new LoadSidebar());
		// Load Viewer scripts
		if (class_exists(LoadViewer::class)) {
			$this->eventDispatcher->dispatchTyped(new LoadViewer());
		}

		$this->initialState->provideInitialState('templates_path', $this->templateManager->hasTemplateDirectory() ? $this->templateManager->getTemplatePath() : false);
		$this->initialState->provideInitialState('templates', $this->templateManager->listCreators());

		$params = [];
		$params['usedSpacePercent'] = (int) $storageInfo['relative'];
		$params['owner'] = $storageInfo['owner'] ?? '';
		$params['ownerDisplayName'] = $storageInfo['ownerDisplayName'] ?? '';
		$params['isPublic'] = false;
		$params['allowShareWithLink'] = $this->shareManager->shareApiAllowLinks() ? 'yes' : 'no';
		$params['defaultFileSorting'] = $this->config->getUserValue($userId, 'files', 'file_sorting', 'name');
		$params['defaultFileSortingDirection'] = $this->config->getUserValue($userId, 'files', 'file_sorting_direction', 'asc');
		$params['showgridview'] = $this->config->getUserValue($userId, 'files', 'show_grid', false);
		$showHidden = (bool) $this->config->getUserValue($userId, 'files', 'show_hidden', false);
		$params['showHiddenFiles'] = $showHidden ? 1 : 0;
		$cropImagePreviews = (bool) $this->config->getUserValue($userId, 'files', 'crop_image_previews', true);
		$params['cropImagePreviews'] = $cropImagePreviews ? 1 : 0;
		$params['fileNotFound'] = $fileNotFound ? 1 : 0;
		$params['appNavigation'] = $nav;
		$params['appContents'] = $contentItems;
		$params['hiddenFields'] = $event->getHiddenFields();

		$response = new TemplateResponse(
			Application::APP_ID,
			'index',
			$params
		);
		$policy = new ContentSecurityPolicy();
		$policy->addAllowedFrameDomain('\'self\'');
		$response->setContentSecurityPolicy($policy);

		$this->provideInitialState($dir, $openfile);

		return $response;
	}

	/**
	 * Add openFileInfo in initialState if $openfile is set.
	 * @param string $dir - the ?dir= URL param
	 * @param string $openfile - the ?openfile= URL param
	 * @return void
	 */
	private function provideInitialState(string $dir, ?string $openfile): void {
		if ($openfile === null) {
			return;
		}

		$user = $this->userSession->getUser();

		if ($user === null) {
			return;
		}

		$uid = $user->getUID();
		$userFolder = $this->rootFolder->getUserFolder($uid);
		$nodes = $userFolder->getById((int) $openfile);
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
			'openFileInfo', [
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
	 * Redirects to the file list and highlight the given file id
	 *
	 * @param string $fileId file id to show
	 * @param bool $setOpenfile - whether or not to set the openfile URL parameter
	 * @return RedirectResponse redirect response or not found response
	 * @throws \OCP\Files\NotFoundException
	 */
	private function redirectToFile($fileId, bool $setOpenfile = false) {
		$uid = $this->userSession->getUser()->getUID();
		$baseFolder = $this->rootFolder->getUserFolder($uid);
		$files = $baseFolder->getById($fileId);
		$params = [];

		if (empty($files) && $this->appManager->isEnabledForUser('files_trashbin')) {
			$baseFolder = $this->rootFolder->get($uid . '/files_trashbin/files/');
			$files = $baseFolder->getById($fileId);
			$params['view'] = 'trashbin';
		}

		if (!empty($files)) {
			$file = current($files);
			if ($file instanceof Folder) {
				// set the full path to enter the folder
				$params['dir'] = $baseFolder->getRelativePath($file->getPath());
			} else {
				// set parent path as dir
				$params['dir'] = $baseFolder->getRelativePath($file->getParent()->getPath());
				// and scroll to the entry
				$params['scrollto'] = $file->getName();

				if ($setOpenfile) {
					// forward the openfile URL parameter.
					$params['openfile'] = $fileId;
				}
			}

			return new RedirectResponse($this->urlGenerator->linkToRoute('files.view.index', $params));
		}
		throw new \OCP\Files\NotFoundException();
	}
}
