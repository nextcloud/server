<?php
/**
 * @copyright 2022 Carl Schwan <carl@carlschwan.eu>
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
namespace OC\Repair;

use OCP\BackgroundJob\QueuedJob;
use OCP\AppFramework\Utility\ITimeFactory;
use OC\Avatar\AvatarManager;

class ClearGeneratedAvatarCacheJob extends QueuedJob {
	protected AvatarManager $avatarManager;

	public function __construct(ITimeFactory $timeFactory, AvatarManager $avatarManager) {
		parent::__construct($timeFactory);
		$this->avatarManager = $avatarManager;
	}

	public function run($argument) {
		$this->avatarManager->clearCachedAvatars();
	}
}
