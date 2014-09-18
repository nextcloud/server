<?php
/**
 * Copyright (c) 2014 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OCP;

interface ICertificate {
	/**
	 * @return string
	 */
	public function getName();

	/**
	 * @return string
	 */
	public function getCommonName();

	/**
	 * @return string
	 */
	public function getOrganization();

	/**
	 * @return \DateTime
	 */
	public function getIssueDate();

	/**
	 * @return \DateTime
	 */
	public function getExpireDate();

	/**
	 * @return bool
	 */
	public function isExpired();

	/**
	 * @return string
	 */
	public function getIssuerName();

	/**
	 * @return string
	 */
	public function getIssuerOrganization();
}
