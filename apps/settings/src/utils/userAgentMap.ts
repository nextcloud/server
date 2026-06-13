/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

// When using capture groups the following parts are extracted
// the first is used as the version number, the second as the OS
// Exception: single-group regexes (ie, androidChrome) use the first group as the version.
export const userAgentMap = {
	ie: /(?:MSIE|Trident|Trident\/7.0; rv)[ :](\d+)/,
	// Microsoft Edge User Agent from https://msdn.microsoft.com/en-us/library/hh869301(v=vs.85).aspx
	edge: /^Mozilla\/5\.0 \([^)]+\) AppleWebKit\/[0-9.]+ \(KHTML, like Gecko\) Chrome\/[0-9.]+ (?:Mobile Safari|Safari)\/[0-9.]+ Edge\/[0-9.]+$/,
	// Firefox User Agent from https://developer.mozilla.org/en-US/docs/Web/HTTP/Gecko_user_agent_string_reference
	firefox: /^Mozilla\/5\.0 \((?![^)]*Android)[^)]*(Windows|OS X|Linux)[^)]+\) Gecko\/[0-9.]+ Firefox\/(\d+)(?:\.\d)?$/,
	// Android Chrome user agent: https://developers.google.com/chrome/mobile/docs/user-agent
	androidChrome: /^Mozilla\/5\.0 \(Linux; Android[^)]+\) AppleWebKit\/[0-9.]+ \(KHTML, like Gecko\) Chrome\/(\d+)[0-9.]+ (?:Mobile )?Safari\/[0-9.]+$/,
	// Chrome User Agent from https://developer.chrome.com/multidevice/user-agent
	chrome: /^Mozilla\/5\.0 \((?![^)]*Android)[^)]*(Windows|OS X|Linux)[^)]+\) AppleWebKit\/[0-9.]+ \(KHTML, like Gecko\) Chrome\/(\d+)[0-9.]+ (?:Mobile Safari|Safari)\/[0-9.]+$/,
	// Safari User Agent from http://www.useragentstring.com/pages/Safari/
	safari: /^Mozilla\/5\.0 \([^)]*(Windows|OS X)[^)]+\) AppleWebKit\/[0-9.]+ \(KHTML, like Gecko\)(?: Version\/([0-9]+)[0-9.]+)? Safari\/[0-9.A-Z]+$/,
	iphone: / *CPU +iPhone +OS +([0-9]+)_(?:[0-9_])+ +like +Mac +OS +X */,
	ipad: /\(iPad; *CPU +OS +([0-9]+)_(?:[0-9_])+ +like +Mac +OS +X */,
	iosClient: /^Mozilla\/5\.0 \(iOS\) (?:ownCloud|Nextcloud)-iOS.*$/,
	androidClient: /^Mozilla\/5\.0 \(Android\) (?:ownCloud|Nextcloud)-android.*$/,
	iosTalkClient: /^Mozilla\/5\.0 \(iOS\) Nextcloud-Talk.*$/,
	androidTalkClient: /^Mozilla\/5\.0 \(Android\) Nextcloud-Talk.*$/,
	// DAVx5/3.3.8-beta2-gplay (2021/01/02; dav4jvm; okhttp/4.9.0) Android/10
	davx5: /DAV(?:droid|x5)\/([^ ]+)/,
	// Mozilla/5.0 (U; Linux; Maemo; Jolla; Sailfish; like Android 4.3) AppleWebKit/538.1 (KHTML, like Gecko) WebPirate/2.0 like Mobile Safari/538.1 (compatible)
	webPirate: /(Sailfish).*WebPirate\/(\d+)/,
	// Mozilla/5.0 (Maemo; Linux; U; Jolla; Sailfish; Mobile; rv:31.0) Gecko/31.0 Firefox/31.0 SailfishBrowser/1.0
	sailfishBrowser: /(Sailfish).*SailfishBrowser\/(\d+)/,
	// Neon 1.0.0+1
	neon: /Neon \d+\.\d+\.\d+\+\d+/,
}
