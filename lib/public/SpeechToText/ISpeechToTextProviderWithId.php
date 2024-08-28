<?php

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\SpeechToText;

/**
 * @since 28.0.0
 * @deprecated 30.0.0
 */
interface ISpeechToTextProviderWithId extends ISpeechToTextProvider {

	/**
	 * @since 28.0.0
	 */
	public function getId(): string;
}
