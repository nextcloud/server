<?php
declare(strict_types=1);


/**
 * Stratos - above your cloud
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


namespace OC\Stratos\Model\Helper;


use daita\NcSmallPhpTools\Traits\TArrayTools;
use JsonSerializable;
use OC\Stratos\Model\StratosRecipients;
use OCP\Stratos\Model\Helper\IStratosEvent;


/**
 * Class StratosEvent
 *
 * @package OC\Stratos\Model\Helper
 */
class StratosEvent extends StratosRecipients implements IStratosEvent, JsonSerializable {


	use TArrayTools;


	/** @var string */
	private $app = '';

	/** @var string */
	private $command = '';

	/** @var array */
	private $payload = [];


	/**
	 * StratosEvent constructor.
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
	 * @return IStratosEvent
	 */
	public function setApp(string $app): IStratosEvent {
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
	 * @return IStratosEvent
	 */
	public function setCommand(string $command): IStratosEvent {
		$this->command = $command;

		return $this;
	}


	/**
	 * @return array
	 */
	public function getPayload(): array {
		return $this->payload;
	}

	/**
	 * @param array $payload
	 *
	 * @return IStratosEvent
	 */
	public function setPayload(array $payload): IStratosEvent {
		$this->payload = $payload;

		return $this;
	}

	/**
	 * @param JsonSerializable $payload
	 *
	 * @return IStratosEvent
	 */
	public function setPayloadSerializable(JsonSerializable $payload): IStratosEvent {
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

