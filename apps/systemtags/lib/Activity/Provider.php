<?php
/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\SystemTags\Activity;

use OCP\Activity\Exceptions\UnknownActivityException;
use OCP\Activity\IEvent;
use OCP\Activity\IManager;
use OCP\Activity\IProvider;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\IUserManager;
use OCP\L10N\IFactory;

class Provider implements IProvider {
	public const CREATE_TAG = 'create_tag';
	public const UPDATE_TAG = 'update_tag';
	public const DELETE_TAG = 'delete_tag';

	public const ASSIGN_TAG = 'assign_tag';
	public const UNASSIGN_TAG = 'unassign_tag';

	/** @var IL10N */
	protected $l;

	/**
	 * @param IFactory $languageFactory
	 * @param IURLGenerator $url
	 * @param IManager $activityManager
	 * @param IUserManager $userManager
	 */
	public function __construct(
		protected IFactory $languageFactory,
		protected IURLGenerator $url,
		protected IManager $activityManager,
		protected IUserManager $userManager,
	) {
	}

	/**
	 * @param string $language
	 * @param IEvent $event
	 * @param IEvent|null $previousEvent
	 * @return IEvent
	 * @throws UnknownActivityException
	 * @since 11.0.0
	 */
	public function parse($language, IEvent $event, ?IEvent $previousEvent = null) {
		if ($event->getApp() !== 'systemtags') {
			throw new UnknownActivityException();
		}

		$this->l = $this->languageFactory->get('systemtags', $language);

		if ($this->activityManager->isFormattingFilteredObject()) {
			try {
				return $this->parseShortVersion($event);
			} catch (UnknownActivityException) {
				// Ignore and simply use the long version...
			}
		}

		return $this->parseLongVersion($event);
	}

	/**
	 * @param IEvent $event
	 * @return IEvent
	 * @throws UnknownActivityException
	 * @since 11.0.0
	 */
	public function parseShortVersion(IEvent $event): IEvent {
		$parsedParameters = $this->getParameters($event);

		if ($this->activityManager->getRequirePNG()) {
			$event->setIcon($this->url->getAbsoluteURL($this->url->imagePath('core', 'actions/tag.png')));
		} else {
			$event->setIcon($this->url->getAbsoluteURL($this->url->imagePath('core', 'actions/tag.svg')));
		}

		if ($event->getSubject() === self::ASSIGN_TAG) {
			if ($parsedParameters['actor']['id'] === '') {
				$event->setParsedSubject($this->l->t('System tag %1$s added by the system', [
					$this->generatePlainSystemTag($parsedParameters['systemtag']),
				]))
					->setRichSubject($this->l->t('Added system tag {systemtag}'), [
						'systemtag' => $parsedParameters['systemtag'],
					]);
			} elseif ($parsedParameters['actor']['id'] === $this->activityManager->getCurrentUserId()) {
				$event->setParsedSubject($this->l->t('Added system tag %1$s', [
					$this->generatePlainSystemTag($parsedParameters['systemtag']),
				]))
					->setRichSubject($this->l->t('Added system tag {systemtag}'), [
						'systemtag' => $parsedParameters['systemtag'],
					]);
			} else {
				$event->setParsedSubject($this->l->t('%1$s added system tag %2$s', [
					$parsedParameters['actor']['name'],
					$this->generatePlainSystemTag($parsedParameters['systemtag']),
				]))
					->setRichSubject($this->l->t('{actor} added system tag {systemtag}'), [
						'actor' => $parsedParameters['actor'],
						'systemtag' => $parsedParameters['systemtag'],
					]);
			}
		} elseif ($event->getSubject() === self::UNASSIGN_TAG) {
			if ($parsedParameters['actor']['id'] === '') {
				$event->setParsedSubject($this->l->t('System tag %1$s removed by the system', [
					$this->generatePlainSystemTag($parsedParameters['systemtag']),
				]))
					->setRichSubject($this->l->t('Removed system tag {systemtag}'), [
						'systemtag' => $parsedParameters['systemtag'],
					]);
			} elseif ($parsedParameters['actor']['id'] === $this->activityManager->getCurrentUserId()) {
				$event->setParsedSubject($this->l->t('Removed system tag %1$s', [
					$this->generatePlainSystemTag($parsedParameters['systemtag']),
				]))
					->setRichSubject($this->l->t('Removed system tag {systemtag}'), [
						'systemtag' => $parsedParameters['systemtag'],
					]);
			} else {
				$event->setParsedSubject($this->l->t('%1$s removed system tag %2$s', [
					$parsedParameters['actor']['name'],
					$this->generatePlainSystemTag($parsedParameters['systemtag']),
				]))
					->setRichSubject($this->l->t('{actor} removed system tag {systemtag}'), [
						'actor' => $parsedParameters['actor'],
						'systemtag' => $parsedParameters['systemtag'],
					]);
			}
		} else {
			throw new UnknownActivityException();
		}

		return $event;
	}

