<?php

/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Files_External\Lib;

interface IIdentifier {

	public function getIdentifier(): string;

	public function setIdentifier(string $identifier): self;
}
