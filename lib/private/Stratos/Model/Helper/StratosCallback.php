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
use OCP\Stratos\Model\Helper\IStratosCallback;


/**
 * Class StratosCallback
 *
 * @package OC\Stratos\Model\Helper
 */
class StratosCallback extends StratosRecipients implements IStratosCallback, JsonSerializable {


	use TArrayTools;


	/** @var string */
	private $app = '';

	/** @var string */
	private $source = '';

	/** @var array */
	private $payload = '';


	/**
	 * StratosCallback constructor.
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
	 * @return IStratosCallback
	 */
	public function setApp(string $app): IStratosCallback {
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
	 * @return IStratosCallback
	 */
	public function setSource(string $source): IStratosCallback {
		$this->source = $source;

		return $this;
	}


	/**
	 * @return array
	 */
	public function getPayload(): array {
		if ($this->payload instanceof JsonSerializable) {
			return json_decode($this->payload, true);
		}

		return $this->payload;
	}

	/**
	 * @param array $payload
	 *
	 * @return IStratosCallback
	 */
	public function setPayload(array $payload): IStratosCallback {
		$this->payload = $payload;

		return $this;
	}

	/**
	 * @param JsonSerializable $payload
	 *
	 * @return self
	 */
	public function setPayloadSerializable(JsonSerializable $payload): IStratosCallback {
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
				'id'      => $this->getId(),
				'payload' => $this->payload
			]
		);
	}

}

