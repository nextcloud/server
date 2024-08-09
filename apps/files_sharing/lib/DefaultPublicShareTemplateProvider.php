<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files_Sharing;

use OCA\FederatedFileSharing\FederatedShareProvider;
use OCA\Files_Sharing\Event\BeforeTemplateRenderedEvent;
use OCA\Viewer\Event\LoadViewer;
use OCP\Accounts\IAccountManager;
use OCP\AppFramework\Http\ContentSecurityPolicy;
use OCP\AppFramework\Http\Template\ExternalShareMenuAction;
use OCP\AppFramework\Http\Template\PublicTemplateResponse;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Services\IInitialState;
use OCP\Defaults;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Files\File;
use OCP\IConfig;
use OCP\IL10N;
use OCP\IPreview;
use OCP\IRequest;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\IUserManager;
use OCP\Share\IPublicShareTemplateProvider;
use OCP\Share\IShare;
use OCP\Util;

class DefaultPublicShareTemplateProvider implements IPublicShareTemplateProvider {

	public function __construct(
		private IUserManager $userManager,
		private IAccountManager $accountManager,
		private IPreview $previewManager,
		protected FederatedShareProvider $federatedShareProvider,
		private IUrlGenerator $urlGenerator,
		private IEventDispatcher $eventDispatcher,
		private IL10N $l10n,
		private Defaults $defaults,
		private IConfig $config,
		private IRequest $request,
		private IInitialState $initialState,
	) {
	}

	public function shouldRespond(IShare $share): bool {
		return true;
	}

	public function renderPage(IShare $share, string $token, string $path): TemplateResponse {
		$shareNode = $share->getNode();
		$ownerName = '';
		$ownerId = '';

		// Only make the share owner public if they allowed to show their name
		$owner = $this->userManager->get($share->getShareOwner());
		if ($owner instanceof IUser) {
			$ownerAccount = $this->accountManager->getAccount($owner);

			$ownerNameProperty = $ownerAccount->getProperty(IAccountManager::PROPERTY_DISPLAYNAME);
			if ($ownerNameProperty->getScope() === IAccountManager::SCOPE_PUBLISHED) {
				$ownerName = $owner->getDisplayName();
				$ownerId = $owner->getUID();
			}
		}

		$view = 'public-share';
		if ($shareNode instanceof File) {
			$view = 'public-file-share';
		} else if (($share->getPermissions() & \OCP\Constants::PERMISSION_CREATE) && !($share->getPermissions() & \OCP\Constants::PERMISSION_READ)) {
			// share is a folder with create but no read permissions -> file drop only
			$view = 'public-file-drop';
			// Only needed for file drops
			$this->initialState->provideInitialState('disclaimer', $this->config->getAppValue('core', 'shareapi_public_link_disclaimertext', ''));
		}
		// Set up initial state
		$this->initialState->provideInitialState('isPublic', true);
		$this->initialState->provideInitialState('sharingToken', $token);
		$this->initialState->provideInitialState('sharePermissions', $share->getPermissions());
		$this->initialState->provideInitialState('filename', $shareNode->getName());
		$this->initialState->provideInitialState('view', $view);

		// Load scripts and styles for UI
		\OCP\Util::addInitScript('files', 'init');
		\OCP\Util::addInitScript('files_sharing', 'init');
		\OCP\Util::addInitScript('files_sharing', 'init-public');
		\OCP\Util::addScript('files', 'main');
		\OCP\Util::addStyle('files', 'merged');

		// Load Viewer scripts
		if (class_exists(LoadViewer::class)) {
			$this->eventDispatcher->dispatchTyped(new LoadViewer());
		}

		// Allow external apps to register their scripts
		$this->eventDispatcher->dispatchTyped(new BeforeTemplateRenderedEvent($share));

		// OpenGraph Support: http://ogp.me/
		$this->addOpenGraphHeaders($share);

		// CSP to allow office
		$csp = new ContentSecurityPolicy();
		$csp->addAllowedFrameDomain('\'self\'');

		$response = new PublicTemplateResponse(
			'files',
			'index',
		);
		$response->setContentSecurityPolicy($csp);
		// If the share has a label, use it as the title
		if ($share->getLabel() !== '') {
			$response->setHeaderTitle($share->getLabel());
		} else {
			$response->setHeaderTitle($shareNode->getName());
		}
		if ($ownerName !== '') {
			$response->setHeaderDetails($this->l10n->t('shared by %s', [$ownerName]));
		}
		if ($this->federatedShareProvider->isOutgoingServer2serverShareEnabled() && $view !== 'public-file-drop' && !$share->getHideDownload()) {
			$response->setHeaderActions([
				new ExternalShareMenuAction(
					// TRANSLATORS The placeholder refers to the software product name as in 'Add to your Nextcloud'
					$this->l10n->t('Add to your %s', [$this->defaults->getProductName()]),
					'icon-external',
					$ownerId,
					$ownerName,
					$shareNode->getName(),
				),
			]);
		}

		return $response;
	}

	/**
	 * Add OpenGraph headers to response for preview
	 * @param IShare $share The share for which to add the headers
	 */
	protected function addOpenGraphHeaders(IShare $share): void {
		$shareNode = $share->getNode();
		$token = $share->getToken();
		$shareUrl = $this->urlGenerator->linkToRouteAbsolute('files_sharing.sharecontroller.showShare', ['token' => $token]);

		// Handle preview generation for OpenGraph
		if ($this->previewManager->isMimeSupported($shareNode->getMimetype())) {
			// For images we can use direct links
			if ($shareNode->getMimePart() === 'image') {
				$ogPreview = $this->urlGenerator->linkToRouteAbsolute('files_sharing.publicpreview.directLink', ['token' => $token]);
				//Whatapp is kind of picky about their size requirements
				if ($this->request->isUserAgent(['/^WhatsApp/'])) {
					$ogPreview = $this->urlGenerator->linkToRouteAbsolute('files_sharing.PublicPreview.getPreview', [
						'token' => $token,
						'x' => 256,
						'y' => 256,
						'a' => true,
					]);
				}
			} else {
				// For normal files use preview API
				$ogPreview = $this->urlGenerator->linkToRouteAbsolute(
					'files_sharing.PublicPreview.getPreview',
					[
						'x' => 256,
						'y' => 256,
						'file' => $share->getTarget(),
						'token' => $token,
					],
				);
			}
		} else {
			// No preview supported, so we just add the favicon
			$ogPreview = $this->urlGenerator->getAbsoluteURL($this->urlGenerator->imagePath('core', 'favicon-fb.png'));
		}

		Util::addHeader('meta', ['property' => "og:title", 'content' => $shareNode->getName()]);
		Util::addHeader('meta', ['property' => "og:description", 'content' => $this->defaults->getName() . ($this->defaults->getSlogan() !== '' ? ' - ' . $this->defaults->getSlogan() : '')]);
		Util::addHeader('meta', ['property' => "og:site_name", 'content' => $this->defaults->getName()]);
		Util::addHeader('meta', ['property' => "og:url", 'content' => $shareUrl]);
		Util::addHeader('meta', ['property' => "og:type", 'content' => "object"]);
		Util::addHeader('meta', ['property' => "og:image", 'content' => $ogPreview]);
	}
}
