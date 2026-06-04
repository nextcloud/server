<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Install\Events;

use OCP\EventDispatcher\Event;

/**
 * Emitted when the Nextcloud installation has been completed successfully.
 *
 * This event is dispatched after:
 * - The database has been configured and migrations have run
 * - The admin user has been created (if applicable)
 * - Default apps have been installed
 * - Background jobs have been configured
 * - The system has been marked as installed
 *
 * Apps can listen to this event to perform additional actions after installation,
 * such as:
 * - Sending notification emails
 * - Triggering external APIs
 * - Initializing app-specific data
 * - Setting up integrations
 *
 * @since 33.0.0
 */
class InstallationCompletedEvent extends Event {
	/**
	 * @since 33.0.0
	 */
	public function __construct(
		private string $dataDirectory,
		private ?string $adminUsername = null,
		private ?string $adminEmail = null,
	) {
		parent::__construct();
	}

	/**
	 * Get the configured data directory path
	 *
	 * @since 33.0.0
	 */
	public function getDataDirectory(): string {
		return $this->dataDirectory;
	}

	/**
	 * Get the admin username if an admin user was created
	 *
	 * @since 33.0.0
	 */
	public function getAdminUsername(): ?string {
		return $this->adminUsername;
	}

	/**
	 * Get the admin email if configured
	 *
	 * @since 33.0.0
	 */
	public function getAdminEmail(): ?string {
		return $this->adminEmail;
	}

	/**
	 * Check if an admin user was created during installation
	 *
	 * @since 33.0.0
	 */
	public function hasAdminUser(): bool {
		return $this->adminUsername !== null;
	}
}
