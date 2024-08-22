<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Arne Hamann <kontakt+github@arne.email>
 * @author Branko Kokanovic <branko@kokanovic.org>
 * @author Carsten Wiedmann <carsten_sttgt@gmx.de>
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Jared Boone <jared.boone@gmail.com>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Julius Härtl <jus@bitgrid.net>
 * @author kevin147147 <kevintamool@gmail.com>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Tekhnee <info@tekhnee.org>
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
namespace OC\Mail;

use Egulias\EmailValidator\EmailValidator;
use Egulias\EmailValidator\Validation\NoRFCWarningsValidation;
use Egulias\EmailValidator\Validation\RFCValidation;
use OCP\Defaults;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IBinaryFinder;
use OCP\IConfig;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\L10N\IFactory;
use OCP\Mail\Events\BeforeMessageSent;
use OCP\Mail\IAttachment;
use OCP\Mail\IEMailTemplate;
use OCP\Mail\IMailer;
use OCP\Mail\IMessage;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\Mailer as SymfonyMailer;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mailer\Transport\SendmailTransport;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport;
use Symfony\Component\Mailer\Transport\Smtp\Stream\SocketStream;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Exception\RfcComplianceException;

/**
 * Class Mailer provides some basic functions to create a mail message that can be used in combination with
 * \OC\Mail\Message.
 *
 * Example usage:
 *
 * 	$mailer = \OC::$server->getMailer();
 * 	$message = $mailer->createMessage();
 * 	$message->setSubject('Your Subject');
 * 	$message->setFrom(array('cloud@domain.org' => 'ownCloud Notifier'));
 * 	$message->setTo(array('recipient@domain.org' => 'Recipient'));
 * 	$message->setBody('The message text', 'text/html');
 * 	$mailer->send($message);
 *
 * This message can then be passed to send() of \OC\Mail\Mailer
 *
 * @package OC\Mail
 */
class Mailer implements IMailer {
	// Do not move this block or change it's content without contacting the release crew
	public const DEFAULT_DIMENSIONS = '252x120';
	// Do not move this block or change it's content without contacting the release crew

	public const MAX_LOGO_SIZE = 105;

	private ?MailerInterface $instance = null;

	public function __construct(
		private IConfig          $config,
		private LoggerInterface  $logger,
		private Defaults         $defaults,
		private IURLGenerator    $urlGenerator,
		private IL10N            $l10n,
		private IEventDispatcher $dispatcher,
		private IFactory         $l10nFactory,
	) {
	}

	/**
	 * Creates a new message object that can be passed to send()
	 */
	public function createMessage(): Message {
		$plainTextOnly = $this->config->getSystemValueBool('mail_send_plaintext_only', false);
		return new Message(new Email(), $plainTextOnly);
	}

	/**
	 * @param string|null $data
	 * @param string|null $filename
	 * @param string|null $contentType
	 * @since 13.0.0
	 */
	public function createAttachment($data = null, $filename = null, $contentType = null): IAttachment {
		return new Attachment($data, $filename, $contentType);
	}

	/**
	 * @param string|null $contentType
	 * @since 13.0.0
	 */
	public function createAttachmentFromPath(string $path, $contentType = null): IAttachment {
		return new Attachment(null, null, $contentType, $path);
	}

