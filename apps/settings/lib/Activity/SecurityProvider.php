<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Settings\Activity;

use InvalidArgumentException;
use OCP\Activity\IEvent;
use OCP\Activity\IManager;
use OCP\Activity\IProvider;
use OCP\IURLGenerator;
use OCP\L10N\IFactory as L10nFactory;

class SecurityProvider implements IProvider {

	/** @var L10nFactory */
	private $l10n;

	/** @var IURLGenerator */
	private $urlGenerator;

	/** @var IManager */
	private $activityManager;

	public function __construct(L10nFactory $l10n, IURLGenerator $urlGenerator, IManager $activityManager) {
		$this->urlGenerator = $urlGenerator;
		$this->l10n = $l10n;
		$this->activityManager = $activityManager;
	}

	public function parse($language, IEvent $event, ?IEvent $previousEvent = null) {
		if ($event->getType() !== 'security') {
			throw new InvalidArgumentException();
		}

		$l = $this->l10n->get('settings', $language);

		switch ($event->getSubject()) {
			case 'twofactor_success':
				$params = $event->getSubjectParameters();
				$event->setParsedSubject($l->t('You successfully logged in using two-factor authentication (%1$s)', [
					$params['provider'],
				]));
				if ($this->activityManager->getRequirePNG()) {
					$event->setIcon($this->urlGenerator->getAbsoluteURL($this->urlGenerator->imagePath('core', 'actions/password.png')));
				} else {
					$event->setIcon($this->urlGenerator->getAbsoluteURL($this->urlGenerator->imagePath('core', 'actions/password.svg')));
				}
				break;
			case 'twofactor_failed':
				$params = $event->getSubjectParameters();
				$event->setParsedSubject($l->t('A login attempt using two-factor authentication failed (%1$s)', [
					$params['provider'],
				]));
				if ($this->activityManager->getRequirePNG()) {
					$event->setIcon($this->urlGenerator->getAbsoluteURL($this->urlGenerator->imagePath('core', 'actions/password.png')));
				} else {
					$event->setIcon($this->urlGenerator->getAbsoluteURL($this->urlGenerator->imagePath('core', 'actions/password.svg')));
				}
				break;
			case 'remote_wipe_start':
				$params = $event->getSubjectParameters();
				$event->setParsedSubject($l->t('Remote wipe was started on %1$s', [
					$params['name'],
				]));
				if ($this->activityManager->getRequirePNG()) {
					$event->setIcon($this->urlGenerator->getAbsoluteURL($this->urlGenerator->imagePath('core', 'actions/delete.png')));
				} else {
					$event->setIcon($this->urlGenerator->getAbsoluteURL($this->urlGenerator->imagePath('core', 'actions/delete.svg')));
				}
				break;
			case 'remote_wipe_finish':
				$params = $event->getSubjectParameters();
				$event->setParsedSubject($l->t('Remote wipe has finished on %1$s', [
					$params['name'],
				]));
				if ($this->activityManager->getRequirePNG()) {
					$event->setIcon($this->urlGenerator->getAbsoluteURL($this->urlGenerator->imagePath('core', 'actions/delete.png')));
				} else {
					$event->setIcon($this->urlGenerator->getAbsoluteURL($this->urlGenerator->imagePath('core', 'actions/delete.svg')));
				}
				break;
			default:
				throw new InvalidArgumentException();
		}
		return $event;
	}
}
