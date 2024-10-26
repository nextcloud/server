<?php
/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Settings\Settings\Admin;

use OCP\AppFramework\Http\TemplateResponse;
use OCP\IBinaryFinder;
use OCP\IConfig;
use OCP\IL10N;
use OCP\Server;
use OCP\Settings\IDelegatedSettings;

class Mail implements IDelegatedSettings {
	/**
	 * @param IConfig $config
	 * @param IL10N $l
	 */
	public function __construct(
		private IConfig $config,
		private IL10N $l,
	) {
	}

	/**
	 * @return TemplateResponse
	 */
	public function getForm() {
		$parameters = [
			// Mail
			'sendmail_is_available' => (bool)Server::get(IBinaryFinder::class)->findBinaryPath('sendmail'),
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
	 *             the admin section. The forms are arranged in ascending order of the
	 *             priority values. It is required to return a value between 0 and 100.
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
