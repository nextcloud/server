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


namespace OC\Push\Model;


use daita\NcSmallPhpTools\Traits\TArrayTools;
use JsonSerializable;
use OCP\Push\Model\IPushItem;
use OCP\Push\Model\IPushWrapper;


/**
 * Class PushManager
 *
 * @package OC\Push\Model
 */
class PushWrapper implements IPushWrapper, JsonSerializable {


	use TArrayTools;


	/** @var IPushItem */
	private $item;

	/** @var array */
	private $recipients = [];

	/** @var int */
	private $creation = 0;


	/**
	 * PushWrapper constructor.
	 *
	 * @param IPushItem $item
	 */
	public function __construct($item = null) {
		if ($item !== null && $item instanceof IPushItem) {
			$this->item = $item;
		}

		return $this;
	}


	/**
	 * @return bool
	 */
	public function hasItem(): bool {
		return ($this->item !== null);
	}

	/**
	 * @return IPushItem
	 */
	public function getItem(): IPushItem {
		return $this->item;
	}

	/**
	 * @param IPushItem $item
	 *
	 * @return IPushWrapper
	 */
	public function setItem(IPushItem $item): IPushWrapper {
		$this->item = $item;

		return $this;
	}


	/**
	 * @return string[]
	 */
	public function getRecipients(): array {
		return $this->recipients;
	}

	/**
	 * @param array $recipients
	 *
	 * @return IPushWrapper
	 */
	public function setRecipients(array $recipients): IPushWrapper {
		$this->recipients = [];
		$this->addRecipients($recipients);

		return $this;
	}

	/**
	 * @param string $recipient
	 *
	 * @return IPushWrapper
	 */
	public function addRecipient(string $recipient): IPushWrapper {
		if (!in_array($recipient, $this->recipients)) {
			$this->recipients[] = $recipient;
		}

		return $this;
	}

	/**
	 * @param array $recipients
	 *
	 * @return IPushWrapper
	 */
	public function addRecipients(array $recipients): IPushWrapper {
		foreach ($recipients as $recipient) {
			$this->addRecipient($recipient);
		}

		return $this;
	}


	/**
	 * @param array $import
	 *
	 * @return self
	 */
	public function import(array $import): IPushWrapper {
		$item = new PushItem();
		$item->import($this->getArray('item', $import, []));

		$this->setItem($item);
		$this->setRecipients($this->getArray('recipients', $import, []));

		return $this;
	}


	/**
	 * @return array
	 */
	public function jsonSerialize(): array {
		return [
			'item'       => ($this->hasItem()) ? $this->getItem() : null,
			'recipients' => $this->getRecipients()
		];
	}

}

