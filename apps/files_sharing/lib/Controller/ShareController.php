<?php
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Files_Sharing\Controller;

use OC\Security\CSP\ContentSecurityPolicy;
use OCA\DAV\Connector\Sabre\PublicAuth;
use OCA\FederatedFileSharing\FederatedShareProvider;
use OCA\Files_Sharing\Event\BeforeTemplateRenderedEvent;
use OCA\Files_Sharing\Services\ShareAccessService;
use OCP\Accounts\IAccountManager;
use OCP\AppFramework\AuthPublicShareController;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\Attribute\OpenAPI;
use OCP\AppFramework\Http\Attribute\PublicPage;
use OCP\AppFramework\Http\RedirectResponse;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\Defaults;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Files\IRootFolder;
use OCP\Files\NotFoundException;
use OCP\IConfig;
use OCP\IL10N;
use OCP\IPreview;
use OCP\IRequest;
use OCP\ISession;
use OCP\IURLGenerator;
use OCP\IUserManager;
use OCP\Security\Events\GenerateSecurePasswordEvent;
use OCP\Security\ISecureRandom;
use OCP\Security\PasswordContext;
use OCP\Share;
use OCP\Share\Exceptions\ShareNotFound;
use OCP\Share\IManager as ShareManager;
use OCP\Share\IPublicShareTemplateFactory;
use OCP\Share\IShare;

/**
 * @package OCA\Files_Sharing\Controllers
 */
#[OpenAPI(scope: OpenAPI::SCOPE_IGNORE)]
class ShareController extends AuthPublicShareController {
	protected ?Share\IShare $share = null;

	public const SHARE_ACCESS = 'access';
	public const SHARE_AUTH = 'auth';
	public const SHARE_DOWNLOAD = 'download';

	public function __construct(
		string $appName,
		IRequest $request,
		ISession $session,
		IURLGenerator $urlGenerator,
		protected IConfig $config,
		protected IUserManager $userManager,
		protected \OCP\Activity\IManager $activityManager,
		protected ShareManager $shareManager,
		protected IPreview $previewManager,
		protected IRootFolder $rootFolder,
		protected FederatedShareProvider $federatedShareProvider,
		protected IAccountManager $accountManager,
		protected IEventDispatcher $eventDispatcher,
		protected IL10N $l10n,
		protected ISecureRandom $secureRandom,
		protected Defaults $defaults,
		private IPublicShareTemplateFactory $publicShareTemplateFactory,
		private ShareAccessService $accessService,
	) {
		parent::__construct($appName, $request, $session, $urlGenerator);
	}

	/**
	 * Show the authentication page
	 * The form has to submit to the authenticate method route
	 */
	#[PublicPage]
	#[NoCSRFRequired]
	public function showAuthenticate(): TemplateResponse {
		$templateParameters = ['share' => $this->share];

		$this->eventDispatcher->dispatchTyped(new BeforeTemplateRenderedEvent($this->share, BeforeTemplateRenderedEvent::SCOPE_PUBLIC_SHARE_AUTH));

		$response = new TemplateResponse('core', 'publicshareauth', $templateParameters, 'guest');
		if ($this->share->getSendPasswordByTalk()) {
			$csp = new ContentSecurityPolicy();
			$csp->addAllowedConnectDomain('*');
			$csp->addAllowedMediaDomain('blob:');
			$response->setContentSecurityPolicy($csp);
		}

		return $response;
	}

	/**
	 * The template to show when authentication failed
	 */
	protected function showAuthFailed(): TemplateResponse {
		$templateParameters = ['share' => $this->share, 'wrongpw' => true];

		$this->eventDispatcher->dispatchTyped(new BeforeTemplateRenderedEvent($this->share, BeforeTemplateRenderedEvent::SCOPE_PUBLIC_SHARE_AUTH));

		$response = new TemplateResponse('core', 'publicshareauth', $templateParameters, 'guest');
		if ($this->share->getSendPasswordByTalk()) {
			$csp = new ContentSecurityPolicy();
			$csp->addAllowedConnectDomain('*');
			$csp->addAllowedMediaDomain('blob:');
			$response->setContentSecurityPolicy($csp);
		}

		return $response;
	}

	/**
	 * The template to show after user identification
	 */
	protected function showIdentificationResult(bool $success = false): TemplateResponse {
		$templateParameters = ['share' => $this->share, 'identityOk' => $success];

		$this->eventDispatcher->dispatchTyped(new BeforeTemplateRenderedEvent($this->share, BeforeTemplateRenderedEvent::SCOPE_PUBLIC_SHARE_AUTH));

		$response = new TemplateResponse('core', 'publicshareauth', $templateParameters, 'guest');
		if ($this->share->getSendPasswordByTalk()) {
			$csp = new ContentSecurityPolicy();
			$csp->addAllowedConnectDomain('*');
			$csp->addAllowedMediaDomain('blob:');
			$response->setContentSecurityPolicy($csp);
		}

		return $response;
	}

