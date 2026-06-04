<?php

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\DirectEditing;

/**
 * @since 18.0.0
 */
abstract class ACreateFromTemplate extends ACreateEmpty {
	/**
	 * List of available templates for the create from template action
	 *
	 * @since 18.0.0
	 * @return ATemplate[]
	 */
	abstract public function getTemplates(): array;
}
