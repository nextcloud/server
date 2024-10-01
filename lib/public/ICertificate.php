<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCP;

/**
 * Interface ICertificate
 *
 * @since 8.0.0
 */
interface ICertificate {
	/**
	 * @return string
	 * @since 8.0.0
	 */
	public function getName(): string;

	/**
	 * @return string|null
	 * @since 8.0.0
	 */
	public function getCommonName(): ?string;

	/**
	 * @return string|null
	 * @since 8.0.0
	 */
	public function getOrganization(): ?string;

	/**
	 * @return \DateTime
	 * @since 8.0.0
	 */
	public function getIssueDate(): \DateTime;

	/**
	 * @return \DateTime
	 * @since 8.0.0
	 */
	public function getExpireDate(): \DateTime;

	/**
	 * @return bool
	 * @since 8.0.0
	 */
	public function isExpired(): bool;

	/**
	 * @return string|null
	 * @since 8.0.0
	 */
	public function getIssuerName(): ?string;

	/**
	 * @return string|null
	 * @since 8.0.0
	 */
	public function getIssuerOrganization(): ?string;
}
