<?php

declare(strict_types=1);
/**
 * @copyright Copyright (c) 2023 Louis Chemineau <louis@chmn.me>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OC\Share20;

use Exception;
use OC\AppFramework\Bootstrap\Coordinator;
use OCA\Files_Sharing\DefaultPublicShareTemplateProvider;
use OCP\Server;
use OCP\Share\IPublicShareTemplateFactory;
use OCP\Share\IPublicShareTemplateProvider;
use OCP\Share\IShare;

class PublicShareTemplateFactory implements IPublicShareTemplateFactory {
	public function __construct(
		private Coordinator $coordinator,
		private DefaultPublicShareTemplateProvider $defaultProvider,
	) {
	}

	public function getProvider(IShare $share): IPublicShareTemplateProvider {
		$context = $this->coordinator->getRegistrationContext();
		if ($context === null) {
			throw new Exception("Can't retrieve public share template providers as context is not defined");
		}

		$providers = array_map(
			fn ($registration) => Server::get($registration->getService()),
			$context->getPublicShareTemplateProviders()
		);

		$filteredProviders = array_filter(
			$providers,
			fn (IPublicShareTemplateProvider $provider) => $provider->shouldRespond($share)
		);

		if (count($filteredProviders) === 0) {
			return $this->defaultProvider;
		} else {
			return array_shift($filteredProviders);
		}
	}
}
