<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Settings\Controller;

use Exception;
use OC\AppFramework\Middleware\Security\Exceptions\NotAdminException;
use OC\AppFramework\Middleware\Security\Exceptions\NotLoggedInException;
use OCA\Settings\ResponseDefinitions;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoSubAdminRequired;
use OCP\AppFramework\Http\Attribute\PasswordConfirmationRequired;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\OCS\OCSBadRequestException;
use OCP\AppFramework\OCSController;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IRequest;
use OCP\IUserSession;
use OCP\Log\Audit\CriticalActionPerformedEvent;
use OCP\Settings\IDeclarativeManager;
use Psr\Log\LoggerInterface;

/**
 * @psalm-import-type SettingsDeclarativeForm from ResponseDefinitions
 */
class DeclarativeSettingsController extends OCSController {
	public function __construct(
		string $appName,
		IRequest $request,
		private IUserSession $userSession,
		private IDeclarativeManager $declarativeManager,
		private LoggerInterface $logger,
		private IEventDispatcher $eventDispatcher,
	) {
		parent::__construct($appName, $request);
	}

	/**
	 * Sets a declarative settings value
	 *
	 * @param string $app ID of the app
	 * @param string $formId ID of the form
	 * @param string $fieldId ID of the field
	 * @param mixed $value Value to be saved
	 * @return DataResponse<Http::STATUS_OK, null, array{}>
	 * @throws NotLoggedInException Not logged in or not an admin user
	 * @throws NotAdminException Not logged in or not an admin user
	 * @throws OCSBadRequestException Invalid arguments to save value
	 *
	 * 200: Value set successfully
	 */
	#[NoAdminRequired]
	public function setValue(string $app, string $formId, string $fieldId, mixed $value): DataResponse {
		return $this->saveValue($app, $formId, $fieldId, $value);
	}

	/**
	 * Sets a declarative settings value.
	 * Password confirmation is required for sensitive values.
	 *
	 * @param string $app ID of the app
	 * @param string $formId ID of the form
	 * @param string $fieldId ID of the field
	 * @param mixed $value Value to be saved
	 * @return DataResponse<Http::STATUS_OK, null, array{}>
	 * @throws NotLoggedInException Not logged in or not an admin user
	 * @throws NotAdminException Not logged in or not an admin user
	 * @throws OCSBadRequestException Invalid arguments to save value
	 *
	 * 200: Value set successfully
	 */
	#[NoAdminRequired]
	#[PasswordConfirmationRequired]
	public function setSensitiveValue(string $app, string $formId, string $fieldId, mixed $value): DataResponse {
		return $this->saveValue($app, $formId, $fieldId, $value);
	}

	/**
	 * Sets a declarative settings value.
	 *
	 * @param string $app ID of the app
	 * @param string $formId ID of the form
	 * @param string $fieldId ID of the field
	 * @param mixed $value Value to be saved
	 * @return DataResponse<Http::STATUS_OK, null, array{}>
	 * @throws NotLoggedInException Not logged in or not an admin user
	 * @throws NotAdminException Not logged in or not an admin user
	 * @throws OCSBadRequestException Invalid arguments to save value
	 *
	 * 200: Value set successfully
	 */
	private function saveValue(string $app, string $formId, string $fieldId, mixed $value): DataResponse {
		$user = $this->userSession->getUser();
		if ($user === null) {
			throw new NotLoggedInException();
		}

		try {
			$this->declarativeManager->loadSchemas();
			$this->declarativeManager->setValue($user, $app, $formId, $fieldId, $value);
		} catch (NotAdminException $e) {
			throw $e;
		} catch (Exception $e) {
			$this->logger->error('Failed to set declarative settings value: ' . $e->getMessage());
			throw new OCSBadRequestException();
		}

		$this->eventDispatcher->dispatchTyped(new CriticalActionPerformedEvent(
			'Declarative setting of app "%s", form "%s", field "%s" was changed',
			['appId' => $app, 'formId' => $formId, 'fieldId' => $fieldId],
			false,
			'settings.declarative_value.updated',
		));
		return new DataResponse(null);
	}

	/**
	 * Gets all declarative forms with the values prefilled.
	 *
	 * @return DataResponse<Http::STATUS_OK, list<SettingsDeclarativeForm>, array{}>
	 * @throws NotLoggedInException
	 *
	 * 200: Forms returned
	 */
	#[NoSubAdminRequired]
	#[NoAdminRequired]
	public function getForms(): DataResponse {
		$user = $this->userSession->getUser();
		if ($user === null) {
			throw new NotLoggedInException();
		}
		$this->declarativeManager->loadSchemas();
		return new DataResponse($this->declarativeManager->getFormsWithValues($user, null, null));
	}
}
