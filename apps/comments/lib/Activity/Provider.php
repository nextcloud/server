<?php
/**
 * @copyright Copyright (c) 2016 Joas Schilling <coding@schilljs.com>
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Morris Jobke <hey@morrisjobke.de>
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
namespace OCA\Comments\Activity;

use OCP\Activity\IEvent;
use OCP\Activity\IManager;
use OCP\Activity\IProvider;
use OCP\Comments\ICommentsManager;
use OCP\Comments\NotFoundException;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\IUserManager;
use OCP\L10N\IFactory;

class Provider implements IProvider {
	protected ?IL10N $l = null;

	public function __construct(
		protected IFactory $languageFactory,
		protected IURLGenerator $url,
		protected ICommentsManager $commentsManager,
		protected IUserManager $userManager,
		protected IManager $activityManager,
	) {
	}

	/**
	 * @param string $language
	 * @param IEvent $event
	 * @param IEvent|null $previousEvent
	 * @return IEvent
	 * @throws \InvalidArgumentException
	 * @since 11.0.0
	 */
	public function parse($language, IEvent $event, IEvent $previousEvent = null): IEvent {
		if ($event->getApp() !== 'comments') {
			throw new \InvalidArgumentException();
		}

		$this->l = $this->languageFactory->get('comments', $language);

		if ($event->getSubject() === 'add_comment_subject') {
			$this->parseMessage($event);
			if ($this->activityManager->getRequirePNG()) {
				$event->setIcon($this->url->getAbsoluteURL($this->url->imagePath('core', 'actions/comment.png')));
			} else {
				$event->setIcon($this->url->getAbsoluteURL($this->url->imagePath('core', 'actions/comment.svg')));
			}

			if ($this->activityManager->isFormattingFilteredObject()) {
				try {
					return $this->parseShortVersion($event);
				} catch (\InvalidArgumentException $e) {
					// Ignore and simply use the long version...
				}
			}

			return $this->parseLongVersion($event);
		} else {
			throw new \InvalidArgumentException();
		}
	}

	/**
	 * @throws \InvalidArgumentException
	 */
	protected function parseShortVersion(IEvent $event): IEvent {
		$subjectParameters = $this->getSubjectParameters($event);

		if ($event->getSubject() === 'add_comment_subject') {
			if ($subjectParameters['actor'] === $this->activityManager->getCurrentUserId()) {
				$event->setRichSubject($this->l->t('You commented'), []);
			} else {
				$author = $this->generateUserParameter($subjectParameters['actor']);
				$event->setRichSubject($this->l->t('{author} commented'), [
					'author' => $author,
				]);
			}
		} else {
			throw new \InvalidArgumentException();
		}

		return $event;
	}

	/**
	 * @throws \InvalidArgumentException
	 */
	protected function parseLongVersion(IEvent $event): IEvent {
		$subjectParameters = $this->getSubjectParameters($event);

		if ($event->getSubject() === 'add_comment_subject') {
			if ($subjectParameters['actor'] === $this->activityManager->getCurrentUserId()) {
				$event->setParsedSubject($this->l->t('You commented on %1$s', [
					$subjectParameters['filePath'],
				]))
					->setRichSubject($this->l->t('You commented on {file}'), [
						'file' => $this->generateFileParameter($subjectParameters['fileId'], $subjectParameters['filePath']),
					]);
			} else {
				$author = $this->generateUserParameter($subjectParameters['actor']);
				$event->setParsedSubject($this->l->t('%1$s commented on %2$s', [
					$author['name'],
					$subjectParameters['filePath'],
				]))
					->setRichSubject($this->l->t('{author} commented on {file}'), [
						'author' => $author,
						'file' => $this->generateFileParameter($subjectParameters['fileId'], $subjectParameters['filePath']),
					]);
			}
		} else {
			throw new \InvalidArgumentException();
		}

		return $event;
	}

	protected function getSubjectParameters(IEvent $event): array {
		$subjectParameters = $event->getSubjectParameters();
		if (isset($subjectParameters['fileId'])) {
			return $subjectParameters;
		}

		// Fix subjects from 12.0.3 and older
		//
		// Do NOT Remove unless necessary
		// Removing this will break parsing of activities that were created on
		// Nextcloud 12, so we should keep this as long as it's acceptable.
		// Otherwise if people upgrade over multiple releases in a short period,
		// they will get the dead entries in their stream.
		return [
			'actor' => $subjectParameters[0],
			'fileId' => $event->getObjectId(),
			'filePath' => trim($subjectParameters[1], '/'),
		];
	}

	protected function parseMessage(IEvent $event): void {
		$messageParameters = $event->getMessageParameters();
		if (empty($messageParameters)) {
			// Email
			return;
		}

		$commentId = $messageParameters['commentId'] ?? $messageParameters[0];

		try {
			$comment = $this->commentsManager->get((string) $commentId);
			$message = $comment->getMessage();

			$mentionCount = 1;
			$mentions = [];
			foreach ($comment->getMentions() as $mention) {
				if ($mention['type'] !== 'user') {
					continue;
				}

				$message = str_replace('@"' . $mention['id'] . '"', '{mention' . $mentionCount . '}', $message);
				if (!str_contains($mention['id'], ' ') && !str_starts_with($mention['id'], 'guest/')) {
					$message = str_replace('@' . $mention['id'], '{mention' . $mentionCount . '}', $message);
				}

				$mentions['mention' . $mentionCount] = $this->generateUserParameter($mention['id']);
				$mentionCount++;
			}

			$event->setParsedMessage($comment->getMessage())
				->setRichMessage($message, $mentions);
		} catch (NotFoundException $e) {
		}
	}

	protected function generateFileParameter(int $id, string $path): array {
		return [
			'type' => 'file',
			'id' => $id,
			'name' => basename($path),
			'path' => $path,
			'link' => $this->url->linkToRouteAbsolute('files.viewcontroller.showFile', ['fileid' => $id]),
		];
	}

	protected function generateUserParameter(string $uid): array {
		return [
			'type' => 'user',
			'id' => $uid,
			'name' => $this->userManager->getDisplayName($uid) ?? $uid,
		];
	}
}
