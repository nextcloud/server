<?php

/**
 * SPDX-FileCopyrightText: 2019-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Settings\Controller;

use OC\AppFramework\Http;
use OC\IntegrityCheck\Checker;
use OCA\Settings\Settings\Admin\Overview;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Attribute\AuthorizedAdminSetting;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\Attribute\OpenAPI;
use OCP\AppFramework\Http\DataDisplayResponse;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\RedirectResponse;
use OCP\IConfig;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IURLGenerator;
use OCP\SetupCheck\ISetupCheckManager;
use Psr\Log\LoggerInterface;

#[OpenAPI(scope: OpenAPI::SCOPE_IGNORE)]
class CheckSetupController extends Controller {
	/** @var Checker */
	private $checker;

	public function __construct(
		$AppName,
		IRequest $request,
		private IConfig $config,
		private IURLGenerator $urlGenerator,
		private IL10N $l10n,
		Checker $checker,
		private LoggerInterface $logger,
		private ISetupCheckManager $setupCheckManager,
	) {
		parent::__construct($AppName, $request);
		$this->checker = $checker;
	}

	/**
	 * @return DataResponse
	 */
	#[NoCSRFRequired]
	#[NoAdminRequired]
	public function setupCheckManager(): DataResponse {
		return new DataResponse($this->setupCheckManager->runAll());
	}

	/**
	 * @return RedirectResponse
	 */
	#[NoCSRFRequired]
	#[AuthorizedAdminSetting(settings: Overview::class)]
	public function rescanFailedIntegrityCheck(): RedirectResponse {
		$this->checker->runInstanceVerification();
		return new RedirectResponse(
			$this->urlGenerator->linkToRoute('settings.AdminSettings.index', ['section' => 'overview'])
		);
	}

	#[NoCSRFRequired]
	#[AuthorizedAdminSetting(settings: Overview::class)]
	public function getFailedIntegrityCheckFiles(): DataDisplayResponse {
		if (!$this->checker->isCodeCheckEnforced()) {
			return new DataDisplayResponse('Integrity checker has been disabled. Integrity cannot be verified.');
		}

		$completeResults = $this->checker->getResults();

		if ($completeResults === null) {
			return new DataDisplayResponse('Integrity checker has not been run. Integrity information not available.');
		}

		if (!empty($completeResults)) {
			$formattedTextResponse = 'Technical information
=====================
The following list covers which files have failed the integrity check. Please read
the previous linked documentation to learn more about the errors and how to fix
them.

Results
=======
';
			foreach ($completeResults as $context => $contextResult) {
				$formattedTextResponse .= "- $context\n";

				foreach ($contextResult as $category => $result) {
					$formattedTextResponse .= "\t- $category\n";
					if ($category !== 'EXCEPTION') {
						foreach ($result as $key => $results) {
							$formattedTextResponse .= "\t\t- $key\n";
						}
					} else {
						foreach ($result as $key => $results) {
							$formattedTextResponse .= "\t\t- $results\n";
						}
					}
				}
			}

			$formattedTextResponse .= '
Raw output
==========
';
			$formattedTextResponse .= print_r($completeResults, true);
		} else {
			$formattedTextResponse = 'No errors have been found.';
		}


		return new DataDisplayResponse(
			$formattedTextResponse,
			Http::STATUS_OK,
			[
				'Content-Type' => 'text/plain',
			]
		);
	}

	/**
	 * @return DataResponse
	 */
	#[AuthorizedAdminSetting(settings: Overview::class)]
	public function check() {
		return new DataResponse(
			[
				'generic' => $this->setupCheckManager->runAll(),
			]
		);
	}
}
