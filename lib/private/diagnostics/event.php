<?php
/**
 * Copyright (c) 2014 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OC\Diagnostics;

use OCP\Diagnostics\IEvent;

class Event implements IEvent {
	/**
	 * @var string
	 */
	protected $id;

	/**
	 * @var float
	 */
	protected $start;

	/**
	 * @var float
	 */
	protected $end;

	/**
	 * @var string
	 */
	protected $description;

	/**
	 * @param string $id
	 * @param string $description
	 * @param float $start
	 */
	public function __construct($id, $description, $start) {
		$this->id = $id;
		$this->description = $description;
		$this->start = $start;
	}

	/**
	 * @param float $time
	 */
	public function end($time) {
		$this->end = $time;
	}

	/**
	 * @return float
	 */
	public function getStart() {
		return $this->start;
	}

	/**
	 * @return string
	 */
	public function getId() {
		return $this->id;
	}

	/**
	 * @return string
	 */
	public function getDescription() {
		return $this->description;
	}

	/**
	 * @return float
	 */
	public function getEnd() {
		return $this->end;
	}

	/**
	 * @return float
	 */
	public function getDuration() {
		if (!$this->end) {
			$this->end = microtime(true);
		}
		return $this->end - $this->start;
	}
}
