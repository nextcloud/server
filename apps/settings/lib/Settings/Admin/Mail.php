<?php
declare(strict_types=1);
/**
 * @copyright Copyright (c) 2016 Arthur Schiwon <blizzz@arthur-schiwon.de>
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Julius Härtl <jus@bitgrid.net>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Carl Schwan <carl@carlschwan.eu>
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
use OCP\AppFramework\Services\IInitialState;
use OCP\IConfig;
use OCP\IL10N;
use OCP\Settings\IDelegatedSettings;
use OCP\IURLGenerator;

class Mail implements IDelegatedSettings {
	private IConfig $config;
	private IL10N $l;
	private IInitialState $initialState;
	private IURLGenerator $urlGenerator;

	public function __construct(IConfig $config, IL10N $l, IInitialState $initialState, IURLGenerator $urlGenerator) {
		$this->config = $config;
		$this->l = $l;
		$this->initialState = $initialState;
		$this->urlGenerator = $urlGenerator;
	}

	public function getForm(): TemplateResponse {
		$parameters = [
			// Mail
			'sendmail_is_available' => (bool) \OC_Helper::findBinaryPath('sendmail'),
			'mail_domain' => $this->config->getSystemValue('mail_domain', ''),
			'mail_from_address' => $this->config->getSystemValue('mail_from_address', ''),
			'mail_smtpmode' => $this->config->getSystemValue('mail_smtpmode', ''),
			'mail_smtpsecure' => $this->config->getSystemValue('mail_smtpsecure', ''),
			'mail_smtphost' => $this->config->getSystemValue('mail_smtphost', ''),
			'mail_smtpport' => $this->config->getSystemValue('mail_smtpport', ''),
			'mail_smtpauthtype' => $this->config->getSystemValue('mail_smtpauthtype', ''),
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

		foreach ($parameters as $key => $parameter) {
			$this->initialState->provideInitialState($key, $parameter);
		}
		$this->initialState->provideInitialState('emailAdminDocUrl', 'https://jroiere.com'); //$this->urlGenerator->linkToDocs('admin-email'));

		return new TemplateResponse('settings', 'settings/admin/additional-mail', $parameters, '');
	}

	public function getSection(): string {
		return 'server';
	}

	public function getPriority(): int {
		return 10;
	}

	public function getName(): ?string {
		return $this->l->t('Email server');
	}

	public function getAuthorizedAppConfig(): array {
		return [];
	}
}
