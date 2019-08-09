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


namespace OC\Stratos\Model;


use daita\NcSmallPhpTools\Traits\TArrayTools;
use daita\NcSmallPhpTools\Traits\TStringTools;
use JsonSerializable;
use OCP\Stratos\Model\IStratosItem;


/**
 * Class StratosItem
 *
 * @package OC\Stratos\Model
 */
class StratosItem implements IStratosItem, JsonSerializable {


	use TArrayTools;
	use TStringTools;

	/** @var int */
	private $id = 0;

	/** @var string */
	private $token = '';

	/** @var string */
	private $app = '';

	/** @var string */
	private $source = '';

	/** @var array */
	private $payload = [];

	/** @var string */
	private $type = '';

	/** @var int */
	private $ttl = -1;

	/** @var int */
	private $creation = 0;


	/**
	 * StratosItem constructor.
	 *
	 * @param string $app
	 * @param string $type
	 */
	public function __construct($app = '', $type = '') {
		$this->app = $app;
		$this->type = $type;

		$this->token = $this->uuid(11);
	}


	/**
	 * @param int $id
	 *
	 * @return StratosItem
	 */
	public function setId(int $id): IStratosItem {
		$this->id = $id;

		return $this;
	}

	/**
	 * @return int
	 */
	public function getId(): int {
		return $this->id;
	}


	/**
	 * @return string
	 */
	public function getToken(): string {
		return $this->token;
	}

	/**
	 * @param string $token
	 *
	 * @return StratosItem
	 */
	public function setToken(string $token): IStratosItem {
		$this->token = $token;

		return $this;
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
	 * @return IStratosItem
	 */
	public function setApp(string $app): IStratosItem {
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
	 * @return IStratosItem
	 */
	public function setSource(string $source): IStratosItem {
		$this->source = $source;

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
	 * @return StratosItem
	 */
	public function setPayload(array $payload): IStratosItem {
		$this->payload = $payload;

		return $this;
	}


	/**
	 * @return string
	 */
	public function getType(): string {
		return $this->type;
	}

	/**
	 * @param string $type
	 *
	 * @return StratosItem
	 */
	public function setType(string $type): IStratosItem {
		$this->type = $type;

		return $this;
	}


	/**
	 * @return int
	 */
	public function getTtl(): int {
		return $this->ttl;
	}

	/**
	 * @param int $ttl
	 *
	 * @return StratosItem
	 */
	public function setTtl(int $ttl): IStratosItem {
		$this->ttl = $ttl;

		return $this;
	}


	/**
	 * @return int
	 */
	public function getCreation(): int {
		return $this->creation;
	}

	/**
	 * @param int $creation
	 *
	 * @return StratosItem
	 */
	public function setCreation(int $creation): IStratosItem {
		$this->creation = $creation;

		return $this;
	}


	/**
	 * @param array $import
	 *
	 * @return IStratosItem
	 */
	public function import(array $import): IStratosItem {
		$this->setId($this->getInt('id', $import, 0));
		$this->setToken($this->get('token', $import, ''));
		$this->setType($this->get('type', $import, ''));
		$this->setApp($this->get('app', $import, ''));
		$this->setTtl($this->getInt('ttl', $import, -1));
		$this->setPayload($this->getArray('payload', $import, []));
		$this->setSource($this->get('source', $import));
		$this->setCreation($this->getInt('creation', $import));

		return $this;
	}


	/**
	 * @return array
	 */
	public function jsonSerialize(): array {
		return [
			'token'    => $this->getToken(),
			'app'      => $this->getApp(),
			'type'     => $this->getType(),
			'source'   => $this->getSource(),
			'payload'  => $this->getPayload(),
			'ttl'      => $this->getTtl(),
			'creation' => $this->getCreation()
		];
	}

}