	/**
	 * @param IEvent $event
	 * @return IEvent
	 * @throws UnknownActivityException
	 * @since 11.0.0
	 */
	public function parseLongVersion(IEvent $event): IEvent {
		$parsedParameters = $this->getParameters($event);

		if ($this->activityManager->getRequirePNG()) {
			$event->setIcon($this->url->getAbsoluteURL($this->url->imagePath('core', 'actions/tag.png')));
		} else {
			$event->setIcon($this->url->getAbsoluteURL($this->url->imagePath('core', 'actions/tag.svg')));
		}

		if ($event->getSubject() === self::CREATE_TAG) {
			if ($parsedParameters['actor']['id'] === $this->activityManager->getCurrentUserId()) {
				$event->setParsedSubject($this->l->t('You created system tag %1$s', [
					$this->generatePlainSystemTag($parsedParameters['systemtag']),
				]))
					->setRichSubject($this->l->t('You created system tag {systemtag}'), $parsedParameters);
			} else {
				$event->setParsedSubject($this->l->t('%1$s created system tag %2$s', [
					$parsedParameters['actor']['name'],
					$this->generatePlainSystemTag($parsedParameters['systemtag']),
				]))
					->setRichSubject($this->l->t('{actor} created system tag {systemtag}'), $parsedParameters);
			}
		} elseif ($event->getSubject() === self::DELETE_TAG) {
			if ($parsedParameters['actor']['id'] === $this->activityManager->getCurrentUserId()) {
				$event->setParsedSubject($this->l->t('You deleted system tag %1$s', [
					$this->generatePlainSystemTag($parsedParameters['systemtag']),
				]))
					->setRichSubject($this->l->t('You deleted system tag {systemtag}'), $parsedParameters);
			} else {
				$event->setParsedSubject($this->l->t('%1$s deleted system tag %2$s', [
					$parsedParameters['actor']['name'],
					$this->generatePlainSystemTag($parsedParameters['systemtag']),
				]))
					->setRichSubject($this->l->t('{actor} deleted system tag {systemtag}'), $parsedParameters);
			}
		} elseif ($event->getSubject() === self::UPDATE_TAG) {
			if ($parsedParameters['actor']['id'] === $this->activityManager->getCurrentUserId()) {
				$event->setParsedSubject($this->l->t('You updated system tag %2$s to %1$s', [
					$this->generatePlainSystemTag($parsedParameters['newsystemtag']),
					$this->generatePlainSystemTag($parsedParameters['oldsystemtag']),
				]))
					->setRichSubject($this->l->t('You updated system tag {oldsystemtag} to {newsystemtag}'), $parsedParameters);
			} else {
				$event->setParsedSubject($this->l->t('%1$s updated system tag %3$s to %2$s', [
					$parsedParameters['actor']['name'],
					$this->generatePlainSystemTag($parsedParameters['newsystemtag']),
					$this->generatePlainSystemTag($parsedParameters['oldsystemtag']),
				]))
					->setRichSubject($this->l->t('{actor} updated system tag {oldsystemtag} to {newsystemtag}'), $parsedParameters);
			}
		} elseif ($event->getSubject() === self::ASSIGN_TAG) {
			if ($parsedParameters['actor']['id'] === '') {
				unset($parsedParameters['actor']);
				$event->setParsedSubject($this->l->t('System tag %2$s was added to %1$s by the system', [
					$parsedParameters['file']['path'],
					$this->generatePlainSystemTag($parsedParameters['systemtag']),
				]))
					->setRichSubject($this->l->t('System tag {systemtag} was added to {file} by the system'), $parsedParameters);
			} elseif ($parsedParameters['actor']['id'] === $this->activityManager->getCurrentUserId()) {
				$event->setParsedSubject($this->l->t('You added system tag %2$s to %1$s', [
					$parsedParameters['file']['path'],
					$this->generatePlainSystemTag($parsedParameters['systemtag']),
				]))
					->setRichSubject($this->l->t('You added system tag {systemtag} to {file}'), $parsedParameters);
			} else {
				$event->setParsedSubject($this->l->t('%1$s added system tag %3$s to %2$s', [
					$parsedParameters['actor']['name'],
					$parsedParameters['file']['path'],
					$this->generatePlainSystemTag($parsedParameters['systemtag']),
				]))
					->setRichSubject($this->l->t('{actor} added system tag {systemtag} to {file}'), $parsedParameters);
			}
		} elseif ($event->getSubject() === self::UNASSIGN_TAG) {
			if ($parsedParameters['actor']['id'] === '') {
				unset($parsedParameters['actor']);
				$event->setParsedSubject($this->l->t('System tag %2$s was removed from %1$s by the system', [
					$parsedParameters['file']['path'],
					$this->generatePlainSystemTag($parsedParameters['systemtag']),
				]))
					->setRichSubject($this->l->t('System tag {systemtag} was removed from {file} by the system'), $parsedParameters);
			} elseif ($parsedParameters['actor']['id'] === $this->activityManager->getCurrentUserId()) {
				$event->setParsedSubject($this->l->t('You removed system tag %2$s from %1$s', [
					$parsedParameters['file']['path'],
					$this->generatePlainSystemTag($parsedParameters['systemtag']),
				]))
					->setRichSubject($this->l->t('You removed system tag {systemtag} from {file}'), $parsedParameters);
			} else {
				$event->setParsedSubject($this->l->t('%1$s removed system tag %3$s from %2$s', [
					$parsedParameters['actor']['name'],
					$parsedParameters['file']['path'],
					$this->generatePlainSystemTag($parsedParameters['systemtag']),
				]))
					->setRichSubject($this->l->t('{actor} removed system tag {systemtag} from {file}'), $parsedParameters);
			}
		} else {
			throw new UnknownActivityException();
		}

		return $event;
	}

