<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Arne Hamann <kontakt+github@arne.email>
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Jared Boone <jared.boone@gmail.com>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
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

use OCP\Mail\Headers\AutoSubmitted;
use OCP\Mail\IAttachment;
use OCP\Mail\IEMailTemplate;
use OCP\Mail\IMessage;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Exception\InvalidArgumentException;
use Symfony\Component\Mime\Exception\RfcComplianceException;

/**
 * Class Message provides a wrapper around Symfony\Component\Mime\Email (Used to be around SwiftMail)
 *
 * @package OC\Mail
 */
class Message implements IMessage {
	private array $to = [];
	private array $from = [];
	private array $replyTo = [];
	private array $cc = [];
	private array $bcc = [];

	public function __construct(
		private Email $symfonyEmail,
		private bool $plainTextOnly,
	) {
	}

	/**
	 * @since 13.0.0
	 * @return $this
	 */
	public function attach(IAttachment $attachment): IMessage {
		/** @var Attachment $attachment */
		$attachment->attach($this->symfonyEmail);
		return $this;
	}

	/**
	 * Can be used to "attach content inline" as message parts with specific MIME type and encoding.
	 * {@inheritDoc}
	 * @since 26.0.0
	 */
	public function attachInline(string $body, string $name, string $contentType = null): IMessage {
		# To be sure this works with iCalendar messages, we encode with 8bit instead of
		# quoted-printable encoding. We save the current encoder, replace the current
		# encoder with an 8bit encoder and after we've finished, we reset the encoder
		# to the previous one. Originally intended to be added after the message body,
		# as it is curently unknown if all mail clients handle this properly if added
		# before.
		$this->symfonyEmail->embed($body, $name, $contentType);
		return $this;
	}

	/**
	 * Converts the [['displayName' => 'email'], ['displayName2' => 'email2']] arrays to valid Adresses
	 *
	 * @param array $addresses Array of mail addresses
	 * @return Address[]
	 * @throws RfcComplianceException|InvalidArgumentException
	 */
	protected function convertAddresses(array $addresses): array {
		$convertedAddresses = [];

		if (empty($addresses)) {
			return [];
		}

		array_walk($addresses, function ($readableName, $email) use (&$convertedAddresses) {
			if (is_numeric($email)) {
				$convertedAddresses[] = new Address($readableName);
			} else {
				$convertedAddresses[] = new Address($email, $readableName);
			}
		});

		return $convertedAddresses;
	}

	/**
	 * Set the from address of this message.
	 *
	 * If no "From" address is used \OC\Mail\Mailer will use mail_from_address and mail_domain from config.php
	 *
	 * @param array $addresses Example: array('sender@domain.org', 'other@domain.org' => 'A name')
	 * @return $this
	 */
	public function setFrom(array $addresses): IMessage {
		$this->from = $addresses;
		return $this;
	}

	/**
	 * Get the from address of this message.
	 */
	public function getFrom(): array {
		return $this->from;
	}

	/**
	 * Set the Reply-To address of this message
	 * @return $this
	 */
	public function setReplyTo(array $addresses): IMessage {
		$this->replyTo = $addresses;
		return $this;
	}

	/**
	 * Returns the Reply-To address of this message
	 */
	public function getReplyTo(): array {
		return $this->replyTo;
	}

	/**
	 * Set the to addresses of this message.
	 *
	 * @param array $recipients Example: array('recipient@domain.org', 'other@domain.org' => 'A name')
	 * @return $this
	 */
	public function setTo(array $recipients): IMessage {
		$this->to = $recipients;
		return $this;
	}

	/**
	 * Get the to address of this message.
	 */
	public function getTo(): array {
		return $this->to;
	}

	/**
	 * Set the CC recipients of this message.
	 *
	 * @param array $recipients Example: array('recipient@domain.org', 'other@domain.org' => 'A name')
	 * @return $this
	 */
	public function setCc(array $recipients): IMessage {
		$this->cc = $recipients;
		return $this;
	}

	/**
	 * Get the cc address of this message.
	 */
	public function getCc(): array {
		return $this->cc;
	}

	/**
	 * Set the BCC recipients of this message.
	 *
	 * @param array $recipients Example: array('recipient@domain.org', 'other@domain.org' => 'A name')
	 * @return $this
	 */
	public function setBcc(array $recipients): IMessage {
		$this->bcc = $recipients;
		return $this;
	}

	/**
	 * Get the Bcc address of this message.
	 */
	public function getBcc(): array {
		return $this->bcc;
	}

