<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2019 Julius Härtl <jus@bitgrid.net>
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Daniel Kesselberg <mail@danielkesselberg.de>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Julius Härtl <jus@bitgrid.net>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Kate Döen <kate.doeen@nextcloud.com>
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
namespace OCA\Settings\Controller;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Attribute\OpenAPI;
use OCP\AppFramework\Http\ContentSecurityPolicy;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IConfig;
use OCP\IGroupManager;
use OCP\IL10N;
use OCP\INavigationManager;
use OCP\IRequest;
use OCP\IURLGenerator;

#[OpenAPI(scope: OpenAPI::SCOPE_IGNORE)]
class HelpController extends Controller {

	/** @var INavigationManager */
	private $navigationManager;
	/** @var IURLGenerator */
	private $urlGenerator;
	/** @var IGroupManager */
	private $groupManager;
	/** @var IL10N */
	private $l10n;

	/** @var string */
	private $userId;

	/** @var IConfig */
	private $config;

	public function __construct(
		string $appName,
		IRequest $request,
		INavigationManager $navigationManager,
		IURLGenerator $urlGenerator,
		?string $userId,
		IGroupManager $groupManager,
		IL10N $l10n,
		IConfig $config,
	) {
		parent::__construct($appName, $request);
		$this->navigationManager = $navigationManager;
		$this->urlGenerator = $urlGenerator;
		$this->userId = $userId;
		$this->groupManager = $groupManager;
		$this->l10n = $l10n;
		$this->config = $config;
	}

	/**
	 * @return TemplateResponse
	 *
	 * @NoCSRFRequired
	 * @NoAdminRequired
	 * @NoSubAdminRequired
	 */
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

		$response = new TemplateResponse('settings', 'help', [
			'admin' => $this->groupManager->isAdmin($this->userId),
			'url' => $documentationUrl,
			'urlUserDocs' => $urlUserDocs,
			'urlAdminDocs' => $urlAdminDocs,
			'mode' => $mode,
			'pageTitle' => $pageTitle,
			'knowledgebaseEmbedded' => $knowledgebaseEmbedded,
		]);
		$policy = new ContentSecurityPolicy();
		$policy->addAllowedFrameDomain('\'self\'');
		$response->setContentSecurityPolicy($policy);
		return $response;
	}
}
