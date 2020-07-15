<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2020 Julius Härtl <jus@bitgrid.net>
 *
 * @author Julius Härtl <jus@bitgrid.net>
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

namespace OCA\Files_Sharing\Event;

use OCP\EventDispatcher\Event;
use OCP\Share\IShare;

class BeforeTemplateRenderedEvent extends Event {
	public const SCOPE_PUBLIC_SHARE_AUTH = 'publicShareAuth';

	/** @var IShare */
	private $share;
	/** @var string|null */
	private $scope;

	public function __construct(IShare $share, ?string $scope = null) {
		parent::__construct();

		$this->share = $share;
		$this->scope = $scope;
	}

	public function getShare(): IShare {
		return $this->share;
	}

	public function getScope(): ?string {
		return $this->scope;
	}
}
