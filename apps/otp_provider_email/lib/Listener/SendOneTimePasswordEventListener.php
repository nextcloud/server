<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
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

		$event->markConsumed();
		$pw = $event->getPassword();
		$rec = $event->getRecipient();
		try {
			$failedRecipients = $this->sendEmail($rec, $pw);
		} catch (\Exception $e) {
			$this->logger->error($e->getMessage());
			$event->setError('Failed to send OTP email: ' . $e->getMessage());
			return;
		}
		if (!empty($failedRecipients)) {
			$event->setError('Could not send OTP to the following recipients: [' . join(', ', $failedRecipients) . ']');
			return;
		}
		$event->setMessage("OTP was sent to recipient '$rec'");
	}

	/**
	 * @param string $recipient
	 * @param string $password
	 * @return array
	 * @throws \Exception
	 */
	protected function sendEmail(string $recipient, string $password): array {
		$message = $this->mailer->createMessage();
		$emailTemplate = $this->mailer->createEMailTemplate(Application::APP_ID . '.OTPMessage', [
			'otp' => $password
		]);
		$emailTemplate->setSubject($this->l->t('OTP for a share'));
		$emailTemplate->addHeader();
		$emailTemplate->addHeading($this->l->t('You received an one-time password for a share.'));
		$emailTemplate->addBodyText(
			$this->l->t('Use this one time password to access the share sent to you: ') . "<pre>$password</pre>",
			$this->l->t('Use this one time password to access the share sent to you: ') . $password
		);

		$instanceName = $this->defaults->getName();
		$message->setFrom([Util::getDefaultEmailAddress($instanceName) => $instanceName]);
		$emailTemplate->addFooter();

		$message->setTo([$recipient]);
		$message->useTemplate($emailTemplate);
		return $this->mailer->send($message);
	}
}
