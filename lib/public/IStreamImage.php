<?php

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP;

/**
 * @since 24.0.0
 */
interface IStreamImage extends IImage {
	/**
	 * @since 24.0.0
	 * @return false|resource Returns the image resource if any
	 */
	public function resource();
}
