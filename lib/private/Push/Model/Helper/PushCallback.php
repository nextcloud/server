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
use OCP\Push\Model\Helper\IPushCallback;


/**
 * Class PushCallback
 *
 * @package OC\Push\Model\Helper
 */
class PushCallback extends PushRecipients implements IPushCallback, JsonSerializable {


	use TArrayTools;


	/** @var string */
	private $app = '';

	/** @var string */
	private $source = '';

	/** @var array */
	private $payload = '';


	/**
	 * PushCallback constructor.
	 *
	 * @param string $app
	 * @param $source
	 */
	public function __construct($app = '', $source = '') {
		$this->app = $app;
		$this->source = $source;
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
	 * @return IPushCallback
	 */
	public function setApp(string $app): IPushCallback {
		$this->app = $app;

		return $this;
	}


	/**
	 * @return string
	 */
	public function getSource(): string {
		return $this->source;
	}

	/**
	 * @param string $source
	 *
	 * @return IPushCallback
	 */
	public function setSource(string $source): IPushCallback {
		$this->source = $source;

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
	 * @return IPushCallback
	 */
	public function setPayload(array $payload): IPushCallback {
		$this->payload = $payload;

		return $this;
	}

	/**
	 * @param JsonSerializable $payload
	 *
	 * @return self
	 */
	public function setPayloadSerializable(JsonSerializable $payload): IPushCallback {
		$this->payload = $payload;

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
				'source'  => $this->getSource(),
				'payload' => $this->payload
			]
		);
	}

}

