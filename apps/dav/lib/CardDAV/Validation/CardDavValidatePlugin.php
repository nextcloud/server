<?php

declare(strict_types=1);

/*
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\CardDAV\Validation;

use OCA\DAV\AppInfo\Application;
use OCP\IAppConfig;
use OCP\Mail\IMailer;
use Sabre\DAV\Exception\BadRequest;
use Sabre\DAV\Exception\Forbidden;
use Sabre\DAV\Server;
use Sabre\DAV\ServerPlugin;
use Sabre\HTTP\RequestInterface;
use Sabre\HTTP\ResponseInterface;
use Sabre\VObject;

class CardDavValidatePlugin extends ServerPlugin {

	public function __construct(
		private IAppConfig $config,
	) {
	}

	public function initialize(Server $server): void {
		$server->on('beforeMethod:PUT', [$this, 'beforePut']);
		$server->on('beforeMethod:POST', [$this, 'beforePost']);
	}

	public function beforePut(RequestInterface $request, ResponseInterface $response): bool {
		// evaluate if card size exceeds defined limit
		$cardSizeLimit = $this->config->getValueInt(Application::APP_ID, 'card_size_limit', 5242880);
		if ((int)$request->getRawServerValue('CONTENT_LENGTH') > $cardSizeLimit) {
			throw new Forbidden("VCard object exceeds $cardSizeLimit bytes");
		}

		$this->validateEmail($request, $response);
		
		// all tests passed return true
		return true;
	}

	public function beforePost(RequestInterface $request, ResponseInterface $response): bool {
		$this->validateEmail($request, $response);
		return true;
	}

	public function validateEmail(RequestInterface $request, ResponseInterface $response) {
		// Get and Parse VCard
		$cardData = $request->getBodyAsString();
		if (!$cardData) {
			return true;
		}

		$vCard = VObject\Reader::read($cardData);

		// Get IMailer
		$mailer = \OC::$server->get(IMailer::class);

		// Loop through all emails, validate. If needed trim email
		foreach ($vCard->EMAIL as $email) {
			$trimedEmail = trim((string) $email);
			if ($trimedEmail !== '' && !$mailer->validateMailAddress($trimedEmail)) {
				throw new BadRequest('Invalid email in vCard');
			}
			if ($trimedEmail !== (string) $email) {
				$vCard->remove($email);
				$vCard->add('EMAIL', $trimedEmail, ['type' => $email['TYPE']]);
			}
		}

		// Pass parsed vcard
		$request->setBody($vCard->serialize());
	}
}
