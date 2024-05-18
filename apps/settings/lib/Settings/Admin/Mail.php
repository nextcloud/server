<?php
/**
 * @copyright Copyright (c) 2016 Arthur Schiwon <blizzz@arthur-schiwon.de>
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Julius Härtl <jus@bitgrid.net>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */
namespace OCA\Settings\Settings\Admin;

use OCP\AppFramework\Http\TemplateResponse;
use OCP\IConfig;
use OCP\IL10N;
use OCP\Settings\IDelegatedSettings;

class Mail implements IDelegatedSettings {
	/** @var IConfig */
	private $config;

	/** @var IL10N $l */
	private $l;

	/**
	 * @param IConfig $config
	 * @param IL10N $l
	 */
	public function __construct(IConfig $config, IL10N $l) {
		$this->config = $config;
		$this->l = $l;
	}

	/**
	 * @return TemplateResponse
	 */
	public function getForm() {
		$parameters = [
			// Mail
			'sendmail_is_available' => (bool) \OC_Helper::findBinaryPath('sendmail'),
			'mail_domain' => $this->config->getSystemValue('mail_domain', ''),
			'mail_from_address' => $this->config->getSystemValue('mail_from_address', ''),
			'mail_smtpmode' => $this->config->getSystemValue('mail_smtpmode', ''),
			'mail_smtpsecure' => $this->config->getSystemValue('mail_smtpsecure', ''),
			'mail_smtphost' => $this->config->getSystemValue('mail_smtphost', ''),
			'mail_smtpport' => $this->config->getSystemValue('mail_smtpport', ''),
			'mail_smtpauth' => $this->config->getSystemValue('mail_smtpauth', false),
			'mail_smtpname' => $this->config->getSystemValue('mail_smtpname', ''),
			'mail_smtppassword' => $this->config->getSystemValue('mail_smtppassword', ''),
			'mail_sendmailmode' => $this->config->getSystemValue('mail_sendmailmode', 'smtp'),
		];

		if ($parameters['mail_smtppassword'] !== '') {
			$parameters['mail_smtppassword'] = '********';
		}

		if ($parameters['mail_smtpmode'] === '' || $parameters['mail_smtpmode'] === 'php') {
			$parameters['mail_smtpmode'] = 'smtp';
		}

		return new TemplateResponse('settings', 'settings/admin/additional-mail', $parameters, '');
	}

	/**
	 * @return string the section ID, e.g. 'sharing'
	 */
	public function getSection() {
		return 'server';
	}

	/**
	 * @return int whether the form should be rather on the top or bottom of
	 * the admin section. The forms are arranged in ascending order of the
	 * priority values. It is required to return a value between 0 and 100.
	 *
	 * E.g.: 70
	 */
	public function getPriority() {
		return 10;
	}

	public function getName(): ?string {
		return $this->l->t('Email server');
	}

	public function getAuthorizedAppConfig(): array {
		return [];
	}
}