	/**
	 * Creates a new email template object
	 *
	 * @since 12.0.0
	 */
	public function createEMailTemplate(string $emailId, array $data = []): IEMailTemplate {
		$logoDimensions = $this->config->getAppValue('theming', 'logoDimensions', self::DEFAULT_DIMENSIONS);
		if (str_contains($logoDimensions, 'x')) {
			[$width, $height] = explode('x', $logoDimensions);
			$width = (int) $width;
			$height = (int) $height;

			if ($width > self::MAX_LOGO_SIZE || $height > self::MAX_LOGO_SIZE) {
				if ($width === $height) {
					$logoWidth = self::MAX_LOGO_SIZE;
					$logoHeight = self::MAX_LOGO_SIZE;
				} elseif ($width > $height) {
					$logoWidth = self::MAX_LOGO_SIZE;
					$logoHeight = (int) (($height / $width) * self::MAX_LOGO_SIZE);
				} else {
					$logoWidth = (int) (($width / $height) * self::MAX_LOGO_SIZE);
					$logoHeight = self::MAX_LOGO_SIZE;
				}
			} else {
				$logoWidth = $width;
				$logoHeight = $height;
			}
		} else {
			$logoWidth = $logoHeight = null;
		}

		$class = $this->config->getSystemValueString('mail_template_class', '');

		if ($class !== '' && class_exists($class) && is_a($class, EMailTemplate::class, true)) {
			return new $class(
				$this->defaults,
				$this->urlGenerator,
				$this->l10nFactory,
				$logoWidth,
				$logoHeight,
				$emailId,
				$data
			);
		}

		return new EMailTemplate(
			$this->defaults,
			$this->urlGenerator,
			$this->l10nFactory,
			$logoWidth,
			$logoHeight,
			$emailId,
			$data
		);
	}

	/**
	 * Send the specified message. Also sets the from address to the value defined in config.php
	 * if no-one has been passed.
	 *
	 * If sending failed, the recipients that failed will be returned (to, cc and bcc).
	 * Will output additional debug info if 'mail_smtpdebug' => 'true' is set in config.php
	 *
	 * @param IMessage $message Message to send
	 * @return string[] $failedRecipients
	 */
	public function send(IMessage $message): array {
		$debugMode = $this->config->getSystemValueBool('mail_smtpdebug', false);

		if (!($message instanceof Message)) {
			throw new \InvalidArgumentException('Object not of type ' . Message::class);
		}

		if (empty($message->getFrom())) {
			$message->setFrom([\OCP\Util::getDefaultEmailAddress('no-reply') => $this->defaults->getName()]);
		}

		$mailer = $this->getInstance();

		$this->dispatcher->dispatchTyped(new BeforeMessageSent($message));

		try {
			$message->setRecipients();
		} catch (\InvalidArgumentException|RfcComplianceException $e) {
			$logMessage = sprintf(
				'Could not send mail to "%s" with subject "%s" as validation for address failed',
				print_r(array_merge($message->getTo(), $message->getCc(), $message->getBcc()), true),
				$message->getSubject()
			);
			$this->logger->debug($logMessage, ['app' => 'core', 'exception' => $e]);
			$recipients = array_merge($message->getTo(), $message->getCc(), $message->getBcc());
			$failedRecipients = [];

			array_walk($recipients, function ($value, $key) use (&$failedRecipients) {
				if (is_numeric($key)) {
					$failedRecipients[] = $value;
				} else {
					$failedRecipients[] = $key;
				}
			});

			return $failedRecipients;
		}

		try {
			$mailer->send($message->getSymfonyEmail());
		} catch (TransportExceptionInterface $e) {
			$logMessage = sprintf('Sending mail to "%s" with subject "%s" failed', print_r($message->getTo(), true), $message->getSubject());
			$this->logger->debug($logMessage, ['app' => 'core', 'exception' => $e]);
			if ($debugMode) {
				$this->logger->debug($e->getDebug(), ['app' => 'core']);
			}
			$recipients = array_merge($message->getTo(), $message->getCc(), $message->getBcc());
			$failedRecipients = [];

			array_walk($recipients, function ($value, $key) use (&$failedRecipients) {
				if (is_numeric($key)) {
					$failedRecipients[] = $value;
				} else {
					$failedRecipients[] = $key;
				}
			});

			return $failedRecipients;
		}

		// Debugging logging
		$logMessage = sprintf('Sent mail to "%s" with subject "%s"', print_r($message->getTo(), true), $message->getSubject());
		$this->logger->debug($logMessage, ['app' => 'core']);

		return [];
	}

