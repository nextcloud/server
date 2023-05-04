<?php

declare(strict_types=1);
/**
 * @copyright Copyright (c) 2023 Louis Chemineau <louis@chmn.me>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\Files_Sharing;

use OCA\FederatedFileSharing\FederatedShareProvider;
use OCA\Files_Sharing\AppInfo\Application;
use OCA\Files_Sharing\Event\BeforeTemplateRenderedEvent;
use OCA\Viewer\Event\LoadViewer;
use OCP\Accounts\IAccountManager;
use OCP\AppFramework\Http\ContentSecurityPolicy;
use OCP\AppFramework\Http\Template\ExternalShareMenuAction;
use OCP\AppFramework\Http\Template\LinkMenuAction;
use OCP\AppFramework\Http\Template\PublicTemplateResponse;
use OCP\AppFramework\Http\Template\SimpleMenuAction;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\Constants;
use OCP\Defaults;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Files\FileInfo;
use OCP\Files\Folder;
use OCP\Files\NotFoundException;
use OCP\IConfig;
use OCP\IL10N;
use OCP\IPreview;
use OCP\IRequest;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\IUserManager;
use OCP\Share\IShare;
use OCP\Share\IPublicShareTemplateProvider;
use OCP\Template;
use OCP\Util;

class DefaultPublicShareTemplateProvider implements IPublicShareTemplateProvider {
	private IUserManager $userManager;
	private IAccountManager $accountManager;
	private IPreview $previewManager;
	protected FederatedShareProvider $federatedShareProvider;
	private IURLGenerator $urlGenerator;
	private IEventDispatcher $eventDispatcher;
	private IL10N $l10n;
	private Defaults $defaults;
	private IConfig $config;
	private IRequest $request;

	public function __construct(
		IUserManager $userManager,
		IAccountManager $accountManager,
		IPreview $previewManager,
		FederatedShareProvider $federatedShareProvider,
		IUrlGenerator $urlGenerator,
		IEventDispatcher $eventDispatcher,
		IL10N $l10n,
		Defaults $defaults,
		IConfig $config,
		IRequest $request
	) {
		$this->userManager = $userManager;
		$this->accountManager = $accountManager;
		$this->previewManager = $previewManager;
		$this->federatedShareProvider = $federatedShareProvider;
		$this->urlGenerator = $urlGenerator;
		$this->eventDispatcher = $eventDispatcher;
		$this->l10n = $l10n;
		$this->defaults = $defaults;
		$this->config = $config;
		$this->request = $request;
	}

	public function shouldRespond(IShare $share): bool {
		return true;
	}

	public function renderPage(IShare $share, string $token, string $path): TemplateResponse {
		$shareNode = $share->getNode();

		$shareTmpl = [];
		$shareTmpl['owner'] = '';
		$shareTmpl['shareOwner'] = '';

		$owner = $this->userManager->get($share->getShareOwner());
		if ($owner instanceof IUser) {
			$ownerAccount = $this->accountManager->getAccount($owner);

			$ownerName = $ownerAccount->getProperty(IAccountManager::PROPERTY_DISPLAYNAME);
			if ($ownerName->getScope() === IAccountManager::SCOPE_PUBLISHED) {
				$shareTmpl['owner'] = $owner->getUID();
				$shareTmpl['shareOwner'] = $owner->getDisplayName();
			}
		}

		$shareTmpl['filename'] = $shareNode->getName();
		$shareTmpl['directory_path'] = $share->getTarget();
		$shareTmpl['note'] = $share->getNote();
		$shareTmpl['mimetype'] = $shareNode->getMimetype();
		$shareTmpl['previewSupported'] = $this->previewManager->isMimeSupported($shareNode->getMimetype());
		$shareTmpl['dirToken'] = $token;
		$shareTmpl['sharingToken'] = $token;
		$shareTmpl['server2serversharing'] = $this->federatedShareProvider->isOutgoingServer2serverShareEnabled();
		$shareTmpl['protected'] = $share->getPassword() !== null ? 'true' : 'false';
		$shareTmpl['dir'] = '';
		$shareTmpl['nonHumanFileSize'] = $shareNode->getSize();
		$shareTmpl['fileSize'] = Util::humanFileSize($shareNode->getSize());
		$shareTmpl['hideDownload'] = $share->getHideDownload();

		$hideFileList = false;

		if ($shareNode instanceof Folder) {
			$shareIsFolder = true;

			$folderNode = $shareNode->get($path);
			$shareTmpl['dir'] = $shareNode->getRelativePath($folderNode->getPath());

			/*
			 * The OC_Util methods require a view. This just uses the node API
			 */
			$freeSpace = $share->getNode()->getStorage()->free_space($share->getNode()->getInternalPath());
			if ($freeSpace < FileInfo::SPACE_UNLIMITED) {
				$freeSpace = (int)max($freeSpace, 0);
			} else {
				$freeSpace = (INF > 0) ? INF: PHP_INT_MAX; // work around https://bugs.php.net/bug.php?id=69188
			}

			$hideFileList = !($share->getPermissions() & Constants::PERMISSION_READ);
			$maxUploadFilesize = $freeSpace;

			$folder = new Template('files', 'list', '');

			$folder->assign('dir', $shareNode->getRelativePath($folderNode->getPath()));
			$folder->assign('dirToken', $token);
			$folder->assign('permissions', Constants::PERMISSION_READ);
			$folder->assign('isPublic', true);
			$folder->assign('hideFileList', $hideFileList);
			$folder->assign('publicUploadEnabled', 'no');
			// default to list view
			$folder->assign('showgridview', false);
			$folder->assign('uploadMaxFilesize', $maxUploadFilesize);
			$folder->assign('uploadMaxHumanFilesize', Util::humanFileSize($maxUploadFilesize));
			$folder->assign('freeSpace', $freeSpace);
			$folder->assign('usedSpacePercent', 0);
			$folder->assign('trash', false);
			$shareTmpl['folder'] = $folder->fetchPage();
		} else {
			$shareIsFolder = false;
		}

		// default to list view
		$shareTmpl['showgridview'] = false;

		$shareTmpl['hideFileList'] = $hideFileList;
		$shareTmpl['downloadURL'] = $this->urlGenerator->linkToRouteAbsolute('files_sharing.sharecontroller.downloadShare', [
			'token' => $token,
			'filename' => $shareIsFolder ? null : $shareNode->getName()
		]);
		$shareTmpl['shareUrl'] = $this->urlGenerator->linkToRouteAbsolute('files_sharing.sharecontroller.showShare', ['token' => $token]);
		$shareTmpl['maxSizeAnimateGif'] = $this->config->getSystemValue('max_filesize_animated_gifs_public_sharing', 10);
		$shareTmpl['previewEnabled'] = $this->config->getSystemValue('enable_previews', true);
		$shareTmpl['previewMaxX'] = $this->config->getSystemValue('preview_max_x', 1024);
		$shareTmpl['previewMaxY'] = $this->config->getSystemValue('preview_max_y', 1024);
		$shareTmpl['disclaimer'] = $this->config->getAppValue('core', 'shareapi_public_link_disclaimertext', '');
		$shareTmpl['previewURL'] = $shareTmpl['downloadURL'];

		if ($shareTmpl['previewSupported']) {
			$shareTmpl['previewImage'] = $this->urlGenerator->linkToRouteAbsolute('files_sharing.PublicPreview.getPreview',
				['x' => 200, 'y' => 200, 'file' => $shareTmpl['directory_path'], 'token' => $shareTmpl['dirToken']]);
			$ogPreview = $shareTmpl['previewImage'];

			// We just have direct previews for image files
			if ($shareNode->getMimePart() === 'image') {
				$shareTmpl['previewURL'] = $this->urlGenerator->linkToRouteAbsolute('files_sharing.publicpreview.directLink', ['token' => $token]);

				$ogPreview = $shareTmpl['previewURL'];

				//Whatapp is kind of picky about their size requirements
				if ($this->request->isUserAgent(['/^WhatsApp/'])) {
					$ogPreview = $this->urlGenerator->linkToRouteAbsolute('files_sharing.PublicPreview.getPreview', [
						'token' => $token,
						'x' => 256,
						'y' => 256,
						'a' => true,
					]);
				}
			}
		} else {
			$shareTmpl['previewImage'] = $this->urlGenerator->getAbsoluteURL($this->urlGenerator->imagePath('core', 'favicon-fb.png'));
			$ogPreview = $shareTmpl['previewImage'];
		}

		// Load files we need
		Util::addScript('files', 'semaphore');
		Util::addScript('files', 'file-upload');
		Util::addStyle('files_sharing', 'publicView');
		Util::addScript('files_sharing', 'public');
		Util::addScript('files_sharing', 'templates');
		Util::addScript('files', 'fileactions');
		Util::addScript('files', 'fileactionsmenu');
		Util::addScript('files', 'jquery.fileupload');
		Util::addScript('files_sharing', 'files_drop');

		if (isset($shareTmpl['folder'])) {
			// JS required for folders
			Util::addStyle('files', 'merged');
			Util::addScript('files', 'filesummary');
			Util::addScript('files', 'templates');
			Util::addScript('files', 'breadcrumb');
			Util::addScript('files', 'fileinfomodel');
			Util::addScript('files', 'newfilemenu');
			Util::addScript('files', 'files');
			Util::addScript('files', 'filemultiselectmenu');
			Util::addScript('files', 'filelist');
			Util::addScript('files', 'keyboardshortcuts');
			Util::addScript('files', 'operationprogressbar');
		}

		// Load Viewer scripts
		if (class_exists(LoadViewer::class)) {
			$this->eventDispatcher->dispatchTyped(new LoadViewer());
		}
		// OpenGraph Support: http://ogp.me/
		Util::addHeader('meta', ['property' => "og:title", 'content' => $shareTmpl['filename']]);
		Util::addHeader('meta', ['property' => "og:description", 'content' => $this->defaults->getName() . ($this->defaults->getSlogan() !== '' ? ' - ' . $this->defaults->getSlogan() : '')]);
		Util::addHeader('meta', ['property' => "og:site_name", 'content' => $this->defaults->getName()]);
		Util::addHeader('meta', ['property' => "og:url", 'content' => $shareTmpl['shareUrl']]);
		Util::addHeader('meta', ['property' => "og:type", 'content' => "object"]);
		Util::addHeader('meta', ['property' => "og:image", 'content' => $ogPreview]);

		$this->eventDispatcher->dispatchTyped(new BeforeTemplateRenderedEvent($share));

		$csp = new ContentSecurityPolicy();
		$csp->addAllowedFrameDomain('\'self\'');

		$response = new PublicTemplateResponse(Application::APP_ID, 'public', $shareTmpl);
		$response->setHeaderTitle($shareTmpl['filename']);
		if ($shareTmpl['shareOwner'] !== '') {
			$response->setHeaderDetails($this->l10n->t('shared by %s', [$shareTmpl['shareOwner']]));
		}

		$isNoneFileDropFolder = $shareIsFolder === false || $share->getPermissions() !== Constants::PERMISSION_CREATE;

		if ($isNoneFileDropFolder && !$share->getHideDownload()) {
			Util::addScript('files_sharing', 'public_note');

			$downloadWhite = new SimpleMenuAction('download', $this->l10n->t('Download'), 'icon-download-white', $shareTmpl['downloadURL'], 0);
			$downloadAllWhite = new SimpleMenuAction('download', $this->l10n->t('Download all files'), 'icon-download-white', $shareTmpl['downloadURL'], 0);
			$download = new SimpleMenuAction('download', $this->l10n->t('Download'), 'icon-download', $shareTmpl['downloadURL'], 10, $shareTmpl['fileSize']);
			$downloadAll = new SimpleMenuAction('download', $this->l10n->t('Download all files'), 'icon-download', $shareTmpl['downloadURL'], 10, $shareTmpl['fileSize']);
			$directLink = new LinkMenuAction($this->l10n->t('Direct link'), 'icon-public', $shareTmpl['previewURL']);
			// TRANSLATORS The placeholder refers to the software product name as in 'Add to your Nextcloud'
			$externalShare = new ExternalShareMenuAction($this->l10n->t('Add to your %s', [$this->defaults->getProductName()]), 'icon-external', $shareTmpl['owner'], $shareTmpl['shareOwner'], $shareTmpl['filename']);

			$responseComposer = [];

			if ($shareIsFolder) {
				$responseComposer[] = $downloadAllWhite;
				$responseComposer[] = $downloadAll;
			} else {
				$responseComposer[] = $downloadWhite;
				$responseComposer[] = $download;
			}
			$responseComposer[] = $directLink;
			if ($this->federatedShareProvider->isOutgoingServer2serverShareEnabled()) {
				$responseComposer[] = $externalShare;
			}

			$response->setHeaderActions($responseComposer);
		}

		$response->setContentSecurityPolicy($csp);
		return $response;
	}
}
