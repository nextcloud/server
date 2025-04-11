<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Share\Events;

use OC\Files\View;
use OCP\EventDispatcher\Event;
use OCP\Share\IShare;

/**
 * @since 19.0.0
 */
class VerifyMountPointEvent extends Event {
	/** @var IShare */
	private $share;
	/** @var View */
	private $view;
	/** @var string */
	private $parent;

	/**
	 * @since 19.0.0
	 */
	public function __construct(IShare $share,
		View $view,
		string $parent) {
		parent::__construct();

		$this->share = $share;
		$this->view = $view;
		$this->parent = $parent;
	}

	/**
	 * @since 19.0.0
	 */
	public function getShare(): IShare {
		return $this->share;
	}

	/**
	 * @since 19.0.0
	 */
	public function getView(): View {
		return $this->view;
	}

	/**
	 * @since 19.0.0
	 */
	public function getParent(): string {
		return $this->parent;
	}

	/**
	 * @since 19.0.0
	 */
	public function setParent(string $parent): void {
		$this->parent = $parent;
	}
}
