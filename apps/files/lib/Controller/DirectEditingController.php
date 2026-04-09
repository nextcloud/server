<?php

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Files\Controller;

use Exception;
use OCA\Files\Service\DirectEditingService;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\OCSController;
use OCP\DirectEditing\IManager;
use OCP\DirectEditing\RegisterDirectEditorEvent;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IRequest;
use OCP\IURLGenerator;
use Psr\Log\LoggerInterface;

class DirectEditingController extends OCSController {
	public function __construct(
		string $appName,
		IRequest $request,
		string $corsMethods,
		string $corsAllowedHeaders,
		int $corsMaxAge,
		private IEventDispatcher $eventDispatcher,
		private IURLGenerator $urlGenerator,
		private IManager $directEditingManager,
		private DirectEditingService $directEditingService,
		private LoggerInterface $logger,
	) {
		parent::__construct($appName, $request, $corsMethods, $corsAllowedHeaders, $corsMaxAge);
	}

	/**
	 * Get the direct editing capabilities
	 * @return DataResponse<Http::STATUS_OK, array{editors: array<string, array{id: string, name: string, mimetypes: list<string>, optionalMimetypes: list<string>, secure: bool}>, creators: array<string, array{id: string, editor: string, name: string, extension: string, templates: bool, mimetypes: list<string>}>}, array{}>
	 *
	 * 200: Direct editing capabilities returned
	 */
	#[NoAdminRequired]
	public function info(): DataResponse {
		$response = new DataResponse($this->directEditingService->getDirectEditingCapabilitites());
		$response->setETag($this->directEditingService->getDirectEditingETag());
		return $response;
	}

	/**
	 * Create a file for direct editing
	 *
	 * @param string $path Path of the file
	 * @param string $editorId ID of the editor
	 * @param string $creatorId ID of the creator
	 * @param ?string $templateId ID of the template
	 *
	 * @return DataResponse<Http::STATUS_OK, array{url: string}, array{}>|DataResponse<Http::STATUS_FORBIDDEN|Http::STATUS_INTERNAL_SERVER_ERROR, array{message: string}, array{}>
	 *
	 * 200: URL for direct editing returned
	 * 403: Opening file is not allowed
	 */
	#[NoAdminRequired]
	public function create(string $path, string $editorId, string $creatorId, ?string $templateId = null): DataResponse {
		if (!$this->directEditingManager->isEnabled()) {
			return new DataResponse(['message' => 'Direct editing is not enabled'], Http::STATUS_INTERNAL_SERVER_ERROR);
		}
		$this->eventDispatcher->dispatchTyped(new RegisterDirectEditorEvent($this->directEditingManager));

		try {
			$token = $this->directEditingManager->create($path, $editorId, $creatorId, $templateId);
			return new DataResponse([
				'url' => $this->urlGenerator->linkToRouteAbsolute('files.DirectEditingView.edit', ['token' => $token])
			]);
		} catch (Exception $e) {
			$this->logger->error(
				'Exception when creating a new file through direct editing',
				[
					'exception' => $e
				],
			);
			return new DataResponse(['message' => 'Failed to create file: ' . $e->getMessage()], Http::STATUS_FORBIDDEN);
		}
	}

	/**
	 * Open a file for direct editing
	 *
	 * @param string $path Path of the file
	 * @param ?string $editorId ID of the editor
	 * @param ?int $fileId ID of the file
	 *
	 * @return DataResponse<Http::STATUS_OK, array{url: string}, array{}>|DataResponse<Http::STATUS_FORBIDDEN|Http::STATUS_INTERNAL_SERVER_ERROR, array{message: string}, array{}>
	 *
	 * 200: URL for direct editing returned
	 * 403: Opening file is not allowed
	 */
	#[NoAdminRequired]
	public function open(string $path, ?string $editorId = null, ?int $fileId = null): DataResponse {
		if (!$this->directEditingManager->isEnabled()) {
			return new DataResponse(['message' => 'Direct editing is not enabled'], Http::STATUS_INTERNAL_SERVER_ERROR);
		}
		$this->eventDispatcher->dispatchTyped(new RegisterDirectEditorEvent($this->directEditingManager));

		try {
			$token = $this->directEditingManager->open($path, $editorId, $fileId);
			return new DataResponse([
				'url' => $this->urlGenerator->linkToRouteAbsolute('files.DirectEditingView.edit', ['token' => $token])
			]);
		} catch (Exception $e) {
			$this->logger->error(
				'Exception when opening a file through direct editing',
				[
					'exception' => $e
				],
			);
			return new DataResponse(['message' => 'Failed to open file: ' . $e->getMessage()], Http::STATUS_FORBIDDEN);
		}
	}



	/**
	 * Get the templates for direct editing
	 *
	 * @param string $editorId ID of the editor
	 * @param string $creatorId ID of the creator
	 *
	 * @return DataResponse<Http::STATUS_OK, array{templates: array<string, array{id: string, title: string, preview: ?string, extension: string, mimetype: string}>}, array{}>|DataResponse<Http::STATUS_INTERNAL_SERVER_ERROR, array{message: string}, array{}>
	 *
	 * 200: Templates returned
	 */
	#[NoAdminRequired]
	public function templates(string $editorId, string $creatorId): DataResponse {
		if (!$this->directEditingManager->isEnabled()) {
			return new DataResponse(['message' => 'Direct editing is not enabled'], Http::STATUS_INTERNAL_SERVER_ERROR);
		}
		$this->eventDispatcher->dispatchTyped(new RegisterDirectEditorEvent($this->directEditingManager));

		try {
			return new DataResponse($this->directEditingManager->getTemplates($editorId, $creatorId));
		} catch (Exception $e) {
			$this->logger->error(
				$e->getMessage(),
				[
					'exception' => $e
				],
			);
			return new DataResponse(['message' => 'Failed to obtain template list: ' . $e->getMessage()], Http::STATUS_INTERNAL_SERVER_ERROR);
		}
	}
}
