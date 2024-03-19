<?php

namespace OCA\Testing\Controller;

use InvalidArgumentException;
use OCP\Accounts\IAccountManager;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\OCSController;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserManager;

class MailVerificationTestController extends OCSController {
	public function __construct(
		$appName,
		IRequest $request,
		protected IAccountManager $accountManager,
		protected IUserManager $userManager,
	) {
		parent::__construct($appName, $request);
	}

	public function verify(string $userId, string $email): DataResponse {
		$user = $this->userManager->get($userId);
		$userAccount = $this->accountManager->getAccount($user);
		$emailProperty = $userAccount->getPropertyCollection(IAccountManager::COLLECTION_EMAIL)
			->getPropertyByValue($email);
		if ($emailProperty === null) {
			throw new InvalidArgumentException('Email not available in account.');
		}
		$emailProperty->setLocallyVerified(IAccountManager::VERIFIED);
		return new DataResponse();
	}
}
