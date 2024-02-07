<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2022 Carl Schwan <carl@carlschwan.eu>
 *
 * @author Carl Schwan <carl@carlschwan.eu>
 * @author Côme Chilliet <come.chilliet@nextcloud.com>
 *
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OCP\SetupCheck;

use OCP\RichObjectStrings\IValidator;

/**
 * @brief This class is used for storing the result of a setup check
 *
 * @since 28.0.0
 */
class SetupResult implements \JsonSerializable {
	public const SUCCESS = 'success';
	public const INFO = 'info';
	public const WARNING = 'warning';
	public const ERROR = 'error';

	/**
	 * @param string $name Translated name to display to the user
	 */
	private ?string $name = null;

	/**
	 * @brief Private constructor, use success()/info()/warning()/error() instead
	 * @param self::SUCCESS|self::INFO|self::WARNING|self::ERROR $severity
	 * @throws \OCP\RichObjectStrings\InvalidObjectExeption
	 * @since 28.0.0
	 * @since 28.0.2 Optional parameter ?array $descriptionParameters
	 * @since 28.0.2 throws \OCP\RichObjectStrings\InvalidObjectExeption
	 */
	private function __construct(
		private string $severity,
		private ?string $description = null,
		private ?array $descriptionParameters = null,
		private ?string $linkToDoc = null,
	) {
		if ($description !== null && $descriptionParameters !== null) {
			\OCP\Server::get(IValidator::class)->validate($description, $descriptionParameters);
		}
	}

	/**
	 * @brief Create a success result object
	 * @param ?string $description Translated detailed description to display to the user
	 * @param ?string $linkToDoc URI of related relevent documentation, be it from Nextcloud or another project
	 * @throws \OCP\RichObjectStrings\InvalidObjectExeption
	 * @since 28.0.0
	 * @since 28.0.2 Optional parameter ?array $descriptionParameters
	 * @since 28.0.2 throws \OCP\RichObjectStrings\InvalidObjectExeption
	 */
	public static function success(?string $description = null, ?string $linkToDoc = null, ?array $descriptionParameters = null): self {
		return new self(self::SUCCESS, $description, $descriptionParameters, $linkToDoc);
	}

	/**
	 * @brief Create an info result object
	 * @param ?string $description Translated detailed description to display to the user
	 * @param ?string $linkToDoc URI of related relevent documentation, be it from Nextcloud or another project
	 * @throws \OCP\RichObjectStrings\InvalidObjectExeption
	 * @since 28.0.0
	 * @since 28.0.2 Optional parameter ?array $descriptionParameters
	 * @since 28.0.2 throws \OCP\RichObjectStrings\InvalidObjectExeption
	 */
	public static function info(?string $description = null, ?string $linkToDoc = null, ?array $descriptionParameters = null): self {
		return new self(self::INFO, $description, $descriptionParameters, $linkToDoc);
	}

	/**
	 * @brief Create a warning result object
	 * @param ?string $description Translated detailed description to display to the user
	 * @param ?string $linkToDoc URI of related relevent documentation, be it from Nextcloud or another project
	 * @throws \OCP\RichObjectStrings\InvalidObjectExeption
	 * @since 28.0.0
	 * @since 28.0.2 Optional parameter ?array $descriptionParameters
	 * @since 28.0.2 throws \OCP\RichObjectStrings\InvalidObjectExeption
	 */
	public static function warning(?string $description = null, ?string $linkToDoc = null, ?array $descriptionParameters = null): self {
		return new self(self::WARNING, $description, $descriptionParameters, $linkToDoc);
	}

	/**
	 * @brief Create an error result object
	 * @param ?string $description Translated detailed description to display to the user
	 * @param ?string $linkToDoc URI of related relevent documentation, be it from Nextcloud or another project
	 * @throws \OCP\RichObjectStrings\InvalidObjectExeption
	 * @since 28.0.0
	 * @since 28.0.2 Optional parameter ?array $descriptionParameters
	 * @since 28.0.2 throws \OCP\RichObjectStrings\InvalidObjectExeption
	 */
	public static function error(?string $description = null, ?string $linkToDoc = null, ?array $descriptionParameters = null): self {
		return new self(self::ERROR, $description, $descriptionParameters, $linkToDoc);
	}

	/**
	 * @brief Get the severity for the setup check result
	 *
	 * @return self::SUCCESS|self::INFO|self::WARNING|self::ERROR
	 * @since 28.0.0
	 */
	public function getSeverity(): string {
		return $this->severity;
	}

	/**
	 * @brief Get the description for the setup check result
	 *
	 * @since 28.0.0
	 */
	public function getDescription(): ?string {
		return $this->description;
	}

	/**
	 * @brief Get the description parameters for the setup check result
	 *
	 * If this returns null, description must not be treated as rich text
	 *
	 * @since 28.0.2
	 */
	public function getDescriptionParameters(): ?array {
		return $this->descriptionParameters;
	}

	/**
	 * @brief Get the name for the setup check
	 *
	 * @since 28.0.0
	 */
	public function getName(): ?string {
		return $this->name;
	}

	/**
	 * @brief Set the name from the setup check
	 *
	 * @since 28.0.0
	 */
	public function setName(string $name): void {
		$this->name = $name;
	}

	/**
	 * @brief Get a link to the doc for the explanation.
	 *
	 * @since 28.0.0
	 */
	public function getLinkToDoc(): ?string {
		return $this->linkToDoc;
	}

	/**
	 * @brief Get an array representation of the result for API responses
	 *
	 * @since 28.0.0
	 */
	public function jsonSerialize(): array {
		return [
			'name' => $this->name,
			'severity' => $this->severity,
			'description' => $this->description,
			'descriptionParameters' => $this->descriptionParameters,
			'linkToDoc' => $this->linkToDoc,
		];
	}
}
