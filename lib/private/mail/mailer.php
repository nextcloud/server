<?php
/**
 * Copyright (c) 2014-2015 Lukas Reschke <lukas@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OC\Mail;

use OCP\IConfig;
use OCP\Mail\IMailer;

/**
 * Class Mailer provides some basic functions to create a mail message that can be used in combination with
 * \OC\Mail\Message.
 *
 * Example usage:
 *
 * 	$mailer = \OC::$server->getMailer();
 * 	$message = $mailer->createMessage();
 * 	$message->setSubject('Your Subject');
 * 	$message->setFrom(array('cloud@domain.org' => 'ownCloud Notifier');
 * 	$message->setTo(array('recipient@domain.org' => 'Recipient');
 * 	$message->setBody('The message text');
 * 	$mailer->send($message);
 *
 * This message can then be passed to send() of \OC\Mail\Mailer
 *
 * @package OC\Mail
 */
class Mailer implements IMailer {
	/** @var \Swift_SmtpTransport|\Swift_SendmailTransport|\Swift_MailTransport Cached transport */
	private $instance = null;
	/** @var IConfig */
	private $config;
	/** @var \OC_Defaults */
	private $defaults;

	/**
	 * @param IConfig $config
	 * @param \OC_Defaults $defaults
	 */
	function __construct(IConfig $config, \OC_Defaults $defaults) {
		$this->config = $config;
		$this->defaults = $defaults;
	}

	/**
	 * Creates a new message object that can be passed to send()
	 *
	 * @return Message
	 */
	public function createMessage() {
		return new Message(new \Swift_Message());
	}

	/**
	 * Send the specified message. Also sets the from address to the value defined in config.php
	 * if no-one has been passed.
	 *
	 * @param Message $message Message to send
	 * @return string[] Array with failed recipients. Be aware that this depends on the used mail backend and
	 * therefore should be considered
	 * @throws \Exception In case it was not possible to send the message. (for example if an invalid mail address
	 * has been supplied.)
	 */
	public function send(Message $message) {
		if (sizeof($message->getFrom()) === 0) {
			$message->setFrom(array(\OCP\Util::getDefaultEmailAddress($this->defaults->getName())));
		}

		$failedRecipients = array();

		$this->getInstance()->send($message->getSwiftMessage(), $failedRecipients);

		return $failedRecipients;
	}

	/**
	 * Checks if an e-mail address is valid
	 *
	 * @param string $email Email address to be validated
	 * @return bool True if the mail address is valid, false otherwise
	 */
	public function validateMailAddress($email) {
		return \Swift_Validate::email($this->convertEmail($email));
	}

	/**
	 * SwiftMailer does currently not work with IDN domains, this function therefore converts the domains
	 *
	 * FIXME: Remove this once SwiftMailer supports IDN
	 *
	 * @param string $email
	 * @return string Converted mail address if `idn_to_ascii` exists
	 */
	protected function convertEmail($email) {
		if (!function_exists('idn_to_ascii') || strpos($email, '@') === false) {
			return $email;
		}

		list($name, $domain) = explode('@', $email, 2);
		$domain = idn_to_ascii($domain);
		return $name.'@'.$domain;
	}

	/**
	 * Returns whatever transport is configured within the config
	 *
	 * @return \Swift_SmtpTransport|\Swift_SendmailTransport|\Swift_MailTransport
	 */
	protected function getInstance() {
		if (!is_null($this->instance)) {
			return $this->instance;
		}

		switch ($this->config->getSystemValue('mail_smtpmode', 'php')) {
			case 'smtp':
				$this->instance = $this->getSMTPInstance();
				break;
			case 'sendmail':
				$this->instance = $this->getSendMailInstance();
				break;
			default:
				$this->instance = $this->getMailInstance();
				break;
		}

		return $this->instance;
	}

	/**
	 * Returns the SMTP transport
	 *
	 * @return \Swift_SmtpTransport
	 */
	protected function getSmtpInstance() {
		$transport = \Swift_SmtpTransport::newInstance();
		$transport->setTimeout($this->config->getSystemValue('mail_smtptimeout', 10));
		$transport->setHost($this->config->getSystemValue('mail_smtphost', '127.0.0.1'));
		$transport->setPort($this->config->getSystemValue('mail_smtpport', 25));
		if ($this->config->getSystemValue('mail_smtpauth', false)) {
			$transport->setUsername($this->config->getSystemValue('mail_smtpname', ''));
			$transport->setPassword($this->config->getSystemValue('mail_smtppassword', ''));
			$transport->setAuthMode($this->config->getSystemValue('mail_smtpauthtype', 'LOGIN'));
		}
		$smtpSecurity = $this->config->getSystemValue('mail_smtpsecure', '');
		if (!empty($smtpSecurity)) {
			$transport->setEncryption($smtpSecurity);
		}
		return $transport;
	}

	/**
	 * Returns the sendmail transport
	 *
	 * @return \Swift_SendmailTransport
	 */
	protected function getSendMailInstance() {
		switch ($this->config->getSystemValue('mail_smtpmode', 'sendmail')) {
			case 'qmail':
				$binaryPath = '/var/qmail/bin/sendmail';
				break;
			default:
				$binaryPath = '/usr/sbin/sendmail';
				break;
		}

		return \Swift_SendmailTransport::newInstance($binaryPath . ' -bs');
	}

	/**
	 * Returns the mail transport
	 *
	 * @return \Swift_MailTransport
	 */
	protected function getMailInstance() {
		return \Swift_MailTransport::newInstance();
	}

}
