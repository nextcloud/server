<?php
/**
 * @copyright Copyright (c) 2019 Julius Härtl <jus@bitgrid.net>
 *
 * @author Julius Härtl <jus@bitgrid.net>
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
namespace OCA\Files\Controller;

use Exception;
use OCA\Files\Service\DirectEditingService;
use OCP\AppFramework\Http;
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
		private LoggerInterface $logger
	) {
		parent::__construct($appName, $request, $corsMethods, $corsAllowedHeaders, $corsMaxAge);
	}

	/**
	 * @NoAdminRequired
	 *
	 * Get the direct editing capabilities
	 * @return DataResponse<Http::STATUS_OK, array{editors: array<string, array{id: string, name: string, mimetypes: string[], optionalMimetypes: string[], secure: bool}>, creators: array<string, array{id: string, editor: string, name: string, extension: string, templates: bool, mimetypes: string[]}>}, array{}>
	 *
	 * 200: Direct editing capabilities returned
	 */
	public function info(): DataResponse {
		$response = new DataResponse($this->directEditingService->getDirectEditingCapabilitites());
		$response->setETag($this->directEditingService->getDirectEditingETag());
		return $response;
	}

	/**
	 * @NoAdminRequired
	 *
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
	public function create(string $path, string $editorId, string $creatorId, string $templateId = null): DataResponse {
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
	 * @NoAdminRequired
	 *
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
	public function open(string $path, string $editorId = null, ?int $fileId = null): DataResponse {
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
	 * @NoAdminRequired
	 *
	 * Get the templates for direct editing
	 *
	 * @param string $editorId ID of the editor
	 * @param string $creatorId ID of the creator
	 *
	 * @return DataResponse<Http::STATUS_OK, array{templates: array<string, array{id: string, title: string, preview: ?string, extension: string, mimetype: string}>}, array{}>|DataResponse<Http::STATUS_INTERNAL_SERVER_ERROR, array{message: string}, array{}>
	 *
	 * 200: Templates returned
	 */
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
