<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Mail\Provider;

/**
 * Mail Service Identity Interface
 *
 * This interface is a base requirement of methods and functionality used to construct a mail service identity.
 *
 * @since 30.0.0
 *
 */
interface IServiceIdentity {

	/**
	 * An arbitrary unique text string identifying this credential type
	 *
	 * @since 30.0.0
	 *
	 * @return string
	 */
	public function type(): string;

}
