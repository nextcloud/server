<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2021 Julius Härtl <jus@bitgrid.net>
 *
 * @author John Molakvoæ <skjnldsv@protonmail.com>
 * @author Julius Härtl <jus@bitgrid.net>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */
namespace OCP\Files\Template;

/**
 * @since 21.0.0
 */
final class TemplateFileCreator implements \JsonSerializable {
	protected $appId;
	/** @var string[] $mimetypes */
	protected $mimetypes = [];
	protected $actionName;
	protected $fileExtension;
	/** @var ?string $iconClass */
	protected $iconClass;
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
		string $appId, string $actionName, string $fileExtension
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
	 */
	public function setIconClass(string $iconClass): TemplateFileCreator {
		$this->iconClass = $iconClass;
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
	 * @return array{app: string, label: string, extension: string, iconClass: ?string, mimetypes: string[], ratio: ?float, actionLabel: string}
	 */
	public function jsonSerialize(): array {
		return [
			'app' => $this->appId,
			'label' => $this->actionName,
			'extension' => $this->fileExtension,
			'iconClass' => $this->iconClass,
			'mimetypes' => $this->mimetypes,
			'ratio' => $this->ratio,
			'actionLabel' => $this->actionLabel,
		];
	}
}
