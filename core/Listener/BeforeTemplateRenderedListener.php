<?php

declare(strict_types=1);

/**
 * @copyright 2022 John Molakvoæ <skjnldsv@protonmail.com>
 *
 * @author John Molakvoæ <skjnldsv@protonmail.com>
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
namespace OC\Core\Listener;

use OCP\AppFramework\Http\Events\BeforeLoginTemplateRenderedEvent;
use OCP\AppFramework\Http\Events\BeforeTemplateRenderedEvent;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\IConfig;
use OCP\Util;

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

		if ($event->getResponse()->getRenderAs() === TemplateResponse::RENDER_AS_PUBLIC) {
			Util::addScript('core', 'public');
		}

		\OC_Util::addStyle('server', null, true);

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
				if ($this->config->getAppValue('core', 'backgroundjobs_mode', 'ajax') == 'ajax') {
					Util::addScript('core', 'backgroundjobs');
				}
			}
		}
	}
}
