<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\AppFramework\Http;

/**
 * Class StrictEvalContentSecurityPolicy is a simple helper which allows applications to
 * modify the Content-Security-Policy sent by Nextcloud. Per default only JavaScript,
 * stylesheets, images, fonts, media and connections from the same domain
 * ('self') are allowed.
 *
 * Even if a value gets modified above defaults will still get appended. Please
 * note that Nextcloud ships already with sensible defaults and those policies
 * should require no modification at all for most use-cases.
 *
 * This is a temp helper class from the default ContentSecurityPolicy to allow slow
 * migration to a stricter CSP. This does not allow unsafe eval.
 *
 * @since 14.0.0
 * @deprecated 17.0.0
 */
class StrictEvalContentSecurityPolicy extends ContentSecurityPolicy {
	/**
	 * @since 14.0.0
	 */
	public function __construct() {
		$this->evalScriptAllowed = false;
	}
}
