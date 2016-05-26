<?php
/**
 * @author Lukas Reschke <lukas@statuscode.ch>
 *
 * @copyright Copyright (c) 2016, ownCloud, Inc.
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OCP\AppFramework\Http;

use OCP\AppFramework\Http;

/**
 * Class EmptyContentSecurityPolicy is a simple helper which allows applications
 * to modify the Content-Security-Policy sent by ownCloud. Per default the policy
 * is forbidding everything.
 *
 * As alternative with sane exemptions look at ContentSecurityPolicy
 *
 * @see \OCP\AppFramework\Http\ContentSecurityPolicy
 * @package OCP\AppFramework\Http
 * @since 9.0.0
 */
class EmptyContentSecurityPolicy {
	/** @var bool Whether inline JS snippets are allowed */
	protected $inlineScriptAllowed = null;
	/**
	 * @var bool Whether eval in JS scripts is allowed
	 * TODO: Disallow per default
	 * @link https://github.com/owncloud/core/issues/11925
	 */
	protected $evalScriptAllowed = null;
	/** @var array Domains from which scripts can get loaded */
	protected $allowedScriptDomains = null;
	/**
	 * @var bool Whether inline CSS is allowed
	 * TODO: Disallow per default
	 * @link https://github.com/owncloud/core/issues/13458
	 */
	protected $inlineStyleAllowed = null;
	/** @var array Domains from which CSS can get loaded */
	protected $allowedStyleDomains = null;
	/** @var array Domains from which images can get loaded */
	protected $allowedImageDomains = null;
	/** @var array Domains to which connections can be done */
	protected $allowedConnectDomains = null;
	/** @var array Domains from which media elements can be loaded */
	protected $allowedMediaDomains = null;
	/** @var array Domains from which object elements can be loaded */
	protected $allowedObjectDomains = null;
	/** @var array Domains from which iframes can be loaded */
	protected $allowedFrameDomains = null;
	/** @var array Domains from which fonts can be loaded */
	protected $allowedFontDomains = null;
	/** @var array Domains from which web-workers and nested browsing content can load elements */
	protected $allowedChildSrcDomains = null;

	/**
	 * Whether inline JavaScript snippets are allowed or forbidden
	 * @param bool $state
	 * @return $this
	 * @since 8.1.0
	 */
	public function allowInlineScript($state = false) {
		$this->inlineScriptAllowed = $state;
		return $this;
	}

	/**
	 * Whether eval in JavaScript is allowed or forbidden
	 * @param bool $state
	 * @return $this
	 * @since 8.1.0
	 */
	public function allowEvalScript($state = true) {
		$this->evalScriptAllowed = $state;
		return $this;
	}

	/**
	 * Allows to execute JavaScript files from a specific domain. Use * to
	 * allow JavaScript from all domains.
	 * @param string $domain Domain to whitelist. Any passed value needs to be properly sanitized.
	 * @return $this
	 * @since 8.1.0
	 */
	public function addAllowedScriptDomain($domain) {
		$this->allowedScriptDomains[] = $domain;
		return $this;
	}

	/**
	 * Remove the specified allowed script domain from the allowed domains.
	 *
	 * @param string $domain
	 * @return $this
	 * @since 8.1.0
	 */
	public function disallowScriptDomain($domain) {
		$this->allowedScriptDomains = array_diff($this->allowedScriptDomains, [$domain]);
		return $this;
	}

	/**
	 * Whether inline CSS snippets are allowed or forbidden
	 * @param bool $state
	 * @return $this
	 * @since 8.1.0
	 */
	public function allowInlineStyle($state = true) {
		$this->inlineStyleAllowed = $state;
		return $this;
	}

	/**
	 * Allows to execute CSS files from a specific domain. Use * to allow
	 * CSS from all domains.
	 * @param string $domain Domain to whitelist. Any passed value needs to be properly sanitized.
	 * @return $this
	 * @since 8.1.0
	 */
	public function addAllowedStyleDomain($domain) {
		$this->allowedStyleDomains[] = $domain;
		return $this;
	}

	/**
	 * Remove the specified allowed style domain from the allowed domains.
	 *
	 * @param string $domain
	 * @return $this
	 * @since 8.1.0
	 */
	public function disallowStyleDomain($domain) {
		$this->allowedStyleDomains = array_diff($this->allowedStyleDomains, [$domain]);
		return $this;
	}

	/**
	 * Allows using fonts from a specific domain. Use * to allow
	 * fonts from all domains.
	 * @param string $domain Domain to whitelist. Any passed value needs to be properly sanitized.
	 * @return $this
	 * @since 8.1.0
	 */
	public function addAllowedFontDomain($domain) {
		$this->allowedFontDomains[] = $domain;
		return $this;
	}

	/**
	 * Remove the specified allowed font domain from the allowed domains.
	 *
	 * @param string $domain
	 * @return $this
	 * @since 8.1.0
	 */
	public function disallowFontDomain($domain) {
		$this->allowedFontDomains = array_diff($this->allowedFontDomains, [$domain]);
		return $this;
	}

	/**
	 * Allows embedding images from a specific domain. Use * to allow
	 * images from all domains.
	 * @param string $domain Domain to whitelist. Any passed value needs to be properly sanitized.
	 * @return $this
	 * @since 8.1.0
	 */
	public function addAllowedImageDomain($domain) {
		$this->allowedImageDomains[] = $domain;
		return $this;
	}

	/**
	 * Remove the specified allowed image domain from the allowed domains.
	 *
	 * @param string $domain
	 * @return $this
	 * @since 8.1.0
	 */
	public function disallowImageDomain($domain) {
		$this->allowedImageDomains = array_diff($this->allowedImageDomains, [$domain]);
		return $this;
	}

