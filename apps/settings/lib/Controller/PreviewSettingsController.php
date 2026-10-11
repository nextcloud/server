<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Settings\Controller;

use OCA\Settings\ResponseDefinitions;
use OCA\Settings\Service\PreviewSettingsService;
use OCA\Settings\Settings\Admin\Previews;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\ApiRoute;
use OCP\AppFramework\Http\Attribute\AuthorizedAdminSetting;
use OCP\AppFramework\Http\Attribute\OpenAPI;
use OCP\AppFramework\Http\Attribute\PasswordConfirmationRequired;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\OCS\OCSBadRequestException;
use OCP\AppFramework\OCS\OCSForbiddenException;
use OCP\AppFramework\OCSController;
use OCP\IL10N;
use OCP\IRequest;

/**
 * @psalm-import-type SettingsPreviewSettings from ResponseDefinitions
 */
#[OpenAPI(scope: OpenAPI::SCOPE_ADMINISTRATION)]
class PreviewSettingsController extends OCSController {
	public function __construct(
		string $appName,
		IRequest $request,
		private readonly PreviewSettingsService $service,
		private readonly IL10N $l,
	) {
		parent::__construct($appName, $request);
	}

	/**
	 * Update the preview settings
	 *
	 * A `null` value, or the default value, removes the setting so the default applies.
	 *
	 * @param bool $enabled Whether previews are generated and served
	 * @param int|null $maxX Maximum preview width in pixels
	 * @param int|null $maxY Maximum preview height in pixels
	 * @param int|null $maxMemory Maximum memory in MB for a preview generated in PHP, -1 for no limit
	 * @param int|null $maxFilesizeImage Maximum size in MB of a source image, -1 for no limit
	 * @param int|null $jpegQuality JPEG quality, 1 to 100
	 * @param int|null $webpQuality WebP quality, 1 to 100
	 * @param int|null $concurrencyNew Number of previews generated at the same time
	 * @param int|null $concurrencyAll Number of preview requests handled at the same time
	 * @param int|null $expirationDays Days after which generated previews are deleted, 0 to keep them
	 * @return DataResponse<Http::STATUS_OK, SettingsPreviewSettings, array{}>
	 * @throws OCSBadRequestException A value is out of range
	 * @throws OCSForbiddenException The configuration is read-only
	 *
	 * 200: Settings updated
	 */
	#[AuthorizedAdminSetting(settings: Previews::class)]
	#[PasswordConfirmationRequired]
	#[ApiRoute(verb: 'PUT', url: '/api/admin/previews/settings')]
	public function updateSettings(
		bool $enabled,
		?int $maxX,
		?int $maxY,
		?int $maxMemory,
		?int $maxFilesizeImage,
		?int $jpegQuality,
		?int $webpQuality,
		?int $concurrencyNew,
		?int $concurrencyAll,
		?int $expirationDays,
	): DataResponse {
		$this->assertWritable();
		try {
			$this->service->setSettings($enabled, $maxX, $maxY, $maxMemory, $maxFilesizeImage, $jpegQuality, $webpQuality, $concurrencyNew, $concurrencyAll, $expirationDays);
		} catch (\InvalidArgumentException $e) {
			throw new OCSBadRequestException($e->getMessage(), $e);
		}
		return new DataResponse($this->service->getSettings());
	}

	/**
	 * Set the enabled preview providers
	 *
	 * @param list<string> $providers Provider classes in the order they are tried
	 * @return DataResponse<Http::STATUS_OK, SettingsPreviewSettings, array{}>
	 * @throws OCSBadRequestException A provider is unknown
	 * @throws OCSForbiddenException The configuration is read-only
	 *
	 * 200: Providers updated
	 */
	#[AuthorizedAdminSetting(settings: Previews::class)]
	#[PasswordConfirmationRequired]
	#[ApiRoute(verb: 'PUT', url: '/api/admin/previews/providers')]
	public function updateProviders(array $providers): DataResponse {
		$this->assertWritable();
		try {
			$this->service->setProviders(array_values(array_filter($providers, 'is_string')));
		} catch (\InvalidArgumentException $e) {
			throw new OCSBadRequestException($e->getMessage(), $e);
		}
		return new DataResponse($this->service->getSettings());
	}

	/**
	 * Reset the preview providers to the default list
	 *
	 * @return DataResponse<Http::STATUS_OK, SettingsPreviewSettings, array{}>
	 * @throws OCSForbiddenException The configuration is read-only
	 *
	 * 200: Providers reset
	 */
	#[AuthorizedAdminSetting(settings: Previews::class)]
	#[PasswordConfirmationRequired]
	#[ApiRoute(verb: 'DELETE', url: '/api/admin/previews/providers')]
	public function resetProviders(): DataResponse {
		$this->assertWritable();
		$this->service->resetProviders();
		return new DataResponse($this->service->getSettings());
	}

	/**
	 * @throws OCSForbiddenException
	 */
	private function assertWritable(): void {
		if ($this->service->isConfigReadOnly()) {
			throw new OCSForbiddenException($this->l->t('The configuration is read-only.'));
		}
	}
}
