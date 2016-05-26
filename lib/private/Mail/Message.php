<?php
/**
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 *
 * @copyright Copyright (c) 2016, ownCloud, Inc.
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OC\Mail;

use Swift_Message;

/**
 * Class Message provides a wrapper around SwiftMail
 *
 * @package OC\Mail
 */
class Message {
	/** @var Swift_Message */
	private $swiftMessage;

	/**
	 * @param Swift_Message $swiftMessage
	 */
	function __construct(Swift_Message $swiftMessage) {
		$this->swiftMessage = $swiftMessage;
	}

	/**
	 * SwiftMailer does currently not work with IDN domains, this function therefore converts the domains
	 * FIXME: Remove this once SwiftMailer supports IDN
	 *
	 * @param array $addresses Array of mail addresses, key will get converted
	 * @return array Converted addresses if `idn_to_ascii` exists
	 */
	protected function convertAddresses($addresses) {
		if (!function_exists('idn_to_ascii')) {
			return $addresses;
		}

		$convertedAddresses = array();

		foreach($addresses as $email => $readableName) {
			if(!is_numeric($email)) {
				list($name, $domain) = explode('@', $email, 2);
				$domain = idn_to_ascii($domain);
				$convertedAddresses[$name.'@'.$domain] = $readableName;
			} else {
				list($name, $domain) = explode('@', $readableName, 2);
				$domain = idn_to_ascii($domain);
				$convertedAddresses[$email] = $name.'@'.$domain;
			}
		}

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
	public function setFrom(array $addresses) {
		$addresses = $this->convertAddresses($addresses);

		$this->swiftMessage->setFrom($addresses);
		return $this;
	}

	/**
	 * Get the from address of this message.
	 *
	 * @return array
	 */
	public function getFrom() {
		return $this->swiftMessage->getFrom();
	}

	/**
	 * Set the Reply-To address of this message
	 *
	 * @param array $addresses
	 * @return $this
	 */
	public function setReplyTo(array $addresses) {
		$addresses = $this->convertAddresses($addresses);

		$this->swiftMessage->setReplyTo($addresses);
		return $this;
	}

	/**
	 * Returns the Reply-To address of this message
	 *
	 * @return array
	 */
	public function getReplyTo() {
		return $this->swiftMessage->getReplyTo();
	}

	/**
	 * Set the to addresses of this message.
	 *
	 * @param array $recipients Example: array('recipient@domain.org', 'other@domain.org' => 'A name')
	 * @return $this
	 */
	public function setTo(array $recipients) {
		$recipients = $this->convertAddresses($recipients);

		$this->swiftMessage->setTo($recipients);
		return $this;
	}

	/**
	 * Get the to address of this message.
	 *
	 * @return array
	 */
	public function getTo() {
		return $this->swiftMessage->getTo();
	}

	/**
	 * Set the CC recipients of this message.
	 *
	 * @param array $recipients Example: array('recipient@domain.org', 'other@domain.org' => 'A name')
	 * @return $this
	 */
	public function setCc(array $recipients) {
		$recipients = $this->convertAddresses($recipients);

		$this->swiftMessage->setCc($recipients);
		return $this;
	}

	/**
	 * Get the cc address of this message.
	 *
	 * @return array
	 */
	public function getCc() {
		return $this->swiftMessage->getCc();
	}

	/**
	 * Set the BCC recipients of this message.
	 *
	 * @param array $recipients Example: array('recipient@domain.org', 'other@domain.org' => 'A name')
	 * @return $this
	 */
	public function setBcc(array $recipients) {
		$recipients = $this->convertAddresses($recipients);

		$this->swiftMessage->setBcc($recipients);
		return $this;
	}

	/**
	 * Get the Bcc address of this message.
	 *
	 * @return array
	 */
	public function getBcc() {
		return $this->swiftMessage->getBcc();
	}

	/**
	 * Set the subject of this message.
	 *
	 * @param $subject
	 * @return $this
	 */
	public function setSubject($subject) {
		$this->swiftMessage->setSubject($subject);
		return $this;
	}

	/**
	 * Get the from subject of this message.
	 *
	 * @return string
	 */
	public function getSubject() {
		return $this->swiftMessage->getSubject();
	}

	/**
	 * Set the plain-text body of this message.
	 *
	 * @param string $body
	 * @return $this
	 */
	public function setPlainBody($body) {
		$this->swiftMessage->setBody($body);
		return $this;
	}

	/**
	 * Get the plain body of this message.
	 *
	 * @return string
	 */
	public function getPlainBody() {
		return $this->swiftMessage->getBody();
	}

	/**
	 * Set the HTML body of this message. Consider also sending a plain-text body instead of only an HTML one.
	 *
	 * @param string $body
	 * @return $this
	 */
	public function setHtmlBody($body) {
		$this->swiftMessage->addPart($body, 'text/html');
		return $this;
	}

	/**
	 * Get's the underlying SwiftMessage
	 * @return Swift_Message
	 */
	public function getSwiftMessage() {
		return $this->swiftMessage;
	}

	/**
	 * @param string $body
	 * @param string $contentType
	 * @return $this
	 */
	public function setBody($body, $contentType) {
		$this->swiftMessage->setBody($body, $contentType);
		return $this;
	}
}
