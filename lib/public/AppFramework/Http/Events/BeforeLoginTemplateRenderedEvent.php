<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2020, Roeland Jago Douma <roeland@famdouma.nl>
 *
 * @author Julius Härtl <jus@bitgrid.net>
 * @author Morris Jobke <hey@morrisjobke.de>
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
namespace OCP\AppFramework\Http\Events;

use OCP\AppFramework\Http\TemplateResponse;
use OCP\EventDispatcher\Event;

/**
 * Emitted before the rendering step of the login TemplateResponse.
 *
 * @since 28.0.0
 */
class BeforeLoginTemplateRenderedEvent extends Event {
	/**
	 * @since 28.0.0
	 */
	public function __construct(private TemplateResponse $response) {
		parent::__construct();
	}

	/**
	 * @since 28.0.0
	 */
	public function getResponse(): TemplateResponse {
		return $this->response;
	}
}
