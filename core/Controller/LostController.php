<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Core\Controller;

use Exception;
use OC\Authentication\TwoFactorAuth\Manager;
use OC\Core\Events\BeforePasswordResetEvent;
use OC\Core\Events\PasswordResetEvent;
use OC\Core\Exception\ResetPasswordException;
use OC\Security\RateLimiting\Exception\RateLimitExceededException;
use OC\Security\RateLimiting\Limiter;
use OC\User\Session;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Attribute\AnonRateLimit;
use OCP\AppFramework\Http\Attribute\BruteForceProtection;
use OCP\AppFramework\Http\Attribute\FrontpageRoute;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\Attribute\OpenAPI;
use OCP\AppFramework\Http\Attribute\PublicPage;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Services\IInitialState;
use OCP\Defaults;
use OCP\Encryption\IEncryptionModule;
use OCP\Encryption\IManager;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\HintException;
use OCP\IConfig;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\IUserManager;
use OCP\Mail\IMailer;
use OCP\PreConditionNotMetException;
use OCP\Security\VerificationToken\InvalidTokenException;
use OCP\Security\VerificationToken\IVerificationToken;
use OCP\Server;
use OCP\Util;
use Psr\Log\LoggerInterface;
use function array_filter;
use function count;
use function reset;

/**
 * Class LostController
 *
 * Successfully changing a password will emit the post_passwordReset hook.
 *
 * @package OC\Core\Controller
 */
#[OpenAPI(scope: OpenAPI::SCOPE_IGNORE)]
class LostController extends Controller {
	public function __construct(
		string $appName,
		IRequest $request,
		private IURLGenerator $urlGenerator,
		private IUserManager $userManager,
		private Defaults $defaults,
		private IL10N $l10n,
		private IConfig $config,
		protected string $defaultMailAddress,
		private IManager $encryptionManager,
		private IMailer $mailer,
		private LoggerInterface $logger,
		private Manager $twoFactorManager,
		private IInitialState $initialState,
		private IVerificationToken $verificationToken,
		private IEventDispatcher $eventDispatcher,
		private Limiter $limiter,
	) {
		parent::__construct($appName, $request);
	}

	/**
	 * Someone wants to reset their password:
	 */
	#[PublicPage]
	#[NoCSRFRequired]
	#[BruteForceProtection(action: 'passwordResetEmail')]
	#[AnonRateLimit(limit: 10, period: 300)]
	#[FrontpageRoute(verb: 'GET', url: '/lostpassword/reset/form/{token}/{userId}')]
	public function resetform(string $token, string $userId): TemplateResponse {
		try {
			$this->checkPasswordResetToken($token, $userId);
		} catch (Exception $e) {
			if ($this->config->getSystemValue('lost_password_link', '') !== 'disabled'
				|| ($e instanceof InvalidTokenException
					&& !in_array($e->getCode(), [InvalidTokenException::TOKEN_NOT_FOUND, InvalidTokenException::USER_UNKNOWN]))
			) {
				$response = new TemplateResponse(
					'core', 'error', [
						'errors' => [['error' => $e->getMessage()]]
					],
					TemplateResponse::RENDER_AS_GUEST
				);
				$response->throttle();
				return $response;
			}
			return new TemplateResponse('core', 'error', [
				'errors' => [['error' => $this->l10n->t('Password reset is disabled')]]
			],
				TemplateResponse::RENDER_AS_GUEST
			);
		}
		$this->initialState->provideInitialState('resetPasswordUser', $userId);
		$this->initialState->provideInitialState('resetPasswordTarget',
			$this->urlGenerator->linkToRouteAbsolute('core.lost.setPassword', ['userId' => $userId, 'token' => $token])
		);

		return new TemplateResponse(
			'core',
			'login',
			[],
			'guest'
		);
	}

	/**
	 * @throws Exception
	 */
	protected function checkPasswordResetToken(string $token, string $userId): void {
		try {
			$user = $this->userManager->get($userId);
			$this->verificationToken->check($token, $user, 'lostpassword', $user ? $user->getEMailAddress() : '', true);
		} catch (InvalidTokenException $e) {
			$error = $e->getCode() === InvalidTokenException::TOKEN_EXPIRED
				? $this->l10n->t('Could not reset password because the token is expired')
				: $this->l10n->t('Could not reset password because the token is invalid');
			throw new Exception($error, (int)$e->getCode(), $e);
		}
	}

	private function error(string $message, array $additional = []): array {
		return array_merge(['status' => 'error', 'msg' => $message], $additional);
	}

	private function success(array $data = []): array {
		return array_merge($data, ['status' => 'success']);
	}

	#[PublicPage]
	#[BruteForceProtection(action: 'passwordResetEmail')]
	#[AnonRateLimit(limit: 10, period: 300)]
	#[FrontpageRoute(verb: 'POST', url: '/lostpassword/email')]
	public function email(string $user): JSONResponse {
		if ($this->config->getSystemValue('lost_password_link', '') !== '') {
			return new JSONResponse($this->error($this->l10n->t('Password reset is disabled')));
		}

		$user = trim($user);

		if (strlen($user) > 255) {
			return new JSONResponse($this->error($this->l10n->t('Unsupported email length (>255)')));
		}

		Util::emitHook(
			'\OCA\Files_Sharing\API\Server2Server',
			'preLoginNameUsedAsUserName',
			['uid' => &$user]
		);

		// FIXME: use HTTP error codes
		try {
			$this->sendEmail($user);
		} catch (ResetPasswordException $e) {
			// Ignore the error since we do not want to leak this info
			$this->logger->warning('Could not send password reset email: ' . $e->getMessage());
		} catch (Exception $e) {
			$this->logger->error($e->getMessage(), ['exception' => $e]);
		}

		$response = new JSONResponse($this->success());
		$response->throttle();
		return $response;
	}

