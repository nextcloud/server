<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Files\Template;

/**
 * @since 21.0.0
 */
final class TemplateFileCreator implements \JsonSerializable {
	protected $appId;
	/** @var list<string> $mimetypes */
	protected $mimetypes = [];
	protected $actionName;
	protected $fileExtension;
	/** @var ?string $iconClass */
	protected $iconClass;
	/** @var ?string $iconSvgInline */
	protected $iconSvgInline;
	/** @var ?float $ratio */
	protected $ratio = null;
	protected $order = 100;
	/**
	 * @since 27.0.0
	 * @deprecated 28.0.0
	 */
	protected string $actionLabel = '';

	/**
	 * @since 21.0.0
	 */
	public function __construct(
		string $appId, string $actionName, string $fileExtension,
	) {
		$this->appId = $appId;
		$this->actionName = $actionName;
		$this->fileExtension = $fileExtension;
	}

	/**
	 * @since 21.0.0
	 */
	public function getAppId(): string {
		return $this->appId;
	}

	/**
	 * @since 21.0.0
	 * @deprecated 29.0.0
	 */
	public function setIconClass(string $iconClass): TemplateFileCreator {
		$this->iconClass = $iconClass;
		return $this;
	}

	/**
	 * @since 29.0.0
	 */
	public function setIconSvgInline(string $iconSvgInline): TemplateFileCreator {
		$this->iconSvgInline = $iconSvgInline;
		return $this;
	}

	/**
	 * @since 21.0.0
	 */
	public function addMimetype(string $mimetype): TemplateFileCreator {
		$this->mimetypes[] = $mimetype;
		return $this;
	}

	/**
	 * @since 21.0.0
	 */
	public function getMimetypes(): array {
		return $this->mimetypes;
	}

	/**
	 * @since 21.0.0
	 */
	public function setRatio(float $ratio): TemplateFileCreator {
		$this->ratio = $ratio;
		return $this;
	}

	/**
	 * @param int $order order in which the create action shall be listed
	 * @since 21.0.0
	 */
	public function setOrder(int $order): TemplateFileCreator {
		$this->order = $order;
		return $this;
	}

	/**
	 * @since 21.0.0
	 */
	public function getOrder(): int {
		return $this->order;
	}

	/**
	 * @since 27.0.0
	 */
	public function setActionLabel(string $actionLabel): TemplateFileCreator {
		$this->actionLabel = $actionLabel;
		return $this;
	}

	/**
	 * @since 27.0.0
	 */
	public function getActionLabel(): string {
		return $this->actionLabel;
	}

	/**
	 * @since 21.0.0
	 * @return array{app: string, label: string, extension: string, iconClass: ?string, iconSvgInline: ?string, mimetypes: list<string>, ratio: ?float, actionLabel: string}
	 */
	public function jsonSerialize(): array {
		return [
			'app' => $this->appId,
			'label' => $this->actionName,
			'extension' => $this->fileExtension,
			'iconClass' => $this->iconClass,
			'iconSvgInline' => $this->iconSvgInline,
			'mimetypes' => $this->mimetypes,
			'ratio' => $this->ratio,
			'actionLabel' => $this->actionLabel,
		];
	}
}
