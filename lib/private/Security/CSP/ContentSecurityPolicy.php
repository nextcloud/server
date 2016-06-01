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
namespace OC\Security\CSP;

/**
 * Class ContentSecurityPolicy extends the public class and adds getter and setters.
 * This is necessary since we don't want to expose the setters and getters to the
 * public API.
 *
 * @package OC\Security\CSP
 */
class ContentSecurityPolicy extends \OCP\AppFramework\Http\ContentSecurityPolicy {
	/**
	 * @return boolean
	 */
	public function isInlineScriptAllowed() {
		return $this->inlineScriptAllowed;
	}

	/**
	 * @param boolean $inlineScriptAllowed
	 */
	public function setInlineScriptAllowed($inlineScriptAllowed) {
		$this->inlineScriptAllowed = $inlineScriptAllowed;
	}

	/**
	 * @return boolean
	 */
	public function isEvalScriptAllowed() {
		return $this->evalScriptAllowed;
	}

	/**
	 * @param boolean $evalScriptAllowed
	 */
	public function setEvalScriptAllowed($evalScriptAllowed) {
		$this->evalScriptAllowed = $evalScriptAllowed;
	}

	/**
	 * @return array
	 */
	public function getAllowedScriptDomains() {
		return $this->allowedScriptDomains;
	}

	/**
	 * @param array $allowedScriptDomains
	 */
	public function setAllowedScriptDomains($allowedScriptDomains) {
		$this->allowedScriptDomains = $allowedScriptDomains;
	}

	/**
	 * @return boolean
	 */
	public function isInlineStyleAllowed() {
		return $this->inlineStyleAllowed;
	}

	/**
	 * @param boolean $inlineStyleAllowed
	 */
	public function setInlineStyleAllowed($inlineStyleAllowed) {
		$this->inlineStyleAllowed = $inlineStyleAllowed;
	}

	/**
	 * @return array
	 */
	public function getAllowedStyleDomains() {
		return $this->allowedStyleDomains;
	}

	/**
	 * @param array $allowedStyleDomains
	 */
	public function setAllowedStyleDomains($allowedStyleDomains) {
		$this->allowedStyleDomains = $allowedStyleDomains;
	}

	/**
	 * @return array
	 */
	public function getAllowedImageDomains() {
		return $this->allowedImageDomains;
	}

	/**
	 * @param array $allowedImageDomains
	 */
	public function setAllowedImageDomains($allowedImageDomains) {
		$this->allowedImageDomains = $allowedImageDomains;
	}

	/**
	 * @return array
	 */
	public function getAllowedConnectDomains() {
		return $this->allowedConnectDomains;
	}

	/**
	 * @param array $allowedConnectDomains
	 */
	public function setAllowedConnectDomains($allowedConnectDomains) {
		$this->allowedConnectDomains = $allowedConnectDomains;
	}

	/**
	 * @return array
	 */
	public function getAllowedMediaDomains() {
		return $this->allowedMediaDomains;
	}

	/**
	 * @param array $allowedMediaDomains
	 */
	public function setAllowedMediaDomains($allowedMediaDomains) {
		$this->allowedMediaDomains = $allowedMediaDomains;
	}

	/**
	 * @return array
	 */
	public function getAllowedObjectDomains() {
		return $this->allowedObjectDomains;
	}

	/**
	 * @param array $allowedObjectDomains
	 */
	public function setAllowedObjectDomains($allowedObjectDomains) {
		$this->allowedObjectDomains = $allowedObjectDomains;
	}

	/**
	 * @return array
	 */
	public function getAllowedFrameDomains() {
		return $this->allowedFrameDomains;
	}

	/**
	 * @param array $allowedFrameDomains
	 */
	public function setAllowedFrameDomains($allowedFrameDomains) {
		$this->allowedFrameDomains = $allowedFrameDomains;
	}

	/**
	 * @return array
	 */
	public function getAllowedFontDomains() {
		return $this->allowedFontDomains;
	}

	/**
	 * @param array $allowedFontDomains
	 */
	public function setAllowedFontDomains($allowedFontDomains) {
		$this->allowedFontDomains = $allowedFontDomains;
	}

	/**
	 * @return array
	 */
	public function getAllowedChildSrcDomains() {
		return $this->allowedChildSrcDomains;
	}

	/**
	 * @param array $allowedChildSrcDomains
	 */
	public function setAllowedChildSrcDomains($allowedChildSrcDomains) {
		$this->allowedChildSrcDomains = $allowedChildSrcDomains;
	}

}
