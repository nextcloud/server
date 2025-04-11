<?php
/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Files\Service;

use OCP\DirectEditing\ACreateEmpty;
use OCP\DirectEditing\ACreateFromTemplate;
use OCP\DirectEditing\IEditor;
use OCP\DirectEditing\IManager;
use OCP\DirectEditing\RegisterDirectEditorEvent;
use OCP\EventDispatcher\IEventDispatcher;

class DirectEditingService {

	public function __construct(
		private IEventDispatcher $eventDispatcher,
		private IManager $directEditingManager,
	) {
	}

	public function getDirectEditingETag(): string {
		return \md5(\json_encode($this->getDirectEditingCapabilitites()));
	}

	public function getDirectEditingCapabilitites(): array {
		$this->eventDispatcher->dispatchTyped(new RegisterDirectEditorEvent($this->directEditingManager));

		$capabilities = [
			'editors' => [],
			'creators' => []
		];

		if (!$this->directEditingManager->isEnabled()) {
			return $capabilities;
		}

		/**
		 * @var string $id
		 * @var IEditor $editor
		 */
		foreach ($this->directEditingManager->getEditors() as $id => $editor) {
			$capabilities['editors'][$id] = [
				'id' => $editor->getId(),
				'name' => $editor->getName(),
				'mimetypes' => $editor->getMimetypes(),
				'optionalMimetypes' => $editor->getMimetypesOptional(),
				'secure' => $editor->isSecure(),
			];
			/** @var ACreateEmpty|ACreateFromTemplate $creator */
			foreach ($editor->getCreators() as $creator) {
				$id = $creator->getId();
				$capabilities['creators'][$id] = [
					'id' => $id,
					'editor' => $editor->getId(),
					'name' => $creator->getName(),
					'extension' => $creator->getExtension(),
					'templates' => $creator instanceof ACreateFromTemplate,
					'mimetype' => $creator->getMimetype()
				];
			}
		}
		return $capabilities;
	}
}