	#[PublicPage]
	#[BruteForceProtection(action: 'passwordResetEmail')]
	#[AnonRateLimit(limit: 10, period: 300)]
	#[FrontpageRoute(verb: 'POST', url: '/lostpassword/set/{token}/{userId}')]
	public function setPassword(string $token, string $userId, string $password, bool $proceed): JSONResponse {
		if ($this->encryptionManager->isEnabled() && !$proceed) {
			$encryptionModules = $this->encryptionManager->getEncryptionModules();
			foreach ($encryptionModules as $module) {
				/** @var IEncryptionModule $instance */
				$instance = call_user_func($module['callback']);
				// this way we can find out whether per-user keys are used or a system wide encryption key
				if ($instance->needDetailedAccessList()) {
					return new JSONResponse($this->error('', ['encryption' => true]));
				}
			}
		}

		try {
			$this->checkPasswordResetToken($token, $userId);
			$user = $this->userManager->get($userId);

			$this->eventDispatcher->dispatchTyped(new BeforePasswordResetEvent($user, $password));
			\OC_Hook::emit('\OC\Core\LostPassword\Controller\LostController', 'pre_passwordReset', ['uid' => $userId, 'password' => $password]);

			if (strlen($password) > IUserManager::MAX_PASSWORD_LENGTH) {
				throw new HintException('Password too long', $this->l10n->t('Password is too long. Maximum allowed length is 469 characters.'));
			}

			if (!$user->setPassword($password)) {
				throw new Exception();
			}

			$this->eventDispatcher->dispatchTyped(new PasswordResetEvent($user, $password));
			\OC_Hook::emit('\OC\Core\LostPassword\Controller\LostController', 'post_passwordReset', ['uid' => $userId, 'password' => $password]);

			$this->twoFactorManager->clearTwoFactorPending($userId);

			$this->config->deleteUserValue($userId, 'core', 'lostpassword');
			@Server::get(Session::class)->unsetMagicInCookie();
		} catch (HintException $e) {
			$response = new JSONResponse($this->error($e->getHint()));
			$response->throttle();
			return $response;
		} catch (Exception $e) {
			$response = new JSONResponse($this->error($e->getMessage()));
			$response->throttle();
			return $response;
		}

		return new JSONResponse($this->success(['user' => $userId]));
	}

	/**
	 * @throws ResetPasswordException
	 * @throws PreConditionNotMetException
	 */
	protected function sendEmail(string $input): void {
		$user = $this->findUserByIdOrMail($input);
		$email = $user->getEMailAddress();

		if (empty($email)) {
			throw new ResetPasswordException('Could not send reset e-mail since there is no email for username ' . $input);
		}

		try {
			$this->limiter->registerUserRequest('lostpasswordemail', 5, 1800, $user);
		} catch (RateLimitExceededException $e) {
			throw new ResetPasswordException('Could not send reset e-mail, 5 of them were already sent in the last 30 minutes', 0, $e);
		}

		// Generate the token. It is stored encrypted in the database with the
		// secret being the users' email address appended with the system secret.
		// This makes the token automatically invalidate once the user changes
		// their email address.
		$token = $this->verificationToken->create($user, 'lostpassword', $email);

		$link = $this->urlGenerator->linkToRouteAbsolute('core.lost.resetform', ['userId' => $user->getUID(), 'token' => $token]);

		$emailTemplate = $this->mailer->createEMailTemplate('core.ResetPassword', [
			'link' => $link,
		]);

		$emailTemplate->setSubject($this->l10n->t('%s password reset', [$this->defaults->getName()]));
		$emailTemplate->addHeader();
		$emailTemplate->addHeading($this->l10n->t('Password reset'));

		$emailTemplate->addBodyText(
			htmlspecialchars($this->l10n->t('Click the following button to reset your password. If you have not requested the password reset, then ignore this email.')),
			$this->l10n->t('Click the following link to reset your password. If you have not requested the password reset, then ignore this email.')
		);

		$emailTemplate->addBodyButton(
			htmlspecialchars($this->l10n->t('Reset your password')),
			$link,
			false
		);
		$emailTemplate->addFooter();

		try {
			$message = $this->mailer->createMessage();
			$message->setTo([$email => $user->getDisplayName()]);
			$message->setFrom([$this->defaultMailAddress => $this->defaults->getName()]);
			$message->useTemplate($emailTemplate);
			$this->mailer->send($message);
		} catch (Exception $e) {
			// Log the exception and continue
			$this->logger->error($e->getMessage(), ['app' => 'core', 'exception' => $e]);
		}
	}

	/**
	 * @throws ResetPasswordException
	 */
	protected function findUserByIdOrMail(string $input): IUser {
		$user = $this->userManager->get($input);
		if ($user instanceof IUser) {
			if (!$user->isEnabled()) {
				throw new ResetPasswordException('Account ' . $user->getUID() . ' is disabled');
			}

			return $user;
		}

		$users = array_filter($this->userManager->getByEmail($input), function (IUser $user) {
			return $user->isEnabled();
		});

		if (count($users) === 1) {
			return reset($users);
		}

		throw new ResetPasswordException('Could not find user ' . $input);
	}
}
