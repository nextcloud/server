<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Settings\Controller;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\Attribute\OpenAPI;
use OCP\AppFramework\Http\ContentSecurityPolicy;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IAppConfig;
use OCP\IConfig;
use OCP\IGroupManager;
use OCP\IL10N;
use OCP\INavigationManager;
use OCP\IRequest;
use OCP\IURLGenerator;

#[OpenAPI(scope: OpenAPI::SCOPE_IGNORE)]
class HelpController extends Controller {

	public function __construct(
		string $appName,
		IRequest $request,
		private INavigationManager $navigationManager,
		private IURLGenerator $urlGenerator,
		/** @var string */
		private ?string $userId,
		private IGroupManager $groupManager,
		private IL10N $l10n,
		private IConfig $config,
		private IAppConfig $appConfig,
	) {
		parent::__construct($appName, $request);
	}

	/**
	 * @return TemplateResponse
	 *
	 * @NoSubAdminRequired
	 */
	#[NoCSRFRequired]
	#[NoAdminRequired]
	public function help(string $mode = 'user'): TemplateResponse {
		$this->navigationManager->setActiveEntry('help');
		$pageTitle = $this->l10n->t('Administrator documentation');
		if ($mode !== 'admin') {
			$pageTitle = $this->l10n->t('User documentation');
			$mode = 'user';
		}

		$documentationUrl = $this->urlGenerator->getAbsoluteURL(
			$this->urlGenerator->linkTo('', 'core/doc/' . $mode . '/index.html')
		);

		$urlUserDocs = $this->urlGenerator->linkToRoute('settings.Help.help', ['mode' => 'user']);
		$urlAdminDocs = $this->urlGenerator->linkToRoute('settings.Help.help', ['mode' => 'admin']);

		$knowledgebaseEmbedded = $this->config->getSystemValueBool('knowledgebase.embedded', false);
		if (!$knowledgebaseEmbedded) {
			$pageTitle = $this->l10n->t('Nextcloud help overview');
			$urlUserDocs = $this->urlGenerator->linkToDocs('user');
			$urlAdminDocs = $this->urlGenerator->linkToDocs('admin');
		}

		$legalNoticeUrl = $this->appConfig->getValueString('theming', 'imprintUrl');
		$privacyUrl = $this->appConfig->getValueString('theming', 'privacyUrl');

		$response = new TemplateResponse('settings', 'help', [
			'admin' => $this->groupManager->isAdmin($this->userId),
			'url' => $documentationUrl,
			'urlUserDocs' => $urlUserDocs,
			'urlAdminDocs' => $urlAdminDocs,
			'mode' => $mode,
			'pageTitle' => $pageTitle,
			'knowledgebaseEmbedded' => $knowledgebaseEmbedded,
			'legalNoticeUrl' => $legalNoticeUrl,
			'privacyUrl' => $privacyUrl,
		]);
		$policy = new ContentSecurityPolicy();
		$policy->addAllowedFrameDomain('\'self\'');
		$response->setContentSecurityPolicy($policy);
		return $response;
	}
}
