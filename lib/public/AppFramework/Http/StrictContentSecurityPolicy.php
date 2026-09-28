<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\AppFramework\Http;

/**
 * Class StrictContentSecurityPolicy is a simple helper which allows applications to
 * modify the Content-Security-Policy sent by Nextcloud. Per default only JavaScript,
 * stylesheets, images, fonts, media and connections from the same domain
 * ('self') are allowed.
 *
 * Even if a value gets modified above defaults will still get appended. Please
 * note that Nextcloud ships already with sensible defaults and those policies
 * should require no modification at all for most use-cases.
 *
 * This class represents out strictest defaults. They may get change from release
 * to release if more strict CSP directives become available.
 *
 * @since 14.0.0
 * @deprecated 17.0.0
 */
class StrictContentSecurityPolicy extends EmptyContentSecurityPolicy {
	/** @var bool Whether inline JS snippets are allowed */
	protected $inlineScriptAllowed = false;
	/** @var bool Whether eval in JS scripts is allowed */
	protected $evalScriptAllowed = false;
	/** @var bool Whether WebAssembly compilation is allowed */
	protected ?bool $evalWasmAllowed = false;
	/** @var array Domains from which scripts can get loaded */
	protected $allowedScriptDomains = [
		'\'self\'',
	];
	/** @var bool Whether inline CSS is allowed */
	protected $inlineStyleAllowed = false;
	/** @var array Domains from which CSS can get loaded */
	protected $allowedStyleDomains = [
		'\'self\'',
	];
	/** @var array Domains from which images can get loaded */
	protected $allowedImageDomains = [
		'\'self\'',
		'data:',
		'blob:',
	];
	/** @var array Domains to which connections can be done */
	protected $allowedConnectDomains = [
		'\'self\'',
	];
	/** @var array Domains from which media elements can be loaded */
	protected $allowedMediaDomains = [
		'\'self\'',
	];
	/** @var array Domains from which object elements can be loaded */
	protected $allowedObjectDomains = [];
	/** @var array Domains from which iframes can be loaded */
	protected $allowedFrameDomains = [];
	/** @var array Domains from which fonts can be loaded */
	protected $allowedFontDomains = [
		'\'self\'',
	];
	/** @var array Domains from which web-workers and nested browsing content can load elements */
	protected $allowedChildSrcDomains = [];

	/** @var array Domains which can embed this Nextcloud instance */
	protected $allowedFrameAncestors = [];
}
