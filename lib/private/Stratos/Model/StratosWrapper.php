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
use JsonSerializable;
use OCP\Stratos\Model\IStratosItem;
use OCP\Stratos\Model\IStratosWrapper;


/**
 * Class StratosManager
 *
 * @package OC\Stratos\Model
 */
class StratosWrapper implements IStratosWrapper, JsonSerializable {


	use TArrayTools;


	/** @var IStratosItem */
	private $item;

	/** @var array */
	private $recipients = [];

	/** @var int */
	private $creation = 0;


	/**
	 * StratosWrapper constructor.
	 *
	 * @param IStratosItem $item
	 */
	public function __construct($item = null) {
		if ($item !== null && $item instanceof IStratosItem) {
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
	 * @return IStratosItem
	 */
	public function getItem(): IStratosItem {
		return $this->item;
	}

	/**
	 * @param IStratosItem $item
	 *
	 * @return IStratosWrapper
	 */
	public function setItem(IStratosItem $item): IStratosWrapper {
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
	 * @return IStratosWrapper
	 */
	public function setRecipients(array $recipients): IStratosWrapper {
		$this->recipients = [];
		$this->addRecipients($recipients);

		return $this;
	}

	/**
	 * @param string $recipient
	 *
	 * @return IStratosWrapper
	 */
	public function addRecipient(string $recipient): IStratosWrapper {
		if (!in_array($recipient, $this->recipients)) {
			$this->recipients[] = $recipient;
		}

		return $this;
	}

	/**
	 * @param array $recipients
	 *
	 * @return IStratosWrapper
	 */
	public function addRecipients(array $recipients): IStratosWrapper {
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
	public function import(array $import): IStratosWrapper {
		$item = new StratosItem();
		$item->import($this->getArray('item', $import, []));

		$this->setItem($item);
		$this->setRecipients($this->getArray('recipients', $import, []));

		return $this;
	}


	/**
	 * @return mixed|void
	 */
	public function jsonSerialize(): array {
		return [
			'item'       => ($this->hasItem()) ? $this->getItem() : null,
			'recipients' => $this->getRecipients()
		];
	}

}

