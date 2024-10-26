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
use OCA\Files_Sharing\Activity\Providers\Downloads;
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
use OCP\Files\Node;
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

	protected function authFailed() {
		$this->emitAccessShareHook($this->share, 403, 'Wrong password');
		$this->emitShareAccessEvent($this->share, self::SHARE_AUTH, 403, 'Wrong password');
	}

	/**
	 * throws hooks when a share is attempted to be accessed
	 *
	 * @param IShare|string $share the Share instance if available,
	 *                             otherwise token
	 * @param int $errorCode
	 * @param string $errorMessage
	 *
	 * @throws HintException
	 * @throws \OC\ServerNotAvailableException
	 *
	 * @deprecated use OCP\Files_Sharing\Event\ShareLinkAccessedEvent
	 */
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
				// we log what we know and pass on the exception afterwards
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

	/**
	 * Emit a ShareLinkAccessedEvent event when a share is accessed, downloaded, auth...
	 */
	protected function emitShareAccessEvent(IShare $share, string $step = '', int $errorCode = 200, string $errorMessage = ''): void {
		if ($step !== self::SHARE_ACCESS &&
			$step !== self::SHARE_AUTH &&
			$step !== self::SHARE_DOWNLOAD) {
			return;
		}
		$this->eventDispatcher->dispatchTyped(new ShareLinkAccessedEvent($share, $step, $errorCode, $errorMessage));
	}

	/**
	 * Validate the permissions of the share
	 *
	 * @param Share\IShare $share
	 * @return bool
	 */
	private function validateShare(IShare $share) {
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
			// The share does not exists, we do not emit an ShareLinkAccessedEvent
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

		// We can't get the path of a file share
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

		if (!$this->validateShare($share)) {
			throw new NotFoundException();
		}

		// Single file share
		if ($share->getNode() instanceof File) {
			// Single file download
			$this->singleFileDownloaded($share, $share->getNode());
		}
		// Directory share
		else {
			/** @var Folder $node */
			$node = $share->getNode();

			// Try to get the path
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
				if ($files === null || $files === '') {
					// The folder is downloaded
					$this->singleFileDownloaded($share, $share->getNode());
				} else {
					$fileList = json_decode($files);
					// in case we get only a single file
					if (!is_array($fileList)) {
						$fileList = [$fileList];
					}
					foreach ($fileList as $file) {
						$subNode = $node->get($file);
						$this->singleFileDownloaded($share, $subNode);
					}
				}
			} else {
				// Single file download
				$this->singleFileDownloaded($share, $share->getNode());
			}
		}

		$this->emitAccessShareHook($share);
		$this->emitShareAccessEvent($share, self::SHARE_DOWNLOAD);

		$davUrl = '/public.php/dav/files/' . $token . '/?accept=zip';
		if ($files !== null) {
			$davUrl .= '&files=' . $files;
		}
		return new RedirectResponse($this->urlGenerator->getAbsoluteURL($davUrl));
	}

	/**
	 * create activity if a single file was downloaded from a link share
	 *
	 * @param Share\IShare $share
	 * @throws NotFoundException when trying to download a folder of a "hide download" share
	 */
	protected function singleFileDownloaded(IShare $share, Node $node) {
		if ($share->getHideDownload() && $node instanceof Folder) {
			throw new NotFoundException('Downloading a folder');
		}

		$fileId = $node->getId();

		$userFolder = $this->rootFolder->getUserFolder($share->getSharedBy());
		$userNode = $userFolder->getFirstNodeById($fileId);
		$ownerFolder = $this->rootFolder->getUserFolder($share->getShareOwner());
		$userPath = $userFolder->getRelativePath($userNode->getPath());
		$ownerPath = $ownerFolder->getRelativePath($node->getPath());
		$remoteAddress = $this->request->getRemoteAddress();
		$dateTime = new \DateTime();
		$dateTime = $dateTime->format('Y-m-d H');
		$remoteAddressHash = md5($dateTime . '-' . $remoteAddress);

		$parameters = [$userPath];

		if ($share->getShareType() === IShare::TYPE_EMAIL) {
			if ($node instanceof File) {
				$subject = Downloads::SUBJECT_SHARED_FILE_BY_EMAIL_DOWNLOADED;
			} else {
				$subject = Downloads::SUBJECT_SHARED_FOLDER_BY_EMAIL_DOWNLOADED;
			}
			$parameters[] = $share->getSharedWith();
		} else {
			if ($node instanceof File) {
				$subject = Downloads::SUBJECT_PUBLIC_SHARED_FILE_DOWNLOADED;
				$parameters[] = $remoteAddressHash;
			} else {
				$subject = Downloads::SUBJECT_PUBLIC_SHARED_FOLDER_DOWNLOADED;
				$parameters[] = $remoteAddressHash;
			}
		}

		$this->publishActivity($subject, $parameters, $share->getSharedBy(), $fileId, $userPath);

		if ($share->getShareOwner() !== $share->getSharedBy()) {
			$parameters[0] = $ownerPath;
			$this->publishActivity($subject, $parameters, $share->getShareOwner(), $fileId, $ownerPath);
		}
	}

	/**
	 * publish activity
	 *
	 * @param string $subject
	 * @param array $parameters
	 * @param string $affectedUser
	 * @param int $fileId
	 * @param string $filePath
	 */
	protected function publishActivity($subject,
		array $parameters,
		$affectedUser,
		$fileId,
		$filePath) {
		$event = $this->activityManager->generateEvent();
		$event->setApp('files_sharing')
			->setType('public_links')
			->setSubject($subject, $parameters)
			->setAffectedUser($affectedUser)
			->setObject('files', $fileId, $filePath);
		$this->activityManager->publish($event);
	}
}
