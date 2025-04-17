<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Core\Listener;

use OCP\AppFramework\Http\Events\BeforeLoginTemplateRenderedEvent;
use OCP\AppFramework\Http\Events\BeforeTemplateRenderedEvent;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\IAppConfig;
use OCP\Util;

/** @template-implements IEventListener<BeforeLoginTemplateRenderedEvent|BeforeTemplateRenderedEvent> */
class BeforeTemplateRenderedListener implements IEventListener {
	public function __construct(
		private IAppConfig $appConfig,
	) {
	}

	public function handle(Event $event): void {
		if (!($event instanceof BeforeTemplateRenderedEvent || $event instanceof BeforeLoginTemplateRenderedEvent)) {
			return;
		}

		if ($event->getResponse()->getRenderAs() === TemplateResponse::RENDER_AS_USER) {
			// Making sure to inject just after core
			Util::addScript('core', 'unsupported-browser-redirect');
		}

		if ($event->getResponse()->getRenderAs() === TemplateResponse::RENDER_AS_PUBLIC) {
			Util::addScript('core', 'public');
		}

		Util::addStyle('server', null, true);

		if ($event instanceof BeforeLoginTemplateRenderedEvent) {
			// todo: make login work without these
			Util::addScript('core', 'common');
			Util::addScript('core', 'main');
			Util::addTranslations('core');
		}

		if ($event instanceof BeforeTemplateRenderedEvent) {
			// include common nextcloud webpack bundle
			Util::addScript('core', 'common');
			Util::addScript('core', 'main');
			Util::addTranslations('core');

			if ($event->getResponse()->getRenderAs() !== TemplateResponse::RENDER_AS_ERROR) {
				Util::addScript('core', 'merged-template-prepend', 'core', true);
				Util::addScript('core', 'files_client', 'core', true);
				Util::addScript('core', 'files_fileinfo', 'core', true);


				// If installed and background job is set to ajax, add dedicated script
				if ($this->appConfig->getValueString('core', 'backgroundjobs_mode', 'ajax') === 'ajax') {
					Util::addScript('core', 'ajax-cron');
				}
			}
		}
	}
}
