<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\TwoFactorBackupCodes\Activity;

use InvalidArgumentException;
use OCP\Activity\IEvent;
use OCP\Activity\IManager;
use OCP\Activity\IProvider;
use OCP\IURLGenerator;
use OCP\L10N\IFactory as L10nFactory;

class Provider implements IProvider {

	/** @var L10nFactory */
	private $l10n;

	/** @var IURLGenerator */
	private $urlGenerator;

	/** @var IManager */
	private $activityManager;

	/**
	 * @param L10nFactory $l10n
	 * @param IURLGenerator $urlGenerator
	 * @param IManager $activityManager
	 */
	public function __construct(L10nFactory $l10n, IURLGenerator $urlGenerator, IManager $activityManager) {
		$this->urlGenerator = $urlGenerator;
		$this->activityManager = $activityManager;
		$this->l10n = $l10n;
	}

	public function parse($language, IEvent $event, ?IEvent $previousEvent = null): IEvent {
		if ($event->getApp() !== 'twofactor_backupcodes') {
			throw new InvalidArgumentException();
		}

		$l = $this->l10n->get('twofactor_backupcodes', $language);

		switch ($event->getSubject()) {
			case 'codes_generated':
				$event->setParsedSubject($l->t('You created two-factor backup codes for your account'));

				if ($this->activityManager->getRequirePNG()) {
					$event->setIcon($this->urlGenerator->getAbsoluteURL($this->urlGenerator->imagePath('core', 'actions/password.png')));
				} else {
					$event->setIcon($this->urlGenerator->getAbsoluteURL($this->urlGenerator->imagePath('core', 'actions/password.svg')));
				}
				break;
			default:
				throw new InvalidArgumentException();
		}
		return $event;
	}
}