	protected function getParameters(IEvent $event) {
		$subject = $event->getSubject();
		$parameters = $event->getSubjectParameters();

		switch ($subject) {
			case self::CREATE_TAG:
			case self::DELETE_TAG:
				return [
					'actor' => $this->getUserParameter((string)$parameters[0]),
					'systemtag' => $this->getSystemTagParameter($parameters[1]),
				];
			case self::UPDATE_TAG:
				return [
					'actor' => $this->getUserParameter((string)$parameters[0]),
					'newsystemtag' => $this->getSystemTagParameter($parameters[1]),
					'oldsystemtag' => $this->getSystemTagParameter($parameters[2]),
				];
			case self::ASSIGN_TAG:
			case self::UNASSIGN_TAG:
				return [
					'actor' => $this->getUserParameter((string)$parameters[0]),
					'file' => $this->getFileParameter($event->getObjectId(), $parameters[1]),
					'systemtag' => $this->getSystemTagParameter($parameters[2]),
				];
		}
		return [];
	}

	protected function getFileParameter($id, $path) {
		return [
			'type' => 'file',
			'id' => (string)$id,
			'name' => basename($path),
			'path' => trim($path, '/'),
		];
	}

	protected function getSystemTagParameter($parameter) {
		$tagData = json_decode($parameter, true);
		if ($tagData === null) {
			[$name, $status] = explode('|||', substr($parameter, 3, -3));
			$tagData = [
				'id' => '0', // No way to recover the ID
				'name' => $name,
				'assignable' => $status === 'assignable',
				'visible' => $status !== 'invisible',
			];
		}

		return [
			'type' => 'systemtag',
			'id' => (string)$tagData['id'],
			'name' => $tagData['name'],
			'assignable' => $tagData['assignable'] ? '1' : '0',
			'visibility' => $tagData['visible'] ? '1' : '0',
		];
	}

	protected function getUserParameter(string $uid): array {
		return [
			'type' => 'user',
			'id' => $uid,
			'name' => $this->userManager->getDisplayName($uid) ?? $uid,
		];
	}

	protected function generatePlainSystemTag(array $parameter) {
		if ($parameter['assignable'] === '1') {
			return $parameter['name'];
		} elseif ($parameter['visibility'] === '1') {
			return $this->l->t('%s (restricted)', $parameter['name']);
		} else {
			return $this->l->t('%s (invisible)', $parameter['name']);
		}
	}
}
