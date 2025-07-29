<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
class OC_Response {
	/**
	 * Sets the content disposition header (with possible workarounds)
	 * @param string $filename file name
	 * @param string $type disposition type, either 'attachment' or 'inline'
	 */
	public static function setContentDispositionHeader($filename, $type = 'attachment') {
		if (\OC::$server->getRequest()->isUserAgent(
			[
				\OC\AppFramework\Http\Request::USER_AGENT_IE,
				\OC\AppFramework\Http\Request::USER_AGENT_ANDROID_MOBILE_CHROME,
				\OC\AppFramework\Http\Request::USER_AGENT_FREEBOX,
			])) {
			header('Content-Disposition: ' . rawurlencode($type) . '; filename="' . rawurlencode($filename) . '"');
		} else {
			header('Content-Disposition: ' . rawurlencode($type) . '; filename*=UTF-8\'\'' . rawurlencode($filename)
												 . '; filename="' . rawurlencode($filename) . '"');
		}
	}

	/**
	 * Sets the content length header (with possible workarounds)
	 * @param string|int|float $length Length to be sent
	 */
	public static function setContentLengthHeader($length) {
		if (PHP_INT_SIZE === 4) {
			if ($length > PHP_INT_MAX && stripos(PHP_SAPI, 'apache') === 0) {
				// Apache PHP SAPI casts Content-Length headers to PHP integers.
				// This enforces a limit of PHP_INT_MAX (2147483647 on 32-bit
				// platforms). So, if the length is greater than PHP_INT_MAX,
				// we just do not send a Content-Length header to prevent
				// bodies from being received incompletely.
				return;
			}
			// Convert signed integer or float to unsigned base-10 string.
			$lfh = new \OC\LargeFileHelper;
			$length = $lfh->formatUnsignedInteger($length);
		}
		header('Content-Length: ' . $length);
	}

	/**
	 * This function adds some security related headers to all requests served via base.php
	 * The implementation of this function has to happen here to ensure that all third-party
	 * components (e.g. SabreDAV) also benefit from this headers.
	 */
	public static function addSecurityHeaders() {
		/**
		 * FIXME: Content Security Policy for legacy ownCloud components. This
		 * can be removed once \OCP\AppFramework\Http\Response from the AppFramework
		 * is used everywhere.
		 * @see \OCP\AppFramework\Http\Response::getHeaders
		 */
		$policy = 'default-src \'self\'; '
			. 'script-src \'self\' \'nonce-' . \OC::$server->getContentSecurityPolicyNonceManager()->getNonce() . '\'; '
			. 'style-src \'self\' \'unsafe-inline\'; '
			. 'frame-src *; '
			. 'img-src * data: blob:; '
			. 'font-src \'self\' data:; '
			. 'media-src *; '
			. 'connect-src *; '
			. 'object-src \'none\'; '
			. 'base-uri \'self\'; ';
		header('Content-Security-Policy:' . $policy);

		// Send fallback headers for installations that don't have the possibility to send
		// custom headers on the webserver side
		if (getenv('modHeadersAvailable') !== 'true') {
			header('Referrer-Policy: no-referrer'); // https://www.w3.org/TR/referrer-policy/
			header('X-Content-Type-Options: nosniff'); // Disable sniffing the content type for IE
			header('X-Frame-Options: SAMEORIGIN'); // Disallow iFraming from other domains
			header('X-Permitted-Cross-Domain-Policies: none'); // https://www.adobe.com/devnet/adobe-media-server/articles/cross-domain-xml-for-streaming.html
			header('X-Robots-Tag: noindex, nofollow'); // https://developers.google.com/webmasters/control-crawl-index/docs/robots_meta_tag
		}
	}
}
