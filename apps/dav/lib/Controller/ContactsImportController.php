<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\Controller;

use InvalidArgumentException;
/**
 * @psalm-type ContactsImportResult = array{items: list<string>, total: non-negative-int}
 */
use OCA\DAV\AppInfo\Application;
use OCA\DAV\CardDAV\Import\ImportService;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\ApiRoute;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\UserRateLimit;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\StreamGeneratorResponse;
use OCP\AppFramework\OCSController;
use OCP\Contacts\ContactsImportOptions;
use OCP\Contacts\IManager;
use OCP\IGroupManager;
use OCP\IRequest;
use OCP\ITempManager;
use OCP\IUserManager;
use OCP\IUserSession;
use OCP\IAddressBookWritable;

class ContactsImportController extends OCSController {

	public function __construct(
		IRequest $request,
		private IUserSession $userSession,
		private IUserManager $userManager,
		private IGroupManager $groupManager,
		private ITempManager $tempManager,
		private IManager $contactsManager,
		private ImportService $importService,
	) {
		parent::__construct(Application::APP_ID, $request);
	}

	/**
	 * Import contacts data
	 *
	 * @param string $transaction client generated transaction id
	 * @param string $target address book id
	 * @param array{format?:string, validation?:0|1|2, errors?:0|1, supersede?:bool} $options configuration options
	 * @param string $data contacts data
	 * @param string|null $user system user id
	 *
	 * @return StreamGeneratorResponse<Http::STATUS_OK, array{Content-Type:'application/x-ndjson'}> | DataResponse<Http::STATUS_BAD_REQUEST|Http::STATUS_UNAUTHORIZED, array{error?: non-empty-string}, array{}>
	 *
	 * 200: NDJSON stream of import event objects
	 * 400: invalid parameters
	 * 401: user not authorized
	 */
	#[ApiRoute(verb: 'POST', url: '/import', root: '/contacts')]
	#[UserRateLimit(limit: 10, period: 3600)]
	#[NoAdminRequired]
	public function import(string $transaction, string $target, array $options, string $data, ?string $user = null): DataResponse|StreamGeneratorResponse {
		$addressBookId = $target;
		$format = isset($options['format']) ? $options['format'] : null;
		$validation = isset($options['validation']) ? (int)$options['validation'] : null;
		$errors = isset($options['errors']) ? (int)$options['errors'] : null;
		$supersede = $options['supersede'] ?? false;
		// evaluate if user is logged in and has permissions
		if (!$this->userSession->isLoggedIn()) {
			return new DataResponse([], Http::STATUS_UNAUTHORIZED);
		}
		if ($user !== null) {
			if ($this->userSession->getUser()->getUID() !== $user
				&& $this->groupManager->isAdmin($this->userSession->getUser()->getUID()) === false) {
				return new DataResponse([], Http::STATUS_UNAUTHORIZED);
			}
			if (!$this->userManager->userExists($user)) {
				return new DataResponse(['error' => 'user not found'], Http::STATUS_BAD_REQUEST);
			}
			$userId = $user;
		} else {
			$userId = $this->userSession->getUser()->getUID();
		}
		// retrieve address book and evaluate if import is supported and writeable
		$addressBooks = $this->contactsManager->getAddressBooksForPrincipal('principals/users/' . $userId, [$addressBookId]);
		if ($addressBooks === []) {
			return new DataResponse(['error' => "Address book <$addressBookId> not found"], Http::STATUS_BAD_REQUEST);
		}
		$addressBook = $addressBooks[0];
		if (!$addressBook instanceof IAddressBookWritable) {
			return new DataResponse(['error' => "Address book <$addressBookId> does not support this function"], Http::STATUS_BAD_REQUEST);
		}
		if (!$addressBook->isWritable()) {
			return new DataResponse(['error' => "Address book <$addressBookId> is not writeable"], Http::STATUS_BAD_REQUEST);
		}
		// construct options object
		$options = new ContactsImportOptions();
		$options->setSupersede($supersede);
		if ($errors !== null) {
			try {
				$options->setErrors($errors);
			} catch (InvalidArgumentException) {
				return new DataResponse(['error' => 'Invalid errors option specified'], Http::STATUS_BAD_REQUEST);
			}
		}
		if ($validation !== null) {
			try {
				$options->setValidate($validation);
			} catch (InvalidArgumentException) {
				return new DataResponse(['error' => 'Invalid validation option specified'], Http::STATUS_BAD_REQUEST);
			}
		}
		try {
			$options->setFormat($format ?? 'ical');
		} catch (InvalidArgumentException) {
			return new DataResponse(['error' => 'Invalid format option specified'], Http::STATUS_BAD_REQUEST);
		}
		$options->setCounts(true);
		// process the data
		$tempPath = $this->tempManager->getTemporaryFile();
		$tempFile = fopen($tempPath, 'w+');
		fwrite($tempFile, $data);
		unset($data);
		fseek($tempFile, 0);

		$importGenerator = $this->importService->import($tempFile, $addressBook, $options);
		$stream = (function () use ($importGenerator, $tempFile, $transaction): \Generator {
			yield json_encode(['type' => 'control', 'transaction' => $transaction, 'disposition' => 'start']) . "\n";
			try {
				foreach ($importGenerator as $result) {
					$data = $result->jsonSerialize();
					$data['transaction'] = $transaction;
					yield json_encode($data) . PHP_EOL;
				}
			} finally {
				yield json_encode(['type' => 'control', 'transaction' => $transaction, 'disposition' => 'end']) . "\n";
				fclose($tempFile);
			}
		})();

		return new StreamGeneratorResponse($stream, 'application/x-ndjson');
	}
}
