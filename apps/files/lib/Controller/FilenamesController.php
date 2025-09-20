<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Files\Controller;

use OCA\Files\BackgroundJob\SanitizeFilenames;
use OCA\Files\Service\SettingsService;
use OCP\AppFramework\Http\Attribute\OpenAPI;
use OCP\AppFramework\Http\Attribute\Route;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\OCS\OCSBadRequestException;
use OCP\AppFramework\OCSController;
use OCP\AppFramework\Services\IAppConfig;
use OCP\BackgroundJob\IJobList;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IUserManager;

class FilenamesController extends OCSController {

	public function __construct(
		string $appName,
		IRequest $request,
		private IL10N $l10n,
		private IJobList $jobList,
		private IAppConfig $appConfig,
		private IUserManager $userManager,
		private SettingsService $settingsService,
	) {
		parent::__construct($appName, $request);
	}

	/**
	 * Toggle the Windows filename support feature.
	 *
	 * @param bool $enabled - The new state of the Windows filename support
	 * @return DataResponse
	 */
	#[OpenAPI(scope: OpenAPI::SCOPE_IGNORE)]
	#[Route(type: Route::TYPE_API, verb: 'POST', url: '/api/v1/filenames/windows-compatibility')]
	public function toggleWindowFilenameSupport(bool $enabled): DataResponse {
		$this->settingsService->setFilesWindowsSupport($enabled);
		return new DataResponse(['enabled' => $enabled]);
	}

	/**
	 * Start a filename sanitization job
	 *
	 * @param null|int $limit Limit the number of users to be sanitized per run
	 * @param null|string $charReplacement Optionally specify a character to replace forbidden characters with
	 * @return DataResponse
	 * @throws OCSBadRequestException On invalid parameters or if a sanitization is already running
	 */
	#[OpenAPI(scope: OpenAPI::SCOPE_IGNORE)]
	#[Route(type: Route::TYPE_API, verb: 'POST', url: '/api/v1/filenames/sanitization')]
	public function sanitizeFilenames(?int $limit = 10, ?string $charReplacement = null): DataResponse {
		if ($limit < 1) {
			throw new OCSBadRequestException($this->l10n->t('Limit must be a positive integer.'));
		}
		if ($charReplacement !== null && ($charReplacement === '' || mb_strlen($charReplacement) > 1)) {
			throw new OCSBadRequestException($this->l10n->t('The replacement character may only be a single character.'));
		}

		if ($this->settingsService->isFilenameSanitizationRunning()) {
			throw new OCSBadRequestException($this->l10n->t('Filename sanitization already started.'));
		}

		$this->jobList->add(SanitizeFilenames::class, [
			'offset' => 0,
			'limit' => $limit,
			'charReplacement' => $charReplacement,
		]);

		return new DataResponse([]);
	}

	#[OpenAPI(scope: OpenAPI::SCOPE_IGNORE)]
	#[Route(type: Route::TYPE_API, verb: 'GET', url: '/api/v1/filenames/sanitization')]
	public function getStatus(): DataResponse {
		return new DataResponse($this->settingsService->getSanitizationStatus());
	}

	/**
	 * @return DataResponse
	 * @throws OCSBadRequestException If there is no filename sanitization in progress
	 */
	#[OpenAPI(scope: OpenAPI::SCOPE_IGNORE)]
	#[Route(type: Route::TYPE_API, verb: 'DELETE', url: '/api/v1/filenames/sanitization')]
	public function stopSanitization(): DataResponse {
		if (!$this->settingsService->isFilenameSanitizationRunning()) {
			throw new OCSBadRequestException($this->l10n->t('No filename sanitization in progress.'));
		}

		$this->jobList->remove(SanitizeFilenames::class);
		return new DataResponse([]);
	}
}