	/**
	 * Validate the identity token of a public share
	 *
	 * @param ?string $identityToken
	 * @return bool
	 */
	protected function validateIdentity(?string $identityToken = null): bool {
		if ($this->share->getShareType() !== IShare::TYPE_EMAIL) {
			return false;
		}

		if ($identityToken === null || $this->share->getSharedWith() === null) {
			return false;
		}

		return $identityToken === $this->share->getSharedWith();
	}

	/**
	 * Generates a password for the share, respecting any password policy defined
	 */
	protected function generatePassword(): void {
		$event = new GenerateSecurePasswordEvent(PasswordContext::SHARING);
		$this->eventDispatcher->dispatchTyped($event);
		$password = $event->getPassword() ?? $this->secureRandom->generate(20);

		$this->share->setPassword($password);
		$this->shareManager->updateShare($this->share);
	}

	protected function verifyPassword(string $password): bool {
		return $this->shareManager->checkPassword($this->share, $password);
	}

	protected function getPasswordHash(): ?string {
		return $this->share->getPassword();
	}

	public function isValidToken(): bool {
		try {
			$this->share = $this->shareManager->getShareByToken($this->getToken());
		} catch (ShareNotFound $e) {
			return false;
		}

		return true;
	}

	protected function isPasswordProtected(): bool {
		return $this->share->getPassword() !== null;
	}

	protected function authSucceeded() {
		if ($this->share === null) {
			throw new NotFoundException();
		}

		// For share this was always set so it is still used in other apps
		$this->session->set(PublicAuth::DAV_AUTHENTICATED, $this->share->getId());
	}

	/** @inheritDoc */
	protected function authFailed() {
		$this->accessService->accessWrongPassword($this->share);
	}

	/**
	 * Validate the permissions of the share
	 *
	 * @param Share\IShare $share
	 * @return bool
	 */
	private function validateSharePermissions(\OCP\Share\IShare $share) {
		// If the owner is disabled no access to the link is granted
		$owner = $this->userManager->get($share->getShareOwner());
		if ($owner === null || !$owner->isEnabled()) {
			return false;
		}

		// If the initiator of the share is disabled no access is granted
		$initiator = $this->userManager->get($share->getSharedBy());
		if ($initiator === null || !$initiator->isEnabled()) {
			return false;
		}

		return $share->getNode()->isReadable() && $share->getNode()->isShareable();
	}

	/**
	 * @param string $path
	 * @return TemplateResponse
	 * @throws NotFoundException
	 * @throws \Exception
	 */
	#[PublicPage]
	#[NoCSRFRequired]
	public function showShare($path = ''): TemplateResponse {
		\OC_User::setIncognitoMode(true);

		// Check whether share exists
		try {
			$share = $this->shareManager->getShareByToken($this->getToken());
		} catch (ShareNotFound $e) {
			throw new NotFoundException($this->l10n->t('This share does not exist or is no longer available'));
		}

		if (!$this->validateSharePermissions($share)) {
			$this->accessService->shareNotFound($share);
			throw new NotFoundException($this->l10n->t('This share does not exist or is no longer available'));
		}

		$shareNode = $share->getNode();

		try {
			$templateProvider = $this->publicShareTemplateFactory->getProvider($share);
			$response = $templateProvider->renderPage($share, $this->getToken(), $path);
		} catch (NotFoundException $e) {
			$this->accessService->shareNotFound($share);
			throw new NotFoundException($this->l10n->t('This share does not exist or is no longer available'));
		}

		// We can't get the path of a file share
		if (($shareNode instanceof \OCP\Files\File) && $path !== '') {
			$this->accessService->shareNotFound($share);
			throw new NotFoundException($this->l10n->t('This share does not exist or is no longer available'));
		}

		$this->accessService->accessShare($share);
		return $response;
	}

	/**
	 * @NoSameSiteCookieRequired
	 *
	 * @param string $token
	 * @param string|null $files
	 * @param string $path
	 * @return void|\OCP\AppFramework\Http\Response
	 * @throws NotFoundException
	 * @deprecated 31.0.0 Users are encouraged to use the DAV endpoint
	 */
	#[PublicPage]
	#[NoCSRFRequired]
	public function downloadShare($token, $files = null, $path = '') {
		\OC_User::setIncognitoMode(true);

		$share = $this->shareManager->getShareByToken($token);

		if (!$this->validateSharePermissions($share)) {
			throw new NotFoundException();
		}

		if (!($share->getPermissions() & \OCP\Constants::PERMISSION_READ)) {
			return new \OCP\AppFramework\Http\DataResponse('Share has no read permission', Http::STATUS_FORBIDDEN);
		}

		$davUrl = '/public.php/dav/files/' . $token . '/?accept=zip';
		if ($files !== null) {
			$davUrl .= '&files=' . $files;
		}
		return new RedirectResponse($this->urlGenerator->getAbsoluteURL($davUrl));
	}

}
