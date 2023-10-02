<?php

namespace OCP\SetupCheck;

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
	 * @param self::SUCCESS|self::INFO|self::WARNING|self::ERROR $severity
	 * @since 28.0.0
	 */
	public function __construct(
		private string $severity,
		private ?string $description = null,
		private ?string $linkToDoc = null,
	) {
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
			'severity' => $this->severity,
			'description' => $this->description,
			'linkToDoc' => $this->linkToDoc,
		];
	}
}