	/**
	 * @deprecated 26.0.0 Implicit validation is done in \OC\Mail\Message::setRecipients
	 *                    via \Symfony\Component\Mime\Address::__construct
	 *
	 * @param string $email Email address to be validated
	 * @return bool True if the mail address is valid, false otherwise
	 */
	public function validateMailAddress(string $email): bool {
		if ($email === '') {
			// Shortcut: empty addresses are never valid
			return false;
		}

		$strictMailCheck = $this->config->getAppValue('core', 'enforce_strict_email_check', 'no') === 'yes';
		$validator = new EmailValidator();
		$validation = $strictMailCheck ? new NoRFCWarningsValidation() : new RFCValidation();

		return $validator->isValid($email, $validation);
	}

	protected function getInstance(): MailerInterface {
		if (!is_null($this->instance)) {
			return $this->instance;
		}

		$transport = null;

		switch ($this->config->getSystemValueString('mail_smtpmode', 'smtp')) {
			case 'sendmail':
				$transport = $this->getSendMailInstance();
				break;
			case 'smtp':
			default:
				$transport = $this->getSmtpInstance();
				break;
		}

		return new SymfonyMailer($transport);
	}

	/**
	 * Returns the SMTP transport
	 *
	 * Only supports ssl/tls
	 * starttls is not enforcable with Symfony Mailer but might be available
	 * via the automatic config (Symfony Mailer internal)
	 *
	 * @return EsmtpTransport
	 */
	protected function getSmtpInstance(): EsmtpTransport {
		// either null or true - if nothing is passed, let the symfony mailer figure out the configuration by itself
		$mailSmtpsecure = ($this->config->getSystemValue('mail_smtpsecure', null) === 'ssl') ? true : null;
		$transport = new EsmtpTransport(
			$this->config->getSystemValueString('mail_smtphost', '127.0.0.1'),
			$this->config->getSystemValueInt('mail_smtpport', 25),
			$mailSmtpsecure,
			null,
			$this->logger
		);
		/** @var SocketStream $stream */
		$stream = $transport->getStream();
		/** @psalm-suppress InternalMethod */
		$stream->setTimeout($this->config->getSystemValueInt('mail_smtptimeout', 10));

		if ($this->config->getSystemValueBool('mail_smtpauth', false)) {
			$transport->setUsername($this->config->getSystemValueString('mail_smtpname', ''));
			$transport->setPassword($this->config->getSystemValueString('mail_smtppassword', ''));
		}

		$streamingOptions = $this->config->getSystemValue('mail_smtpstreamoptions', []);
		if (is_array($streamingOptions) && !empty($streamingOptions)) {
			/** @psalm-suppress InternalMethod */
			$currentStreamingOptions = $stream->getStreamOptions();

			$currentStreamingOptions = array_merge_recursive($currentStreamingOptions, $streamingOptions);

			/** @psalm-suppress InternalMethod */
			$stream->setStreamOptions($currentStreamingOptions);
		}

		$overwriteCliUrl = parse_url(
			$this->config->getSystemValueString('overwrite.cli.url', ''),
			PHP_URL_HOST
		);

		if (!empty($overwriteCliUrl)) {
			$transport->setLocalDomain($overwriteCliUrl);
		}

		return $transport;
	}

	/**
	 * Returns the sendmail transport
	 *
	 * @return SendmailTransport
	 */
	protected function getSendMailInstance(): SendmailTransport {
		switch ($this->config->getSystemValueString('mail_smtpmode', 'smtp')) {
			case 'qmail':
				$binaryPath = '/var/qmail/bin/sendmail';
				break;
			default:
				$sendmail = \OCP\Server::get(IBinaryFinder::class)->findBinaryPath('sendmail');
				if ($sendmail === null) {
					$sendmail = '/usr/sbin/sendmail';
				}
				$binaryPath = $sendmail;
				break;
		}

		$binaryParam = match ($this->config->getSystemValueString('mail_sendmailmode', 'smtp')) {
			'pipe' => ' -t -i',
			default => ' -bs',
		};

		return new SendmailTransport($binaryPath . $binaryParam, null, $this->logger);
	}
}
