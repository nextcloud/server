<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2020, Maxence Lange <maxence@artificial-owl.com>
 *
 * @author Maxence Lange <maxence@artificial-owl.com>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
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
namespace OCP\Share\Events;

use OCP\EventDispatcher\Event;
use OCP\Share\IShare;

/**
 * @since 21.0.0
 */
class ShareDeletedEvent extends Event {
	/** @var IShare */
	private $share;

	/**
	 *
	 * @param IShare $share
	 * @param IShare[] $children
	 *
	 * @since 21.0.0
	 */
	public function __construct(IShare $share) {
		parent::__construct();

		$this->share = $share;
	}

	/**
	 * @return IShare
	 * @since 21.0.0
	 */
	public function getShare(): IShare {
		return $this->share;
	}
}
