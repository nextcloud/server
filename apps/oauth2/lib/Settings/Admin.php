<?php
/**
 * @copyright Copyright (c) 2017 Lukas Reschke <lukas@statuscode.ch>
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

namespace OCA\OAuth2\Settings;

use OCA\OAuth2\Db\ClientMapper;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\Settings\ISettings;

class Admin implements ISettings {
	/** @var ClientMapper */
	private $clientMapper;

	/**
	 * @param ClientMapper $clientMapper
	 */
	public function __construct(ClientMapper $clientMapper) {
		$this->clientMapper = $clientMapper;
	}

	/**
	 * @return TemplateResponse
	 */
	public function getForm() {
		return new TemplateResponse(
			'oauth2',
			'admin',
			[
				'clients' => $this->clientMapper->getClients(),
			],
			''
		);
	}

	/**
	 * {@inheritdoc}
	 */
	public function getSection() {
		return 'security';
	}

	/**
	 * {@inheritdoc}
	 */
	public function getPriority() {
		return 0;
	}
}