	/**
	 * To which remote domains the JS connect to.
	 * @param string $domain Domain to whitelist. Any passed value needs to be properly sanitized.
	 * @return $this
	 * @since 8.1.0
	 */
	public function addAllowedConnectDomain($domain) {
		$this->allowedConnectDomains[] = $domain;
		return $this;
	}

	/**
	 * Remove the specified allowed connect domain from the allowed domains.
	 *
	 * @param string $domain
	 * @return $this
	 * @since 8.1.0
	 */
	public function disallowConnectDomain($domain) {
		$this->allowedConnectDomains = array_diff($this->allowedConnectDomains, [$domain]);
		return $this;
	}

	/**
	 * From which domains media elements can be embedded.
	 * @param string $domain Domain to whitelist. Any passed value needs to be properly sanitized.
	 * @return $this
	 * @since 8.1.0
	 */
	public function addAllowedMediaDomain($domain) {
		$this->allowedMediaDomains[] = $domain;
		return $this;
	}

	/**
	 * Remove the specified allowed media domain from the allowed domains.
	 *
	 * @param string $domain
	 * @return $this
	 * @since 8.1.0
	 */
	public function disallowMediaDomain($domain) {
		$this->allowedMediaDomains = array_diff($this->allowedMediaDomains, [$domain]);
		return $this;
	}

	/**
	 * From which domains objects such as <object>, <embed> or <applet> are executed
	 * @param string $domain Domain to whitelist. Any passed value needs to be properly sanitized.
	 * @return $this
	 * @since 8.1.0
	 */
	public function addAllowedObjectDomain($domain) {
		$this->allowedObjectDomains[] = $domain;
		return $this;
	}

	/**
	 * Remove the specified allowed object domain from the allowed domains.
	 *
	 * @param string $domain
	 * @return $this
	 * @since 8.1.0
	 */
	public function disallowObjectDomain($domain) {
		$this->allowedObjectDomains = array_diff($this->allowedObjectDomains, [$domain]);
		return $this;
	}

	/**
	 * Which domains can be embedded in an iframe
	 * @param string $domain Domain to whitelist. Any passed value needs to be properly sanitized.
	 * @return $this
	 * @since 8.1.0
	 */
	public function addAllowedFrameDomain($domain) {
		$this->allowedFrameDomains[] = $domain;
		return $this;
	}

	/**
	 * Remove the specified allowed frame domain from the allowed domains.
	 *
	 * @param string $domain
	 * @return $this
	 * @since 8.1.0
	 */
	public function disallowFrameDomain($domain) {
		$this->allowedFrameDomains = array_diff($this->allowedFrameDomains, [$domain]);
		return $this;
	}

	/**
	 * Domains from which web-workers and nested browsing content can load elements
	 * @param string $domain Domain to whitelist. Any passed value needs to be properly sanitized.
	 * @return $this
	 * @since 8.1.0
	 */
	public function addAllowedChildSrcDomain($domain) {
		$this->allowedChildSrcDomains[] = $domain;
		return $this;
	}

	/**
	 * Remove the specified allowed child src domain from the allowed domains.
	 *
	 * @param string $domain
	 * @return $this
	 * @since 8.1.0
	 */
	public function disallowChildSrcDomain($domain) {
		$this->allowedChildSrcDomains = array_diff($this->allowedChildSrcDomains, [$domain]);
		return $this;
	}

	/**
	 * Get the generated Content-Security-Policy as a string
	 * @return string
	 * @since 8.1.0
	 */
	public function buildPolicy() {
		$policy = "default-src 'none';";

		if(!empty($this->allowedScriptDomains) || $this->inlineScriptAllowed || $this->evalScriptAllowed) {
			$policy .= 'script-src ';
			if(is_array($this->allowedScriptDomains)) {
				$policy .= implode(' ', $this->allowedScriptDomains);
			}
			if($this->inlineScriptAllowed) {
				$policy .= ' \'unsafe-inline\'';
			}
			if($this->evalScriptAllowed) {
				$policy .= ' \'unsafe-eval\'';
			}
			$policy .= ';';
		}

		if(!empty($this->allowedStyleDomains) || $this->inlineStyleAllowed) {
			$policy .= 'style-src ';
			if(is_array($this->allowedStyleDomains)) {
				$policy .= implode(' ', $this->allowedStyleDomains);
			}
			if($this->inlineStyleAllowed) {
				$policy .= ' \'unsafe-inline\'';
			}
			$policy .= ';';
		}

		if(!empty($this->allowedImageDomains)) {
			$policy .= 'img-src ' . implode(' ', $this->allowedImageDomains);
			$policy .= ';';
		}

		if(!empty($this->allowedFontDomains)) {
			$policy .= 'font-src ' . implode(' ', $this->allowedFontDomains);
			$policy .= ';';
		}

		if(!empty($this->allowedConnectDomains)) {
			$policy .= 'connect-src ' . implode(' ', $this->allowedConnectDomains);
			$policy .= ';';
		}

		if(!empty($this->allowedMediaDomains)) {
			$policy .= 'media-src ' . implode(' ', $this->allowedMediaDomains);
			$policy .= ';';
		}

		if(!empty($this->allowedObjectDomains)) {
			$policy .= 'object-src ' . implode(' ', $this->allowedObjectDomains);
			$policy .= ';';
		}

		if(!empty($this->allowedFrameDomains)) {
			$policy .= 'frame-src ' . implode(' ', $this->allowedFrameDomains);
			$policy .= ';';
		}

		if(!empty($this->allowedChildSrcDomains)) {
			$policy .= 'child-src ' . implode(' ', $this->allowedChildSrcDomains);
			$policy .= ';';
		}

		return rtrim($policy, ';');
	}
}