	/**
	 * @return $this
	 */
	public function setSubject(string $subject): IMessage {
		$this->symfonyEmail->subject($subject);
		return $this;
	}

	/**
	 * Get the from subject of this message.
	 */
	public function getSubject(): string {
		return $this->symfonyEmail->getSubject() ?? '';
	}

	/**
	 * @return $this
	 */
	public function setPlainBody(string $body): IMessage {
		$this->symfonyEmail->text($body);
		return $this;
	}

	/**
	 * Get the plain body of this message.
	 */
	public function getPlainBody(): string {
		/** @var string $body */
		$body = $this->symfonyEmail->getTextBody() ?? '';
		return $body;
	}

	/**
	 * @return $this
	 */
	public function setHtmlBody(string $body): IMessage {
		if (!$this->plainTextOnly) {
			$this->symfonyEmail->html($body);
		}
		return $this;
	}

	/**
	 * Set the underlying Email instance
	 */
	public function setSymfonyEmail(Email $symfonyEmail): void {
		$this->symfonyEmail = $symfonyEmail;
	}

	/**
	 * Get the underlying Email instance
	 */
	public function getSymfonyEmail(): Email {
		return $this->symfonyEmail;
	}

	/**
	 * @return $this
	 */
	public function setBody(string $body, string $contentType): IMessage {
		if (!$this->plainTextOnly || $contentType !== 'text/html') {
			if ($contentType === 'text/html') {
				$this->symfonyEmail->html($body);
			} else {
				$this->symfonyEmail->text($body);
			}
		}
		return $this;
	}

	/**
	 * Set the recipients on the symphony email
	 *
	 * Since
	 *
	 * setTo
	 * setFrom
	 * setReplyTo
	 * setCc
	 * setBcc
	 *
	 * could throw a \Symfony\Component\Mime\Exception\RfcComplianceException
	 * or a \Symfony\Component\Mime\Exception\InvalidArgumentException
	 * we wrap the calls here. We then have the validation errors all in one place and can
	 * throw shortly before \OC\Mail\Mailer::send
	 *
	 * @throws InvalidArgumentException|RfcComplianceException
	 */
	public function setRecipients(): void {
		$this->symfonyEmail->to(...$this->convertAddresses($this->getTo()));
		$this->symfonyEmail->from(...$this->convertAddresses($this->getFrom()));
		$this->symfonyEmail->replyTo(...$this->convertAddresses($this->getReplyTo()));
		$this->symfonyEmail->cc(...$this->convertAddresses($this->getCc()));
		$this->symfonyEmail->bcc(...$this->convertAddresses($this->getBcc()));
	}

	/**
	 * @return $this
	 */
	public function useTemplate(IEMailTemplate $emailTemplate): IMessage {
		$this->setSubject($emailTemplate->renderSubject());
		$this->setPlainBody($emailTemplate->renderText());
		if (!$this->plainTextOnly) {
			$this->setHtmlBody($emailTemplate->renderHtml());
		}
		return $this;
	}

	/**
	 * Add the Auto-Submitted header to the email, preventing most automated
	 * responses to automated messages.
	 *
	 * @param AutoSubmitted::VALUE_* $value (one of AutoSubmitted::VALUE_NO, AutoSubmitted::VALUE_AUTO_GENERATED, AutoSubmitted::VALUE_AUTO_REPLIED)
	 * @return $this
	 */
	public function setAutoSubmitted(string $value): IMessage {
		$headers = $this->symfonyEmail->getHeaders();

		if ($headers->has(AutoSubmitted::HEADER)) {
			// if the header already exsists, remove it.
			// the value can be modified with some implementations
			// of the interface \Swift_Mime_Header, however the
			// interface doesn't, and this makes the static-code
			// analysis unhappy.
			// @todo check if symfony mailer can modify the autosubmitted header
			$headers->remove(AutoSubmitted::HEADER);
		}

		$headers->addTextHeader(AutoSubmitted::HEADER, $value);

		return $this;
	}

	/**
	 * Get the current value of the Auto-Submitted header. Defaults to "no"
	 * which is equivalent to the header not existing at all
	 */
	public function getAutoSubmitted(): string {
		$headers = $this->symfonyEmail->getHeaders();

		return $headers->has(AutoSubmitted::HEADER) ?
			$headers->get(AutoSubmitted::HEADER)->getBodyAsString() : AutoSubmitted::VALUE_NO;
	}
}
