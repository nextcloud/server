<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Bjoern Schiessle <bjoern@schiessle.org>
 * @author Björn Schießle <bjoern@schiessle.org>
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Daniel Calviño Sánchez <danxuliu@gmail.com>
 * @author Georg Ehrke <oc.list@georgehrke.com>
 * @author j3l11234 <297259024@qq.com>
 * @author Joas Schilling <coding@schilljs.com>
 * @author John Molakvoæ <skjnldsv@protonmail.com>
 * @author Jonas Sulzer <jonas@violoncello.ch>
 * @author Julius Härtl <jus@bitgrid.net>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author MartB <mart.b@outlook.de>
 * @author Maxence Lange <maxence@pontapreta.net>
 * @author Michael Weimann <mail@michael-weimann.eu>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Piotr Filiciak <piotr@filiciak.pl>
 * @author Robin Appelman <robin@icewind.nl>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Sascha Sambale <mastixmc@gmail.com>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author Vincent Petry <vincent@nextcloud.com>
 * @author Kate Döen <kate.doeen@nextcloud.com>
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
namespace OCA\Files_Sharing\Controller;

use OC\Security\CSP\ContentSecurityPolicy;
use OC_Files;
use OC_Util;
use OCA\FederatedFileSharing\FederatedShareProvider;
use OCA\Files_Sharing\Activity\Providers\Downloads;
use OCA\Files_Sharing\Event\BeforeTemplateRenderedEvent;
use OCA\Files_Sharing\Event\ShareLinkAccessedEvent;
use OCP\Accounts\IAccountManager;
use OCP\AppFramework\AuthPublicShareController;
use OCP\AppFramework\Http\Attribute\OpenAPI;
use OCP\AppFramework\Http\NotFoundResponse;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\Defaults;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\Files\NotFoundException;
use OCP\IConfig;
use OCP\IL10N;
use OCP\IPreview;
use OCP\IRequest;
use OCP\ISession;
use OCP\IURLGenerator;
use OCP\IUserManager;
use OCP\Security\ISecureRandom;
use OCP\Share;
use OCP\Share\Exceptions\ShareNotFound;
use OCP\Share\IManager as ShareManager;
use OCP\Share\IPublicShareTemplateFactory;
use OCP\Share\IShare;
use OCP\Template;

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
	 * @PublicPage
	 * @NoCSRFRequired
	 *
	 * Show the authentication page
	 * The form has to submit to the authenticate method route
	 */
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
		$event = new \OCP\Security\Events\GenerateSecurePasswordEvent();
		$this->eventDispatcher->dispatchTyped($event);
		$password = $event->getPassword() ?? $this->secureRandom->generate(20);

		$this->share->setPassword($password);
		$this->shareManager->updateShare($this->share);
	}

	protected function verifyPassword(string $password): bool {
		return $this->shareManager->checkPassword($this->share, $password);
	}

	protected function getPasswordHash(): string {
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
		// For share this was always set so it is still used in other apps
		$this->session->set('public_link_authenticated', (string)$this->share->getId());
	}

	protected function authFailed() {
		$this->emitAccessShareHook($this->share, 403, 'Wrong password');
		$this->emitShareAccessEvent($this->share, self::SHARE_AUTH, 403, 'Wrong password');
	}

	/**
	 * throws hooks when a share is attempted to be accessed
	 *
	 * @param \OCP\Share\IShare|string $share the Share instance if available,
	 * otherwise token
	 * @param int $errorCode
	 * @param string $errorMessage
	 *
	 * @throws \OCP\HintException
	 * @throws \OC\ServerNotAvailableException
	 *
	 * @deprecated use OCP\Files_Sharing\Event\ShareLinkAccessedEvent
	 */
	protected function emitAccessShareHook($share, int $errorCode = 200, string $errorMessage = '') {
		$itemType = $itemSource = $uidOwner = '';
		$token = $share;
		$exception = null;
		if ($share instanceof \OCP\Share\IShare) {
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
	private function validateShare(\OCP\Share\IShare $share) {
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
	 * @PublicPage
	 * @NoCSRFRequired
	 *
	 *
	 * @param string $path
	 * @return TemplateResponse
	 * @throws NotFoundException
	 * @throws \Exception
	 */
	public function showShare($path = ''): TemplateResponse {
		\OC_User::setIncognitoMode(true);

		// Check whether share exists
		try {
			$share = $this->shareManager->getShareByToken($this->getToken());
		} catch (ShareNotFound $e) {
			// The share does not exists, we do not emit an ShareLinkAccessedEvent
			$this->emitAccessShareHook($this->getToken(), 404, 'Share not found');
			throw new NotFoundException();
		}

		if (!$this->validateShare($share)) {
			throw new NotFoundException();
		}

		$shareNode = $share->getNode();

		try {
			$templateProvider = $this->publicShareTemplateFactory->getProvider($share);
			$response = $templateProvider->renderPage($share, $this->getToken(), $path);
		} catch (NotFoundException $e) {
			$this->emitAccessShareHook($share, 404, 'Share not found');
			$this->emitShareAccessEvent($share, ShareController::SHARE_ACCESS, 404, 'Share not found');
			throw new NotFoundException();
		}

		// We can't get the path of a file share
		try {
			if ($shareNode instanceof \OCP\Files\File && $path !== '') {
				$this->emitAccessShareHook($share, 404, 'Share not found');
				$this->emitShareAccessEvent($share, self::SHARE_ACCESS, 404, 'Share not found');
				throw new NotFoundException();
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
	 * @PublicPage
	 * @NoCSRFRequired
	 * @NoSameSiteCookieRequired
	 *
	 * @param string $token
	 * @param string $files
	 * @param string $path
	 * @param string $downloadStartSecret
	 * @return void|\OCP\AppFramework\Http\Response
	 * @throws NotFoundException
	 */
	public function downloadShare($token, $files = null, $path = '', $downloadStartSecret = '') {
		\OC_User::setIncognitoMode(true);

		$share = $this->shareManager->getShareByToken($token);

		if (!($share->getPermissions() & \OCP\Constants::PERMISSION_READ)) {
			return new \OCP\AppFramework\Http\DataResponse('Share has no read permission');
		}

		$files_list = null;
		if (!is_null($files)) { // download selected files
			$files_list = json_decode($files);
			// in case we get only a single file
			if ($files_list === null) {
				$files_list = [$files];
			}
			// Just in case $files is a single int like '1234'
			if (!is_array($files_list)) {
				$files_list = [$files_list];
			}
		}

		if (!$this->validateShare($share)) {
			throw new NotFoundException();
		}

		$userFolder = $this->rootFolder->getUserFolder($share->getShareOwner());
		$originalSharePath = $userFolder->getRelativePath($share->getNode()->getPath());


		// Single file share
		if ($share->getNode() instanceof \OCP\Files\File) {
			// Single file download
			$this->singleFileDownloaded($share, $share->getNode());
		}
		// Directory share
		else {
			/** @var \OCP\Files\Folder $node */
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

			$originalSharePath = $userFolder->getRelativePath($node->getPath());

			if ($node instanceof \OCP\Files\File) {
				// Single file download
				$this->singleFileDownloaded($share, $share->getNode());
			} else {
				try {
					if (!empty($files_list)) {
						$this->fileListDownloaded($share, $files_list, $node);
					} else {
						// The folder is downloaded
						$this->singleFileDownloaded($share, $share->getNode());
					}
				} catch (NotFoundException $e) {
					return new NotFoundResponse();
				}
			}
		}

		/* FIXME: We should do this all nicely in OCP */
		OC_Util::tearDownFS();
		OC_Util::setupFS($share->getShareOwner());

		/**
		 * this sets a cookie to be able to recognize the start of the download
		 * the content must not be longer than 32 characters and must only contain
		 * alphanumeric characters
		 */
		if (!empty($downloadStartSecret)
			&& !isset($downloadStartSecret[32])
			&& preg_match('!^[a-zA-Z0-9]+$!', $downloadStartSecret) === 1) {
			// FIXME: set on the response once we use an actual app framework response
			setcookie('ocDownloadStarted', $downloadStartSecret, time() + 20, '/');
		}

		$this->emitAccessShareHook($share);
		$this->emitShareAccessEvent($share, self::SHARE_DOWNLOAD);

		$server_params = [ 'head' => $this->request->getMethod() === 'HEAD' ];

		/**
		 * Http range requests support
		 */
		if (isset($_SERVER['HTTP_RANGE'])) {
			$server_params['range'] = $this->request->getHeader('Range');
		}

		// download selected files
		if (!is_null($files) && $files !== '') {
			// FIXME: The exit is required here because otherwise the AppFramework is trying to add headers as well
			// after dispatching the request which results in a "Cannot modify header information" notice.
			OC_Files::get($originalSharePath, $files_list, $server_params);
			exit();
		} else {
			// FIXME: The exit is required here because otherwise the AppFramework is trying to add headers as well
			// after dispatching the request which results in a "Cannot modify header information" notice.
			OC_Files::get(dirname($originalSharePath), basename($originalSharePath), $server_params);
			exit();
		}
	}

	/**
	 * create activity for every downloaded file
	 *
	 * @param Share\IShare $share
	 * @param array $files_list
	 * @param \OCP\Files\Folder $node
	 * @throws NotFoundException when trying to download a folder or multiple files of a "hide download" share
	 */
	protected function fileListDownloaded(Share\IShare $share, array $files_list, \OCP\Files\Folder $node) {
		if ($share->getHideDownload() && count($files_list) > 1) {
			throw new NotFoundException('Downloading more than 1 file');
		}

		foreach ($files_list as $file) {
			$subNode = $node->get($file);
			$this->singleFileDownloaded($share, $subNode);
		}
	}

	/**
	 * create activity if a single file was downloaded from a link share
	 *
	 * @param Share\IShare $share
	 * @throws NotFoundException when trying to download a folder of a "hide download" share
	 */
	protected function singleFileDownloaded(Share\IShare $share, \OCP\Files\Node $node) {
		if ($share->getHideDownload() && $node instanceof Folder) {
			throw new NotFoundException('Downloading a folder');
		}

		$fileId = $node->getId();

		$userFolder = $this->rootFolder->getUserFolder($share->getSharedBy());
		$userNodeList = $userFolder->getById($fileId);
		$userNode = $userNodeList[0];
		$ownerFolder = $this->rootFolder->getUserFolder($share->getShareOwner());
		$userPath = $userFolder->getRelativePath($userNode->getPath());
		$ownerPath = $ownerFolder->getRelativePath($node->getPath());
		$remoteAddress = $this->request->getRemoteAddress();
		$dateTime = new \DateTime();
		$dateTime = $dateTime->format('Y-m-d H');
		$remoteAddressHash = md5($dateTime . '-' . $remoteAddress);

		$parameters = [$userPath];

		if ($share->getShareType() === IShare::TYPE_EMAIL) {
			if ($node instanceof \OCP\Files\File) {
				$subject = Downloads::SUBJECT_SHARED_FILE_BY_EMAIL_DOWNLOADED;
			} else {
				$subject = Downloads::SUBJECT_SHARED_FOLDER_BY_EMAIL_DOWNLOADED;
			}
			$parameters[] = $share->getSharedWith();
		} else {
			if ($node instanceof \OCP\Files\File) {
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
