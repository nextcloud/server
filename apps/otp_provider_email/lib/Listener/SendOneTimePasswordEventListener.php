<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\OTPProviderEmail\Listener;

use OCA\OTPProviderEmail\AppInfo\Application;
use OCP\Defaults;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\IL10N;
use OCP\Mail\IMailer;
use OCP\OneTimePassword\Events\SendOneTimePasswordEvent;
use OCP\Util;
use Psr\Log\LoggerInterface;

/**
 * @template-implements IEventListener<SendOneTimePasswordEvent>
 */
class SendOneTimePasswordEventListener implements IEventListener {

	public function __construct(
		private readonly IMailer $mailer,
		private readonly IL10N $l,
		private readonly Defaults $defaults,
		private readonly LoggerInterface $logger,
	) {
	}

	#[\Override]
	public function handle(Event $event): void {
		if (!($event instanceof SendOneTimePasswordEvent) || $event->getProvider() !== Application::OTP_PROVIDER_ID || $event->getWasConsumed()) {
			return;
		}

		$this->logger->debug('claiming SendOneTimePasswordEvent', ['app' => 'otp_provider_email']);
		$event->markConsumed();
		$pw = $event->getPassword();
		$rec = $event->getRecipient();
		$msg = $event->getMessage();
		try {
			$failedRecipients = $this->sendEmail($rec, $pw, $msg);
		} catch (\Exception $e) {
			$errMsg = 'Failed to send OTP email: ' . $e->getMessage();
			$this->logger->warning($errMsg, ['exception' => $e]);
			$event->setError($errMsg);
			return;
		}
		if (!empty($failedRecipients)) {
			$errMsg = 'Could not send OTP to the following recipients: [' . join(', ', $failedRecipients) . ']';
			$this->logger->warning($errMsg);
			$event->setError($errMsg);
		}
	}

	/**
	 * @param string $recipient
	 * @param string $password
	 * @return array
	 * @throws \Exception
	 */
	protected function sendEmail(string $recipient, string $password, ?string $recipientMsg): array {
		$message = $this->mailer->createMessage();
		$emailTemplate = $this->mailer->createEMailTemplate(Application::APP_ID . '.OTPMessage', [
			'otp' => $password
		]);
		$emailTemplate->setSubject($this->l->t('Your One-time Password'));
		$emailTemplate->addHeader();
		$emailTemplate->addHeading($this->l->t('You received a one-time password'));
		$msgParts = [
			(htmlspecialchars($recipientMsg) ?? $this->l->t('A one-time password for a Nextcloud resource has been requested for your email address.')),
			$this->l->t('If you have not requested a one-time password, you can ignore this message.')
		];

		$emailTemplate->addBodyText(
			'<p>' . $msgParts[0] . '</p>'
			. '<br><br><p><pre>' . htmlspecialchars($password) . '</pre></p><br><br>'
			. '<p>' . $msgParts[1] . '</p>',
			$msgParts[0] . '\n\r\n\r' . htmlspecialchars($password) . '\n\r\n\r' . $msgParts[1],
		);

		$instanceName = $this->defaults->getName();
		$message->setFrom([Util::getDefaultEmailAddress($instanceName) => $instanceName]);
		$emailTemplate->addFooter();

		$message->setTo([$recipient]);
		$message->useTemplate($emailTemplate);
		return $this->mailer->send($message);
	}
}
