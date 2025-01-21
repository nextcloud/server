<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Console;

use OCP\IConfig;
use Symfony\Component\Console\Formatter\OutputFormatterInterface;
use Symfony\Component\Console\Formatter\OutputFormatterStyleInterface;

class TimestampFormatter implements OutputFormatterInterface {
	/** @var ?IConfig */
	protected $config;

	/** @var OutputFormatterInterface */
	protected $formatter;

	/**
	 * @param ?IConfig $config
	 * @param OutputFormatterInterface $formatter
	 */
	public function __construct(?IConfig $config, OutputFormatterInterface $formatter) {
		$this->config = $config;
		$this->formatter = $formatter;
	}

	/**
	 * Sets the decorated flag.
	 *
	 * @param bool $decorated Whether to decorate the messages or not
	 */
	public function setDecorated(bool $decorated) {
		$this->formatter->setDecorated($decorated);
	}

	/**
	 * Gets the decorated flag.
	 *
	 * @return bool true if the output will decorate messages, false otherwise
	 */
	public function isDecorated(): bool {
		return $this->formatter->isDecorated();
	}

	/**
	 * Sets a new style.
	 *
	 * @param string $name The style name
	 * @param OutputFormatterStyleInterface $style The style instance
	 */
	public function setStyle(string $name, OutputFormatterStyleInterface $style) {
		$this->formatter->setStyle($name, $style);
	}

	/**
	 * Checks if output formatter has style with specified name.
	 *
	 * @param string $name
	 * @return bool
	 */
	public function hasStyle(string $name): bool {
		return $this->formatter->hasStyle($name);
	}

	/**
	 * Gets style options from style with specified name.
	 *
	 * @param string $name
	 * @return OutputFormatterStyleInterface
	 * @throws \InvalidArgumentException When style isn't defined
	 */
	public function getStyle(string $name): OutputFormatterStyleInterface {
		return $this->formatter->getStyle($name);
	}

	/**
	 * Formats a message according to the given styles.
	 *
	 * @param string|null $message The message to style
	 * @return string|null The styled message, prepended with a timestamp using the
	 *                     log timezone and dateformat, e.g. "2015-06-23T17:24:37+02:00"
	 */
	public function format(?string $message): ?string {
		if (!$this->formatter->isDecorated()) {
			// Don't add anything to the output when we shouldn't
			return $this->formatter->format($message);
		}

		if ($this->config instanceof IConfig) {
			$timeZone = $this->config->getSystemValue('logtimezone', 'UTC');
			$timeZone = $timeZone !== null ? new \DateTimeZone($timeZone) : null;

			$time = new \DateTime('now', $timeZone);
			$timestampInfo = $time->format($this->config->getSystemValue('logdateformat', \DateTimeInterface::ATOM));
		} else {
			$time = new \DateTime('now');
			$timestampInfo = $time->format(\DateTimeInterface::ATOM);
		}

		return $timestampInfo . ' ' . $this->formatter->format($message);
	}
}
