<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Joas Schilling <coding@schilljs.com>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Morris Jobke <hey@morrisjobke.de>
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OC\Settings\Controller;

use OC\User\Session;
use \OCP\AppFramework\Controller;
use OCP\IRequest;
use OCP\IL10N;
use OCP\IConfig;
use OCP\Mail\IMailer;

/**
 * @package OC\Settings\Controller
 */
class MailSettingsController extends Controller {

	/** @var \OCP\IL10N */
	private $l10n;
	/** @var \OCP\IConfig */
	private $config;
	/** @var Session */
	private $userSession;
	/** @var \OC_Defaults */
	private $defaults;
	/** @var IMailer */
	private $mailer;
	/** @var string */
	private $defaultMailAddress;

	/**
	 * @param string $appName
	 * @param IRequest $request
	 * @param IL10N $l10n
	 * @param IConfig $config
	 * @param Session $userSession
	 * @param \OC_Defaults $defaults
	 * @param IMailer $mailer
	 * @param string $defaultMailAddress
	 */
	public function __construct($appName,
								IRequest $request,
								IL10N $l10n,
								IConfig $config,
								Session $userSession,
								\OC_Defaults $defaults,
								IMailer $mailer,
								$defaultMailAddress) {
		parent::__construct($appName, $request);
		$this->l10n = $l10n;
		$this->config = $config;
		$this->userSession = $userSession;
		$this->defaults = $defaults;
		$this->mailer = $mailer;
		$this->defaultMailAddress = $defaultMailAddress;
	}

	/**
	 * Sets the email settings
	 * @param string $mail_domain
	 * @param string $mail_from_address
	 * @param string $mail_smtpmode
	 * @param string $mail_smtpsecure
	 * @param string $mail_smtphost
	 * @param string $mail_smtpauthtype
	 * @param int $mail_smtpauth
	 * @param string $mail_smtpport
	 * @return array
	 */
	public function setMailSettings($mail_domain,
									$mail_from_address,
									$mail_smtpmode,
									$mail_smtpsecure,
									$mail_smtphost,
									$mail_smtpauthtype,
									$mail_smtpauth,
									$mail_smtpport) {

		$params = get_defined_vars();
		$configs = [];
		foreach($params as $key => $value) {
			$configs[$key] = (empty($value)) ? null : $value;
		}

		// Delete passwords from config in case no auth is specified
		if ($params['mail_smtpauth'] !== 1) {
			$configs['mail_smtpname'] = null;
			$configs['mail_smtppassword'] = null;
		}

		$this->config->setSystemValues($configs);

		return array('data' =>
			array('message' =>
				(string) $this->l10n->t('Saved')
			),
			'status' => 'success'
		);
	}

	/**
	 * Store the credentials used for SMTP in the config
	 * @param string $mail_smtpname
	 * @param string $mail_smtppassword
	 * @return array
	 */
	public function storeCredentials($mail_smtpname, $mail_smtppassword) {
		$this->config->setSystemValues([
			'mail_smtpname'		=> $mail_smtpname,
			'mail_smtppassword'	=> $mail_smtppassword,
		]);

		return array('data' =>
			array('message' =>
				(string) $this->l10n->t('Saved')
			),
			'status' => 'success'
		);
	}

	/**
	 * Send a mail to test the settings
	 * @return array
	 */
	public function sendTestMail() {
		$email = $this->config->getUserValue($this->userSession->getUser()->getUID(), $this->appName, 'email', '');
		if (!empty($email)) {
			try {
				$message = $this->mailer->createMessage();
				$message->setTo([$email => $this->userSession->getUser()->getDisplayName()]);
				$message->setFrom([$this->defaultMailAddress]);
				$message->setSubject($this->l10n->t('test email settings'));
				$message->setPlainBody('If you received this email, the settings seem to be correct.');
				$this->mailer->send($message);
			} catch (\Exception $e) {
				return [
					'data' => [
						'message' => (string) $this->l10n->t('A problem occurred while sending the email. Please revise your settings. (Error: %s)', [$e->getMessage()]),
					],
					'status' => 'error',
				];
			}

			return array('data' =>
				array('message' =>
					(string) $this->l10n->t('Email sent')
				),
				'status' => 'success'
			);
		}

		return array('data' =>
			array('message' =>
				(string) $this->l10n->t('You need to set your user email before being able to send test emails.'),
			),
			'status' => 'error'
		);
	}

}
