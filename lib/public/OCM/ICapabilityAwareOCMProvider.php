<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\OCM;

/**
 * Version 1.1 and 1.2 extensions to the Open Cloud Mesh Discovery API
 * @link https://github.com/cs3org/OCM-API/
 * @since 32.0.0
 */
interface ICapabilityAwareOCMProvider extends IOCMProvider {
	/**
	 * get the capabilities
	 *
	 * @return array
	 * @since 32.0.0
	 */
	public function getCapabilities(): array;
	
	/**
	 * get the provider name
	 *
	 * @return string
	 * @since 32.0.0
	 */
	public function getProvider(): string;
	
	/**
	 * returns the invite accept dialog
	 *
	 * @return string
	 * @since 32.0.0
	 */
	public function getInviteAcceptDialog(): string;
	
	/**
	 * set the capabilities
	 *
	 * @param array $capabilities
	 *
	 * @return $this
	 * @since 32.0.0
	 */
	public function setCapabilities(array $capabilities): static;

	/**
	 * set the invite accept dialog
	 *
	 * @param string $inviteAcceptDialog
	 *
	 * @return $this
	 * @since 32.0.0
	 */
	public function setInviteAcceptDialog(string $inviteAcceptDialog): static;
}
