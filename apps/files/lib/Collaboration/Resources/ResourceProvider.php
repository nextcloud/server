<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Files\Collaboration\Resources;

use OCP\Collaboration\Resources\IProvider;
use OCP\Collaboration\Resources\IResource;
use OCP\Collaboration\Resources\ResourceException;
use OCP\Files\IRootFolder;
use OCP\Files\Node;
use OCP\IPreview;
use OCP\IURLGenerator;
use OCP\IUser;

class ResourceProvider implements IProvider {
	public const RESOURCE_TYPE = 'file';

	/** @var array */
	protected $nodes = [];

	public function __construct(
		protected IRootFolder $rootFolder,
		private IPreview $preview,
		private IURLGenerator $urlGenerator,
	) {
	}

	private function getNode(IResource $resource): ?Node {
		if (isset($this->nodes[(int)$resource->getId()])) {
			return $this->nodes[(int)$resource->getId()];
		}
		$node = $this->rootFolder->getFirstNodeById((int)$resource->getId());
		if ($node) {
			$this->nodes[(int)$resource->getId()] = $node;
			return $this->nodes[(int)$resource->getId()];
		}
		return null;
	}

	/**
	 * @param IResource $resource
	 * @return array
	 * @since 16.0.0
	 */
	public function getResourceRichObject(IResource $resource): array {
		if (isset($this->nodes[(int)$resource->getId()])) {
			$node = $this->nodes[(int)$resource->getId()]->getPath();
		} else {
			$node = $this->getNode($resource);
		}

		if ($node instanceof Node) {
			$link = $this->urlGenerator->linkToRouteAbsolute(
				'files.viewcontroller.showFile',
				['fileid' => $resource->getId()]
			);
			return [
				'type' => 'file',
				'id' => $resource->getId(),
				'name' => $node->getName(),
				'path' => $node->getInternalPath(),
				'link' => $link,
				'mimetype' => $node->getMimetype(),
				'preview-available' => $this->preview->isAvailable($node),
			];
		}

		throw new ResourceException('File not found');
	}

	/**
	 * Can a user/guest access the collection
	 *
	 * @param IResource $resource
	 * @param IUser $user
	 * @return bool
	 * @since 16.0.0
	 */
	public function canAccessResource(IResource $resource, ?IUser $user = null): bool {
		if (!$user instanceof IUser) {
			return false;
		}

		$userFolder = $this->rootFolder->getUserFolder($user->getUID());
		$node = $userFolder->getById((int)$resource->getId());

		if ($node) {
			$this->nodes[(int)$resource->getId()] = $node;
			return true;
		}

		return false;
	}

	/**
	 * Get the resource type of the provider
	 *
	 * @return string
	 * @since 16.0.0
	 */
	public function getType(): string {
		return self::RESOURCE_TYPE;
	}
}
