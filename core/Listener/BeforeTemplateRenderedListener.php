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
use OCP\IConfig;
use OCP\Util;

/** @template-implements IEventListener<BeforeLoginTemplateRenderedEvent|BeforeTemplateRenderedEvent> */
class BeforeTemplateRenderedListener implements IEventListener {
	public function __construct(private IConfig $config) {
	}

	public function handle(Event $event): void {
		if (!($event instanceof BeforeTemplateRenderedEvent || $event instanceof BeforeLoginTemplateRenderedEvent)) {
			return;
		}

		if ($event->getResponse()->getRenderAs() === TemplateResponse::RENDER_AS_USER) {
			// Making sure to inject just after core
			Util::addScript('core', 'unsupported-browser-redirect');
		}

		\OC_Util::addStyle('server', null, true);

		if ($event instanceof BeforeLoginTemplateRenderedEvent) {
			// todo: make login work without these
			Util::addScript('core', 'main');
			Util::addTranslations('core');
		}

		if ($event instanceof BeforeTemplateRenderedEvent) {
			// include common nextcloud webpack bundle
			Util::addScript('core', 'main');
			Util::addTranslations('core');

			if ($event->getResponse()->getRenderAs() !== TemplateResponse::RENDER_AS_ERROR) {
				Util::addScript('core', 'merged-template-prepend', 'core', true);
				Util::addScript('core', 'files_client', 'core', true);
				Util::addScript('core', 'files_fileinfo', 'core', true);


				// If installed and background job is set to ajax, add dedicated script
				if ($this->config->getAppValue('core', 'backgroundjobs_mode', 'ajax') == 'ajax') {
					Util::addScript('core', 'backgroundjobs');
				}
			}
		}
		// If not on login and on non user page or on settings, then add the legacy scrips.
		// This MUST be the last one so `prepand` inserts it as the very first script
		// TODO: Remove if we finally migrated from jQuery to Vue
		if (!($event instanceof BeforeLoginTemplateRenderedEvent) && ($event->getResponse()->getRenderAs() !== TemplateResponse::RENDER_AS_USER || $event->getResponse()->getApp() === 'settings')) {
			Util::addScript('core', 'legacy', 'core', true);
		}
	}
}
