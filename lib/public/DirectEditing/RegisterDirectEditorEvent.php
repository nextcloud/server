<?php

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\DirectEditing;

use OCP\EventDispatcher\Event;

/**
 * Event to allow to register the direct editor.
 *
 * @since 18.0.0
 */
class RegisterDirectEditorEvent extends Event {
	/**
	 * @var IManager
	 */
	private $manager;

	/**
	 * RegisterDirectEditorEvent constructor.
	 *
	 * @param IManager $manager
	 * @since 18.0.0
	 */
	public function __construct(IManager $manager) {
		parent::__construct();
		$this->manager = $manager;
	}

	/**
	 * @since 18.0.0
	 * @param IEditor $editor
	 */
	public function register(IEditor $editor): void {
		$this->manager->registerDirectEditor($editor);
	}
}
