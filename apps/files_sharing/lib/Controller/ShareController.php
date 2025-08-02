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
use OCA\Files_Sharing\Event\ShareLinkAccessedEvent;
use OCP\Accounts\IAccountManager;
use OCP\AppFramework\AuthPublicShareController;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\Attribute\OpenAPI;
use OCP\AppFramework\Http\Attribute\PublicPage;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\NotFoundResponse;
use OCP\AppFramework\Http\RedirectResponse;
use OCP\AppFramework\Http\Response;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\Constants;
use OCP\Defaults;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\Files\NotFoundException;
use OCP\HintException;
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
    protected ?IShare $share = null;

    public const SHARE_ACCESS = 'access';
    public const SHARE_AUTH = 'auth';
    public const SHARE_DOWNLOAD = 'download';

    public function __construct(
        string $appName,
        IRequest $request,
        protected IConfig $config,
        IURLGenerator $urlGenerator,
        protected IUserManager $userManager,
        protected \OCP\Activity\IManager $activityManager,
        protected ShareManager $shareManager,
        ISession $session,
        protected IPreview $previewManager,
        protected IRootFolder $rootFolder,
        protected FederatedShareProvider $federatedShareProvider,
        protected IAccountManager $accountManager,
        protected IEventDispatcher $eventDispatcher,
        protected IL10N $l10n,
        protected ISecureRandom $secureRandom,
        protected Defaults $defaults,
        private IPublicShareTemplateFactory $publicShareTemplateFactory,
    ) {
        parent::__construct($appName, $request, $session, $urlGenerator);
    }

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

    protected function validateIdentity(?string $identityToken = null): bool {
        if ($this->share->getShareType() !== IShare::TYPE_EMAIL) {
            return false;
        }
        if ($identityToken === null || $this->share->getSharedWith() === null) {
            return false;
        }
        return $identityToken === $this->share->getSharedWith();
    }

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
        $this->session->set(PublicAuth::DAV_AUTHENTICATED, $this->share->getId());
    }

    protected function authFailed() {
        $this->emitAccessShareHook($this->share, 403, 'Wrong password');
        $this->emitShareAccessEvent($this->share, self::SHARE_AUTH, 403, 'Wrong password');
    }

    protected function emitAccessShareHook($share, int $errorCode = 200, string $errorMessage = '') {
        $itemType = $itemSource = $uidOwner = '';
        $token = $share;
        $exception = null;
        if ($share instanceof IShare) {
            try {
                $token = $share->getToken();
                $uidOwner = $share->getSharedBy();
                $itemType = $share->getNodeType();
                $itemSource = $share->getNodeId();
            } catch (\Exception $e) {
                $exception = $e;
            }
        }
        \OC_Hook::emit(Share::class, 'share_link_access', [
            'itemType' => $itemType,
            'itemSource' => $itemSource,
            'uidOwner' => $uidOwner,
            'token' => $token,
            'errorCode' => $errorCode,
            'errorMessage' => $errorMessage
        ]);
        if (!is_null($exception)) {
            throw $exception;
        }
    }

    protected function emitShareAccessEvent(IShare $share, string $step = '', int $errorCode = 200, string $errorMessage = ''): void {
        if ($step !== self::SHARE_ACCESS
            && $step !== self::SHARE_AUTH
            && $step !== self::SHARE_DOWNLOAD) {
            return;
        }
        $this->eventDispatcher->dispatchTyped(new ShareLinkAccessedEvent($share, $step, $errorCode, $errorMessage));
    }

    private function validateShare(IShare $share) {
        $owner = $this->userManager->get($share->getShareOwner());
        if ($owner === null || !$owner->isEnabled()) {
            return false;
        }
        $initiator = $this->userManager->get($share->getSharedBy());
        if ($initiator === null || !$initiator->isEnabled()) {
            return false;
        }
        return $share->getNode()->isReadable() && $share->getNode()->isShareable();
    }

    #[PublicPage]
    #[NoCSRFRequired]
    public function showShare($path = ''): TemplateResponse {
        \OC_User::setIncognitoMode(true);
        try {
            $share = $this->shareManager->getShareByToken($this->getToken());
        } catch (ShareNotFound $e) {
            $this->emitAccessShareHook($this->getToken(), 404, 'Share not found');
            throw new NotFoundException($this->l10n->t('This share does not exist or is no longer available'));
        }

        if (!$this->validateShare($share)) {
            throw new NotFoundException($this->l10n->t('This share does not exist or is no longer available'));
        }

        $shareNode = $share->getNode();
        try {
            $templateProvider = $this->publicShareTemplateFactory->getProvider($share);
            $response = $templateProvider->renderPage($share, $this->getToken(), $path);
        } catch (NotFoundException $e) {
            $this->emitAccessShareHook($share, 404, 'Share not found');
            $this->emitShareAccessEvent($share, ShareController::SHARE_ACCESS, 404, 'Share not found');
            throw new NotFoundException($this->l10n->t('This share does not exist or is no longer available'));
        }

        try {
            if ($shareNode instanceof File && $path !== '') {
                $this->emitAccessShareHook($share, 404, 'Share not found');
                $this->emitShareAccessEvent($share, self::SHARE_ACCESS, 404, 'Share not found');
                throw new NotFoundException($this->l10n->t('This share does not exist or is no longer available'));
            }
        } catch (\Exception $e) {
            $this->emitAccessShareHook($share, 404, 'Share not found');
            $this->emitShareAccessEvent($share, self::SHARE_ACCESS, 404, 'Share not found');
            throw $e;
        }

        $this->emitAccessShareHook($share);
        $this->emitShareAccessEvent($share, self::SHARE_ACCESS);

        return $response;
    }

    /**
     * @NoSameSiteCookieRequired
     *
     * @param string $token
     * @param string|null $files
     * @param string $path
     * @return void|Response
     * @throws NotFoundException
     * @deprecated 31.0.0 Users are encouraged to use the DAV endpoint
     */
    #[PublicPage]
    #[NoCSRFRequired]
    public function downloadShare($token, $files = null, $path = '') {
        \OC_User::setIncognitoMode(true);

        $share = $this->shareManager->getShareByToken($token);

        if (!($share->getPermissions() & Constants::PERMISSION_READ)) {
            return new DataResponse('Share has no read permission');
        }

        $attributes = $share->getAttributes();
        if ($attributes?->getAttribute('permissions', 'download') === false) {
            return new DataResponse('Share has no download permission');
        }

        if (!$this->validateShare($share)) {
            throw new NotFoundException();
        }

        $node = $share->getNode();
        if ($node instanceof Folder) {
            if ($path !== '') {
                try {
                    $node = $node->get($path);
                } catch (NotFoundException $e) {
                    $this->emitAccessShareHook($share, 404, 'Share not found');
                    $this->emitShareAccessEvent($share, self::SHARE_DOWNLOAD, 404, 'Share not found');
                    return new NotFoundResponse();
                }
            }
            if ($node instanceof Folder) {
                if (($files === null || $files === '') && $share->getHideDownload()) {
                    throw new NotFoundException('Downloading a folder');
                }
            }
        }

        // --- NEW: Allow streaming (Range requests) even when downloads are hidden ---
        $isRangeRequest = $this->request->getHeader('Range') !== null;
        if ($share->getHideDownload() && !$isRangeRequest) {
            // If hideDownload is enabled and it's NOT a streaming request, block it
            throw new NotFoundException('Downloading is disabled for this share');
        }

        $this->emitAccessShareHook($share);
        $this->emitShareAccessEvent($share, self::SHARE_DOWNLOAD);

        $davUrl = '/public.php/dav/files/' . $token . '/?accept=zip';
        if ($files !== null) {
            $davUrl .= '&files=' . $files;
        }
        return new RedirectResponse($this->urlGenerator->getAbsoluteURL($davUrl));
    }
}
