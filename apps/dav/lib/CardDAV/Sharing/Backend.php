<?php

declare(strict_types=1);
/**
 * @copyright 2024 Anna Larch <anna.larch@gmx.net>
 *
 * @author Anna Larch <anna.larch@gmx.net>
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU AFFERO GENERAL PUBLIC LICENSE for more details.
 *
 * You should have received a copy of the GNU Affero General Public
 * License along with this library.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace OCA\DAV\CardDAV\Sharing;

use OCA\DAV\Connector\Sabre\Principal;
use OCA\DAV\DAV\Sharing\Backend as SharingBackend;
use OCP\ICacheFactory;
use OCP\IGroupManager;
use OCP\IUserManager;
use Psr\Log\LoggerInterface;

class Backend extends SharingBackend {
	public function __construct(private IUserManager $userManager,
		private IGroupManager $groupManager,
		private Principal $principalBackend,
		private ICacheFactory $cacheFactory,
		private Service $service,
		private LoggerInterface $logger,
	) {
		parent::__construct($this->userManager, $this->groupManager, $this->principalBackend, $this->cacheFactory, $this->service, $this->logger);
	}
}
