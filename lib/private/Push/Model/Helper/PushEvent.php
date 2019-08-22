<?php
declare(strict_types=1);


/**
 * Push - Nextcloud Push Service
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Maxence Lange <maxence@artificial-owl.com>
 * @copyright 2019, Maxence Lange <maxence@artificial-owl.com>
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


namespace OC\Push\Model\Helper;


use daita\NcSmallPhpTools\Traits\TArrayTools;
use JsonSerializable;
use OC\Push\Model\PushRecipients;
use OCP\Push\Model\Helper\IPushEvent;


/**
 * Class PushEvent
 *
 * @package OC\Push\Model\Helper
 */
class PushEvent extends PushRecipients implements IPushEvent, JsonSerializable {


	use TArrayTools;


	/** @var string */
	private $app = '';

	/** @var string */
	private $command = '';

	/** @var array */
	private $payload = [];


	/**
	 * PushEvent constructor.
	 *
	 * @param string $app
	 * @param string $command
	 */
	public function __construct($app = '', $command = '') {
		$this->app = $app;
		$this->command = $command;
	}


	/**
	 * @return string
	 */
	public function getApp(): string {
		return $this->app;
	}

	/**
	 * @param string $app
	 *
	 * @return IPushEvent
	 */
	public function setApp(string $app): IPushEvent {
		$this->app = $app;

		return $this;
	}


	/**
	 * @return string
	 */
	public function getCommand(): string {
		return $this->command;
	}

	/**
	 * @param string $command
	 *
	 * @return IPushEvent
	 */
	public function setCommand(string $command): IPushEvent {
		$this->command = $command;

		return $this;
	}


	/**
	 * @return array
	 */
	public function getPayload(): array {
		if ($this->payload instanceof JsonSerializable) {
			return $this->payload->jsonSerialize();
		}

		return $this->payload;
	}

	/**
	 * @param array $payload
	 *
	 * @return IPushEvent
	 */
	public function setPayload(array $payload): IPushEvent {
		$this->payload = $payload;

		return $this;
	}

	/**
	 * @param JsonSerializable $payload
	 *
	 * @return IPushEvent
	 */
	public function setPayloadSerializable(JsonSerializable $payload): IPushEvent {
		$this->payload = $payload->jsonSerialize();

		return $this;
	}


	/**
	 * @return array
	 */
	public function jsonSerialize(): array {
		return array_merge(
			parent::jsonSerialize(),
			[
				'app'     => $this->getApp(),
				'command' => $this->getCommand(),
				'payload' => $this->getPayload()
			]
		);
	}

}

