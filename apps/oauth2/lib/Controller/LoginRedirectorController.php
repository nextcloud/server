<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\OAuth2\Controller;

use OCA\OAuth2\Db\ClientMapper;
use OCA\OAuth2\Exceptions\ClientNotFoundException;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\Attribute\PublicPage;
use OCP\AppFramework\Http\Attribute\UseSession;
use OCP\AppFramework\Http\RedirectResponse;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IL10N;
use OCP\IRequest;
use OCP\ISession;
use OCP\IURLGenerator;

class LoginRedirectorController extends Controller {
	/** @var IURLGenerator */
	private $urlGenerator;
	/** @var ClientMapper */
	private $clientMapper;
	/** @var ISession */
	private $session;
	/** @var IL10N */
	private $l;

	/**
	 * @param string $appName
	 * @param IRequest $request
	 * @param IURLGenerator $urlGenerator
	 * @param ClientMapper $clientMapper
	 * @param ISession $session
	 * @param IL10N $l
	 */
	public function __construct(string $appName,
		IRequest $request,
		IURLGenerator $urlGenerator,
		ClientMapper $clientMapper,
		ISession $session,
		IL10N $l) {
		parent::__construct($appName, $request);
		$this->urlGenerator = $urlGenerator;
		$this->clientMapper = $clientMapper;
		$this->session = $session;
		$this->l = $l;
	}

	/**
	 * Authorize the user
	 *
	 * @param string $client_id Client ID
	 * @param string $state State of the flow
	 * @param string $response_type Response type for the flow
	 * @return TemplateResponse<Http::STATUS_OK, array{}>|RedirectResponse<Http::STATUS_SEE_OTHER, array{}>
	 *
	 * 200: Client not found
	 * 303: Redirect to login URL
	 */
	#[PublicPage]
	#[NoCSRFRequired]
	#[UseSession]
	public function authorize($client_id,
		$state,
		$response_type): TemplateResponse|RedirectResponse {
		try {
			$client = $this->clientMapper->getByIdentifier($client_id);
		} catch (ClientNotFoundException $e) {
			$params = [
				'content' => $this->l->t('Your client is not authorized to connect. Please inform the administrator of your client.'),
			];
			return new TemplateResponse('core', '404', $params, 'guest');
		}

		if ($response_type !== 'code') {
			//Fail
			$url = $client->getRedirectUri() . '?error=unsupported_response_type&state=' . $state;
			return new RedirectResponse($url);
		}

		$this->session->set('oauth.state', $state);

		$targetUrl = $this->urlGenerator->linkToRouteAbsolute(
			'core.ClientFlowLogin.showAuthPickerPage',
			[
				'clientIdentifier' => $client->getClientIdentifier(),
			]
		);
		return new RedirectResponse($targetUrl);
	}
}
