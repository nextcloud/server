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


namespace OC\Stratos;


use daita\NcSmallPhpTools\Traits\TArrayTools;
use JsonSerializable;
use OCP\Stratos\Model\IStratosStream;


/**
 * Class StratosManager
 *
 * @package OC\Stratos
 */
class StratosStream implements IStratosStream, JsonSerializable {


	use TArrayTools;

	/** @var int */
	private $id = 0;

	/** @var string */
	private $type = '';

	/** @var string */
	private $ttl = '';

	/** @var string */
	private $app = '';

	/** @var string */
	private $source = '';

	/** @var string */
	private $recipient = '';

	/** @var int */
	private $creation = 0;


	/**
	 * StratosMessage constructor.
	 *
	 * @param string $app
	 * @param string $source
	 */
	public function __construct(string $app = '', string $source = '') {
		$this->app = $app;
		$this->source = $source;
	}


	/**
	 * @return int
	 */
	public function getId(): int {
		return $this->id;
	}

	/**
	 * @param int $id
	 *
	 * @return IStratosStream
	 */
	public function setId(int $id): IStratosStream {
		$this->id = $id;

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
	 * @return IStratosStream
	 */
	public function setType(string $type): IStratosStream {
		$this->type = $type;

		return $this;
	}


	/**
	 * @return string
	 */
	public function getTtl(): string {
		return $this->ttl;
	}

	/**
	 * @param string $ttl
	 *
	 * @return IStratosStream
	 */
	public function setTtl(string $ttl): IStratosStream {
		$this->ttl = $ttl;

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
	 * @return IStratosStream
	 */
	public function setApp(string $app): IStratosStream {
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
	 * @return IStratosStream
	 */
	public function setSource(string $source): IStratosStream {
		$this->source = $source;

		return $this;
	}


	/**
	 * @return string
	 */
	public function getRecipient(): string {
		return $this->recipient;
	}

	/**
	 * @param string $recipient
	 *
	 * @return IStratosStream
	 */
	public function setRecipient(string $recipient): IStratosStream {
		$this->recipient = $recipient;

		return $this;
	}


	/**
	 * @return int
	 */
	public function getCreation(): int {
		return $this->creation;
	}

	/**
	 * @param int $timestamp
	 *
	 * @return IStratosStream
	 */
	public function setCreation(int $timestamp): IStratosStream {
		$this->creation = $timestamp;

		return $this;
	}


	/**
	 * @param array $import
	 *
	 * @return IStratosStream
	 */
	public function import(array $import): IStratosStream {
		$this->setType($this->get('type', $import));
		$this->setApp($this->get('app', $import));
		$this->setTtl($this->get('ttl', $import));
		$this->setRecipient($this->get('recipient', $import));
		$this->setSource($this->get('source', $import));
		$this->setCreation($this->getInt('creation', $import));

		return $this;
	}


	/**
	 * @return array
	 */
	public function jsonSerialize(): array {
		return [
			'id'        => $this->getId(),
			'ttl'       => $this->getTtl(),
			'app'       => $this->getApp(),
			'source'    => $this->getSource(),
			'type'      => $this->getType(),
			'recipient' => $this->getRecipient(),
			'creation'  => $this->getCreation()
		];
	}

}

