<?php

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Settings\Settings\Admin;

use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Services\IInitialState;
use OCP\IBinaryFinder;
use OCP\IConfig;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\Server;
use OCP\Settings\IDelegatedSettings;
use OCP\Util;

class Mail implements IDelegatedSettings {

	public function __construct(
		private IConfig $config,
		private IL10N $l,
		private IInitialState $initialState,
		private IURLGenerator $urlGenerator,
	) {
	}

	/**
	 * @return TemplateResponse
	 */
	public function getForm() {
		$finder = Server::get(IBinaryFinder::class);

		$smtpModeOptions = [
			['label' => 'SMTP', 'id' => 'smtp'],
		];
		if ($finder->findBinaryPath('sendmail') !== false) {
			$smtpModeOptions[] = ['label' => 'Sendmail', 'id' => 'sendmail'];
		}
		if ($finder->findBinaryPath('qmail') !== false) {
			$smtpModeOptions[] = ['label' => 'qmail', 'id' => 'qmail'];
		}

		$this->initialState->provideInitialState('settingsAdminMail', [
			'configIsReadonly' => $this->config->getSystemValueBool('config_is_read_only', false),
			'docUrl' => $this->urlGenerator->linkToDocs('admin-email'),

			'smtpModeOptions' => $smtpModeOptions,
			'smtpEncryptionOptions' => [
				['label' => $this->l->t('None / STARTTLS'), 'id' => ''],
				['label' => 'SSL/TLS', 'id' => 'ssl'],
			],
			'smtpSendmailModeOptions' => [
				['label' => 'smtp (-bs)', 'id' => 'smtp'],
				['label' => 'pipe (-t -i)', 'id' => 'pipe'],
			],
		]);

		$smtpPassword = $this->config->getSystemValue('mail_smtppassword', '');
		if ($smtpPassword !== '') {
			$smtpPassword = '********';
		}

		$smtpMode = $this->config->getSystemValue('mail_smtpmode', '');
		if ($smtpMode === '' || $smtpMode === 'php') {
			$smtpMode = 'smtp';
		}

		$smtpOptions = $this->config->getSystemValue('mail_smtpstreamoptions', []);
		$this->initialState->provideInitialState('settingsAdminMailConfig', [
			'mail_domain' => $this->config->getSystemValue('mail_domain', ''),
			'mail_from_address' => $this->config->getSystemValue('mail_from_address', ''),
			'mail_smtpmode' => $smtpMode,
			'mail_smtpsecure' => $this->config->getSystemValue('mail_smtpsecure', ''),
			'mail_smtphost' => $this->config->getSystemValue('mail_smtphost', ''),
			'mail_smtpport' => $this->config->getSystemValue('mail_smtpport', ''),
			'mail_smtpauth' => $this->config->getSystemValue('mail_smtpauth', false),
			'mail_smtpname' => $this->config->getSystemValue('mail_smtpname', ''),
			'mail_smtppassword' => $smtpPassword,
			'mail_sendmailmode' => $this->config->getSystemValue('mail_sendmailmode', 'smtp'),

			'mail_noverify' => $smtpOptions['ssl']['allow_self_signed'] ?? false,
		]);

		Util::addScript('settings', 'vue-settings-admin-mail');
		return new TemplateResponse('settings', 'settings/admin/additional-mail', renderAs: '');
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
