<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author J0WI <J0WI@users.noreply.github.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <robin@icewind.nl>
 *
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
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
