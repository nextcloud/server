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


use JsonSerializable;
use OCP\Stratos\Model\IStratosMessage;


/**
 * Class StratosManager
 *
 * @package OC\Stratos
 */
class StratosMessage extends StratosStream implements IStratosMessage, JsonSerializable {


	const TYPE = 'Message';


	/** @var int */
	private $messageType = 0;

	/** @var string */
	private $title = '';

	/** @var string */
	private $content = '';

	/** @var string */
	private $detail = '';

	/** @var string */
	private $link = '';


	/**
	 * StratosMessage constructor.
	 *
	 * @param string $app
	 * @param string $source
	 */
	public function __construct(string $app = '', string $source = '') {
		parent::__construct($app, $source);

		$this->setType(StratosMessage::TYPE);
	}


	/**
	 * @return int
	 */
	public function getMessageType(): int {
		return $this->messageType;
	}

	/**
	 * @param int $messageType
	 *
	 * @return IStratosMessage
	 */
	public function setMessageType(int $messageType): IStratosMessage {
		$this->messageType = $messageType;

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
	 * @return IStratosMessage
	 */
	public function setTitle(string $title): IStratosMessage {
		$this->title = $title;

		return $this;
	}


	/**
	 * @return string
	 */
	public function getContent(): string {
		return $this->content;
	}

	/**
	 * @param string $content
	 *
	 * @return IStratosMessage
	 */
	public function setContent(string $content): IStratosMessage {
		$this->content = $content;

		return $this;
	}


	/**
	 * @return string
	 */
	public function getDetail(): string {
		return $this->detail;
	}

	/**
	 * @param string $detail
	 *
	 * @return IStratosMessage
	 */
	public function setDetail(string $detail): IStratosMessage {
		$this->detail = $detail;

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
	 * @return IStratosMessage
	 */
	public function setLink(string $link): IStratosMessage {
		$this->link = $link;

		return $this;
	}


	/**
	 * @return array
	 */
	public function jsonSerialize(): array {
		return array_merge(
			parent::jsonSerialize(),
			[
				'message_type' => $this->getMessageType(),
				'title'        => $this->getTitle(),
				'content'      => $this->getContent(),
				'link'         => $this->getLink()
			]
		);
	}

}

