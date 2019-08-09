<?php
declare(strict_types=1);
/**
 * @copyright Copyright (c) 2019 Arthur Schiwon <blizzz@arthur-schiwon.de>
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\WorkflowEngine\Entity;

use OCP\Files\IRootFolder;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\WorkflowEngine\IEntity;

class File implements IEntity {

	/** @var IL10N */
	protected $l10n;
	/** @var IURLGenerator */
	protected $urlGenerator;

	public function __construct(IL10N $l10n, IURLGenerator $urlGenerator) {
		$this->l10n = $l10n;
		$this->urlGenerator = $urlGenerator;
	}

	public function getId(): string {
		return 'WorkflowEngine_Entity_File';
	}

	public function getName(): string {
		return $this->l10n->t('File');
	}

	public function getIcon(): string {
		return $this->urlGenerator->imagePath('core', 'categories/files.svg');
	}

	public function getEvents(): array {
		$emitterClass = IRootFolder::class;
		$slot = '\OC\Files';
		return [
			new GenericEntityEmitterEvent($emitterClass, $slot, 'postCreate', $this->l10n->t('File created')),
			new GenericEntityEmitterEvent($emitterClass, $slot, 'postWrite', $this->l10n->t('File updated')),
			new GenericEntityEmitterEvent($emitterClass, $slot, 'postRename', $this->l10n->t('File renamed')),
			new GenericEntityEmitterEvent($emitterClass, $slot, 'postDelete', $this->l10n->t('File deleted')),
			new GenericEntityEmitterEvent($emitterClass, $slot, 'postTouch', $this->l10n->t('File accessed')),
			new GenericEntityEmitterEvent($emitterClass, $slot, 'postCopy', $this->l10n->t('File copied')),
		];
	}
}
