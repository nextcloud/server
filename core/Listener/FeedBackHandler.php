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

/**
 * Handles repair feedback events and sends localized progress, info, warnings,
 * and error messages to the UI/event source during system repair operations.
 */
class FeedBackHandler {
	private int $totalSteps = 100;
	private int $completedSteps = 0;
	private string $currentStepName = '';

	public function __construct(
		private IEventSource $eventSource,
		private IL10N $l10n,
	) {
	}

	/**
	 * Handles feedback events and dispatches localized messages.
	 */
	public function handleRepairFeedback(Event $event): void {
		if ($event instanceof RepairStartEvent) {
			$this->onStart($event);
		} elseif ($event instanceof RepairAdvanceEvent) {
			$this->onAdvance($event);
		} elseif ($event instanceof RepairFinishEvent) {
			$this->onFinish($event);
		} elseif ($event instanceof RepairStepEvent) {
			$this->onStep($event);
		} elseif ($event instanceof RepairInfoEvent) {
			$this->onInfo($event);
		} elseif ($event instanceof RepairWarningEvent) {
			$this->onWarning($event);
		} elseif ($event instanceof RepairErrorEvent) {
			$this->onError($event);
		}
		// TODO: handle unknown event types
		// e.g., log or $this->eventSource->send('notice', $this->l10n->t('Unknown repair event type: %s', [get_class($event)]));

	}

	private function onStart(RepairStartEvent $event): void {
		$this->totalSteps = $event->getMaxStep();
		$this->completedSteps = 0;
		$this->currentStepName = $event->getCurrentStepName();
	}

	private function onAdvance(RepairAdvanceEvent $event): void {
		$this->completedSteps += $event->getIncrement();
		$description = trim($event->getDescription()) ?: $this->currentStepName;
		$message = $this->l10n->t(
			'[%d / %d]: %s',
			[$this->completedSteps, $this->totalSteps, $description]
		);
		$this->eventSource->send('success', $message);
	}

	private function onFinish(RepairFinishEvent $event): void {
		$message = $this->l10n->t(
			'[%d / %d]: %s',
			[$this->completedSteps, $this->totalSteps, $this->currentStepName]
		);
		$this->eventSource->send('success', $message);
	}

	private function onStep(RepairStepEvent $event): void {
		$stepName = trim($event->getStepName());
		$message = $this->l10n->t('Repair step:') . ' ' . $stepName;
		$this->eventSource->send('success', $message);
	}

	private function onInfo(RepairInfoEvent $event): void {
		$info = trim($event->getMessage());
		$message = $this->l10n->t('Repair info:') . ' ' . $info;
		$this->eventSource->send('success', $message);
	}

	private function onWarning(RepairWarningEvent $event): void {
		$warning = trim($event->getMessage());
		$message = $this->l10n->t('Repair warning:') . ' ' . $warning;
		$this->eventSource->send('notice', $message);
	}

	private function onError(RepairErrorEvent $event): void {
		$error = trim($event->getMessage());
		$message = $this->l10n->t('Repair error:') . ' ' . $error;
		$this->eventSource->send('error', $message);
	}
}
