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
use OCP\Stratos\Model\IStratosItem;
use OCP\Stratos\Model\Helper\IStratosNotification;
use OC\Stratos\Model\StratosRecipients;


/**
 * Class StratosNotification
 *
 * @package OC\Stratos\Model\Helper
 */
class StratosNotification extends StratosRecipients implements IStratosNotification, JsonSerializable {


	use TArrayTools;


	/** @var string */
	private $app = '';

	/** @var string */
	private $title = '';

	/** @var string */
	private $message = '';

	/** @var string */
	private $level = '';

	/** @var string */
	private $link = '';

	/** @var int */
	private $ttl = -1;


	/**
	 * StratosNotification constructor.
	 *
	 * @param string $app
	 * @param int $ttl
	 */
	public function __construct($app = '', $ttl = IStratosItem::TTL_INSTANT) {
		$this->app = $app;
		$this->ttl = $ttl;
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
	 * @return IStratosNotification
	 */
	public function setApp(string $app): IStratosNotification {
		$this->app = $app;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getTitle(): string {
		return $this->title;
	}

	/**
	 * @param string $title
	 *
	 * @return IStratosNotification
	 */
	public function setTitle(string $title): IStratosNotification {
		$this->title = $title;

		return $this;
	}


	/**
	 * @return string
	 */
	public function getMessage(): string {
		return $this->message;
	}

	/**
	 * @param string $message
	 *
	 * @return IStratosNotification
	 */
	public function setMessage(string $message): IStratosNotification {
		$this->message = $message;

		return $this;
	}


	/**
	 * @return string
	 */
	public function getLevel(): string {
		return $this->level;
	}

	/**
	 * @param string $level
	 *
	 * @return IStratosNotification
	 */
	public function setLevel(string $level): IStratosNotification {
		$this->level = $level;

		return $this;
	}


	/**
	 * @return string
	 */
	public function getLink(): string {
		return $this->link;
	}

	/**
	 * @param string $link
	 *
	 * @return IStratosNotification
	 */
	public function setLink(string $link): IStratosNotification {
		$this->link = $link;

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
	 * @return IStratosNotification
	 */
	public function setTtl(int $ttl): IStratosNotification {
		$this->ttl = $ttl;

		return $this;
	}


	/**
	 * @return array
	 */
	public function jsonSerialize(): array {
		return array_merge(
			parent::jsonSerialize(),
			[
				'app'    => $this->getApp(),
				'title'  => $this->getTitle(),
				'type'   => $this->getMessage(),
				'source' => $this->getLevel(),
				'link'   => $this->getLink(),
				'ttl'    => $this->getTtl()
			]);
	}

}

