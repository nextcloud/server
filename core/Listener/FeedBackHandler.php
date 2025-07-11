<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OC\Core\Listener;

use OC\Repair\Events\RepairAdvanceEvent;
use OC\Repair\Events\RepairErrorEvent;
use OC\Repair\Events\RepairFinishEvent;
use OC\Repair\Events\RepairInfoEvent;
use OC\Repair\Events\RepairStartEvent;
use OC\Repair\Events\RepairStepEvent;
use OC\Repair\Events\RepairWarningEvent;
use OCP\EventDispatcher\Event;
use OCP\IEventSource;
use OCP\IL10N;

class FeedBackHandler {
	private int $progressStateMax = 100;
	private int $progressStateStep = 0;
	private string $currentStep = '';

	public function __construct(
		private IEventSource $eventSource,
		private IL10N $l10n,
	) {
	}

	public function handleRepairFeedback(Event $event): void {
		if ($event instanceof RepairStartEvent) {
			$this->progressStateMax = $event->getMaxStep();
			$this->progressStateStep = 0;
			$this->currentStep = $event->getCurrentStepName();
		} elseif ($event instanceof RepairAdvanceEvent) {
			$this->progressStateStep += $event->getIncrement();
			$desc = $event->getDescription();
			if (empty($desc)) {
				$desc = $this->currentStep;
			}
			$this->eventSource->send('success', $this->l10n->t('[%d / %d]: %s', [$this->progressStateStep, $this->progressStateMax, $desc]));
		} elseif ($event instanceof RepairFinishEvent) {
			$this->progressStateMax = $this->progressStateStep;
			$this->eventSource->send('success', $this->l10n->t('[%d / %d]: %s', [$this->progressStateStep, $this->progressStateMax, $this->currentStep]));
		} elseif ($event instanceof RepairStepEvent) {
			$this->eventSource->send('success', $this->l10n->t('Repair step:') . ' ' . $event->getStepName());
		} elseif ($event instanceof RepairInfoEvent) {
			$this->eventSource->send('success', $this->l10n->t('Repair info:') . ' ' . $event->getMessage());
		} elseif ($event instanceof RepairWarningEvent) {
			$this->eventSource->send('notice', $this->l10n->t('Repair warning:') . ' ' . $event->getMessage());
		} elseif ($event instanceof RepairErrorEvent) {
			$this->eventSource->send('error', $this->l10n->t('Repair error:') . ' ' . $event->getMessage());
		}
	}
}
