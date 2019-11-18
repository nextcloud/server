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

use OCA\WorkflowEngine\AppInfo\Application;
use OCP\EventDispatcher\Event;
use OCP\Files\IRootFolder;
use OCP\IL10N;
use OCP\ILogger;
use OCP\IURLGenerator;
use OCP\SystemTag\MapperEvent;
use OCP\WorkflowEngine\GenericEntityEvent;
use OCP\WorkflowEngine\IEntity;
use OCP\WorkflowEngine\IRuleMatcher;
use Symfony\Component\EventDispatcher\GenericEvent;

class File implements IEntity {

	/** @var IL10N */
	protected $l10n;
	/** @var IURLGenerator */
	protected $urlGenerator;
	/** @var IRootFolder */
	protected $root;
	/** @var ILogger */
	protected $logger;

	public function __construct(IL10N $l10n, IURLGenerator $urlGenerator, IRootFolder $root, ILogger $logger) {
		$this->l10n = $l10n;
		$this->urlGenerator = $urlGenerator;
		$this->root = $root;
		$this->logger = $logger;
	}

	public function getName(): string {
		return $this->l10n->t('File');
	}

	public function getIcon(): string {
		return $this->urlGenerator->imagePath('core', 'categories/files.svg');
	}

	public function getEvents(): array {
		$namespace = '\OCP\Files::';
		return [
			new GenericEntityEvent($this->l10n->t('File created'), $namespace . 'postCreate'),
			new GenericEntityEvent($this->l10n->t('File updated'), $namespace . 'postWrite'),
			new GenericEntityEvent($this->l10n->t('File renamed'), $namespace . 'postRename'),
			new GenericEntityEvent($this->l10n->t('File deleted'), $namespace . 'postDelete'),
			new GenericEntityEvent($this->l10n->t('File accessed'), $namespace . 'postTouch'),
			new GenericEntityEvent($this->l10n->t('File copied'), $namespace . 'postCopy'),
			new GenericEntityEvent($this->l10n->t('Tag assigned'), MapperEvent::EVENT_ASSIGN),
		];
	}

	public function prepareRuleMatcher(IRuleMatcher $ruleMatcher, string $eventName, Event $event): void {
		if (!$event instanceof GenericEvent && !$event instanceof MapperEvent) {
			return;
		}
		switch ($eventName) {
			case 'postCreate':
			case 'postWrite':
			case 'postDelete':
			case 'postTouch':
				$ruleMatcher->setEntitySubject($this, $event->getSubject());
				break;
			case 'postRename':
			case 'postCopy':
				$ruleMatcher->setEntitySubject($this, $event->getSubject()[1]);
				break;
			case MapperEvent::EVENT_ASSIGN:
				if (!$event instanceof MapperEvent || $event->getObjectType() !== 'files') {
					break;
				}
				$nodes = $this->root->getById((int)$event->getObjectId());
				if (is_array($nodes) && !empty($nodes)) {
					$node = array_shift($nodes);
					$ruleMatcher->setEntitySubject($this, $node);
				}
				break;
		}
	}
}
