<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files\Controller;

use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\ApiRoute;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\UserRateLimit;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\OCS\OCSNotFoundException;
use OCP\AppFramework\OCSController;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Files\Events\Node\NodeOpenedEvent;
use OCP\Files\IRootFolder;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IUser;

final class OpenedController extends OCSController {
	public function __construct(
		string $appName,
		IRequest $request,
		private readonly IRootFolder $rootFolder,
		private readonly IEventDispatcher $eventDispatcher,
		private readonly IL10N $l10n,
	) {
		parent::__construct($appName, $request);
	}

	/**
	 * Report that the current user opened a file or folder
	 *
	 * Clients call this when the user actually opens a node, not when they
	 * read its content (sync, previews, downloads). Apps can listen to it to
	 * know what the user works with.
	 *
	 * @param int $fileId ID of the opened file or folder
	 *
	 * @return DataResponse<Http::STATUS_OK, list<empty>, array{}>
	 * @throws OCSNotFoundException The file or folder was not found
	 *
	 * 200: Open reported
	 */
	#[NoAdminRequired]
	#[UserRateLimit(limit: 100, period: 60)]
	#[ApiRoute(verb: 'POST', url: '/api/v1/opened')]
	public function opened(IUser $user, int $fileId): DataResponse {
		$node = $this->rootFolder->getUserFolder($user->getUID())->getFirstNodeById($fileId);

		// Also a 404 if the node is not readable, to not leak its existence
		if ($node === null || !$node->isReadable()) {
			throw new OCSNotFoundException($this->l10n->t('The file cannot be found'));
		}

		$this->eventDispatcher->dispatchTyped(new NodeOpenedEvent($node, $user));
		return new DataResponse([]);
	}
}
