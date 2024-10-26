<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
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
	public function isInlineScriptAllowed(): bool {
		return $this->inlineScriptAllowed;
	}

	public function setInlineScriptAllowed(bool $inlineScriptAllowed): void {
		$this->inlineScriptAllowed = $inlineScriptAllowed;
	}

	public function isEvalScriptAllowed(): bool {
		return $this->evalScriptAllowed;
	}

	/**
	 * @deprecated 17.0.0 Unsafe eval should not be used anymore.
	 */
	public function setEvalScriptAllowed(bool $evalScriptAllowed): void {
		$this->evalScriptAllowed = $evalScriptAllowed;
	}

	public function isEvalWasmAllowed(): ?bool {
		return $this->evalWasmAllowed;
	}

	public function setEvalWasmAllowed(bool $evalWasmAllowed): void {
		$this->evalWasmAllowed = $evalWasmAllowed;
	}

	public function getAllowedScriptDomains(): array {
		return $this->allowedScriptDomains;
	}

	public function setAllowedScriptDomains(array $allowedScriptDomains): void {
		$this->allowedScriptDomains = $allowedScriptDomains;
	}

	public function isInlineStyleAllowed(): bool {
		return $this->inlineStyleAllowed;
	}

	public function setInlineStyleAllowed(bool $inlineStyleAllowed): void {
		$this->inlineStyleAllowed = $inlineStyleAllowed;
	}

	public function getAllowedStyleDomains(): array {
		return $this->allowedStyleDomains;
	}

	public function setAllowedStyleDomains(array $allowedStyleDomains): void {
		$this->allowedStyleDomains = $allowedStyleDomains;
	}

	public function getAllowedImageDomains(): array {
		return $this->allowedImageDomains;
	}

	public function setAllowedImageDomains(array $allowedImageDomains): void {
		$this->allowedImageDomains = $allowedImageDomains;
	}

	public function getAllowedConnectDomains(): array {
		return $this->allowedConnectDomains;
	}

	public function setAllowedConnectDomains(array $allowedConnectDomains): void {
		$this->allowedConnectDomains = $allowedConnectDomains;
	}

	public function getAllowedMediaDomains(): array {
		return $this->allowedMediaDomains;
	}

	public function setAllowedMediaDomains(array $allowedMediaDomains): void {
		$this->allowedMediaDomains = $allowedMediaDomains;
	}

	public function getAllowedObjectDomains(): array {
		return $this->allowedObjectDomains;
	}

	public function setAllowedObjectDomains(array $allowedObjectDomains): void {
		$this->allowedObjectDomains = $allowedObjectDomains;
	}

	public function getAllowedFrameDomains(): array {
		return $this->allowedFrameDomains;
	}

	public function setAllowedFrameDomains(array $allowedFrameDomains): void {
		$this->allowedFrameDomains = $allowedFrameDomains;
	}

	public function getAllowedFontDomains(): array {
		return $this->allowedFontDomains;
	}

	public function setAllowedFontDomains($allowedFontDomains): void {
		$this->allowedFontDomains = $allowedFontDomains;
	}

	/**
	 * @deprecated 15.0.0 use FrameDomains and WorkerSrcDomains
	 */
	public function getAllowedChildSrcDomains(): array {
		return $this->allowedChildSrcDomains;
	}

	/**
	 * @param array $allowedChildSrcDomains
	 * @deprecated 15.0.0 use FrameDomains and WorkerSrcDomains
	 */
	public function setAllowedChildSrcDomains($allowedChildSrcDomains): void {
		$this->allowedChildSrcDomains = $allowedChildSrcDomains;
	}

	public function getAllowedFrameAncestors(): array {
		return $this->allowedFrameAncestors;
	}

	/**
	 * @param array $allowedFrameAncestors
	 */
	public function setAllowedFrameAncestors($allowedFrameAncestors): void {
		$this->allowedFrameAncestors = $allowedFrameAncestors;
	}

	public function getAllowedWorkerSrcDomains(): array {
		return $this->allowedWorkerSrcDomains;
	}

	public function setAllowedWorkerSrcDomains(array $allowedWorkerSrcDomains): void {
		$this->allowedWorkerSrcDomains = $allowedWorkerSrcDomains;
	}

	public function getAllowedFormActionDomains(): array {
		return $this->allowedFormActionDomains;
	}

	public function setAllowedFormActionDomains(array $allowedFormActionDomains): void {
		$this->allowedFormActionDomains = $allowedFormActionDomains;
	}


	public function getReportTo(): array {
		return $this->reportTo;
	}

	public function setReportTo(array $reportTo): void {
		$this->reportTo = $reportTo;
	}

	public function isStrictDynamicAllowed(): bool {
		return $this->strictDynamicAllowed;
	}

	public function setStrictDynamicAllowed(bool $strictDynamicAllowed): void {
		$this->strictDynamicAllowed = $strictDynamicAllowed;
	}

	public function isStrictDynamicAllowedOnScripts(): bool {
		return $this->strictDynamicAllowedOnScripts;
	}

	public function setStrictDynamicAllowedOnScripts(bool $strictDynamicAllowedOnScripts): void {
		$this->strictDynamicAllowedOnScripts = $strictDynamicAllowedOnScripts;
	}
}
