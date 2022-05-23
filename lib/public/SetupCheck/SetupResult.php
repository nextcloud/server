<?php

namespace OCP\SetupCheck;

/**
 * @brief This class is used for storing the result of a setup check
 *
 * @since 25.0.0
 */
class SetupResult implements \JsonSerializable {
	const SUCCESS = 'success';
	const INFO = 'info';
	const WARNING = 'warning';
	const ERROR = 'error';

	private string $severity;
	private ?string $description;
	private ?string $linkToDoc;

	/**
	 * @psalm-param self::SUCCESS|self::INFO|self::WARNING|self::ERROR $severity
	 * @since 25.0.0
	 */
	public function __construct(string $severity, ?string $description = null, ?string $linkToDoc = null) {
		$this->severity = $severity;
		$this->description = $description;
		$this->linkToDoc = $linkToDoc;
	}

	/**
	 * @brief Get the severity for the setup check result
	 *
	 * @psalm-return self::INFO|self::WARNING|self::ERROR
	 * @since 25.0.0
	 */
	public function getSeverity(): string {
		return $this->severity;
	}

	/**
	 * @brief Get the description for the setup check result
	 *
	 * @since 25.0.0
	 */
	public function getDescription(): ?string {
		return $this->description;
	}

	/**
	 * @brief Get a link to the doc for the explanation.
	 *
	 * @since 25.0.0
	 */
	public function getLinkToDoc(): ?string {
		return $this->linkToDoc;
	}

	#[\ReturnTypeWillChange]
	function jsonSerialize() {
		return [
			'severity' => $this->severity,
			'description' => $this->description,
			'linkToDoc' => $this->linkToDoc,
		];
	}
}
