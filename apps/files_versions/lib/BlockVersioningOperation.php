<?php

declare(strict_types=1);

/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Files_Versions;

use OCA\Files_Versions\Events\CreateVersionEvent;
use OCA\WorkflowEngine\Entity\File as FileEntity;
use OCP\EventDispatcher\Event;
use OCP\Files\Folder;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\WorkflowEngine\IComplexOperation;
use OCP\WorkflowEngine\IManager;
use OCP\WorkflowEngine\IRuleMatcher;
use OCP\WorkflowEngine\ISpecificOperation;
use Psr\Log\LoggerInterface;

class BlockVersioningOperation implements ISpecificOperation, IComplexOperation {

	public function __construct(
		private readonly IL10N $l10n,
		private readonly FileEntity $fileEntity,
		private readonly LoggerInterface $logger,
		private readonly IURLGenerator $urlGenerator,
	) {
	}

	#[\Override]
	public function getEntityId(): string {
		return FileEntity::class;
	}

	#[\Override]
	public function getDisplayName(): string {
		return $this->l10n->t('Block versioning');
	}

	#[\Override]
	public function getDescription(): string {
		return $this->l10n->t('Automatic tag based blocking of files version creation.');
	}

	#[\Override]
	public function getIcon(): string {
		return $this->urlGenerator->imagePath('files_versions', 'app.svg');
	}

	#[\Override]
	public function isAvailableForScope(int $scope): bool {
		return $scope === IManager::SCOPE_ADMIN;
	}

	#[\Override]
	public function validateOperation(string $name, array $checks, string $operation): void {
		if (empty($checks)) {
			throw new \UnexpectedValueException($this->l10n->t('No rule given'));
		}
	}

	#[\Override]
	public function onEvent(string $eventName, Event $event, IRuleMatcher $ruleMatcher): void {
		if ($eventName !== CreateVersionEvent::class || !($event instanceof CreateVersionEvent)) {
			return;
		}

		$node = $event->getNode();
		$path = $node->getInternalPath();

		$ruleMatcher->setFileInfo(
			$node->getStorage(),
			$path,
			$node instanceof Folder,
		);
		$ruleMatcher->setEntitySubject($this->fileEntity, $node);
		$ruleMatcher->setOperation($this);
		$flows = $ruleMatcher->getFlows();

		if ($flows !== []) {
			$this->logger->debug('Blocking version creation due to matching workflow rules', [
				'path' => $path,
			]);
			$event->disableVersions();
		}
	}

	#[\Override]
	public function getTriggerHint(): string {
		return $this->l10n->t('A new version is created'); // TRANSLATORS: This will be shown as "When: " "A new version is created"
	}
}
