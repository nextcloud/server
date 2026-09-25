<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Template;

use OCP\AppFramework\Http\Events\BeforeTemplateRenderedEvent;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\Util;

/**
 * Put the viewer on every page a file can be opened from, which is any page an
 * app can put a file link on rather than only the Files list. The script only
 * registers the handlers; the viewer itself is fetched when a file is opened.
 *
 * @template-implements IEventListener<BeforeTemplateRenderedEvent>
 */
class LoadViewerListener implements IEventListener {
	#[\Override]
	public function handle(Event $event): void {
		if (!($event instanceof BeforeTemplateRenderedEvent)) {
			return;
		}

		// The themed error page renders through here too, and nothing on it
		// opens a file
		if ($event->getResponse()->getRenderAs() === TemplateResponse::RENDER_AS_ERROR) {
			return;
		}

		Util::addInitScript('core', 'viewer-init');
	}
}
