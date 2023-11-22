<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2019, Roeland Jago Douma <roeland@famdouma.nl>
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
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
namespace OCA\Comments\Listener;

use OCA\Comments\AppInfo\Application;
use OCA\Files\Event\LoadSidebar;
use OCP\App\IAppManager;
use OCP\AppFramework\Services\IInitialState;
use OCP\Comments\ICommentsManager;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\Util;

class LoadSidebarScripts implements IEventListener {
	public function __construct(
		private ICommentsManager $commentsManager,
		private IInitialState $initialState,
		private IAppManager $appManager,
	) {
	}

	public function handle(Event $event): void {
		if (!($event instanceof LoadSidebar)) {
			return;
		}

		$this->commentsManager->load();

		$this->initialState->provideInitialState('activityEnabled', $this->appManager->isEnabledForUser('activity'));

		// TODO: make sure to only include the sidebar script when
		// we properly split it between files list and sidebar
		Util::addScript(Application::APP_ID, 'comments');
		Util::addScript(Application::APP_ID, 'comments-tab', 'files');
	}
}
