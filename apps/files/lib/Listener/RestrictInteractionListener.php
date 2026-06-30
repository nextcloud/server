<?php

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace OCA\Files\Listener;

use OCA\Files\AppInfo\Application;
use OCP\Constants;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\IL10N;
use OCP\Interaction\InteractionRestrictedException;
use OCP\Interaction\Resources\NodeResource;
use OCP\Interaction\RestrictInteractionEvent;
use OCP\L10N\IFactory;

/**
 * @template-implements IEventListener<RestrictInteractionEvent>
 */
final readonly class RestrictInteractionListener implements IEventListener {
	private IL10N $l10n;

	public function __construct(
		IFactory $l10nFactory,
	) {
		$this->l10n = $l10nFactory->get(Application::APP_ID);
	}

	/**
	 * @param RestrictInteractionEvent $event
	 */
	#[\Override]
	public function handle(Event $event): void {
		if ($event->resource instanceof NodeResource && ($event->resource->getNodePermissions() & Constants::PERMISSION_READ) !== Constants::PERMISSION_READ) {
			throw new InteractionRestrictedException('No read permission on the node.', $this->l10n->t('No read permission on file.'));
		}
	}
}
