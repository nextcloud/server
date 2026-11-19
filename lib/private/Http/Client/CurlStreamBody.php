<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Http\Client;

use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Handler\CurlMultiHandler;
use GuzzleHttp\Promise\Is;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Psr7\Utils;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamInterface;

/**
 * Response body that downloads the data of a cURL transfer while it is read
 *
 * cURL appends the data to $sink, a php://temp stream, so the body stays
 * seekable. Only the time spent waiting for the server counts towards the
 * timeout, not the time the reader spends between reads. Closing the body
 * cancels the transfer; clones share the transfer but never end it.
 */
class CurlStreamBody implements StreamInterface {
	private int $position = 0;
	private float $waitedSeconds = 0;
	private ?ConnectException $timeoutException = null;
	private bool $failureThrown = false;
	private bool $ownsTransfer = true;

	public function __construct(
		private RequestInterface $request,
		private CurlMultiHandler $curl,
		private PromiseInterface $transfer,
		private StreamInterface $sink,
		private float $timeout,
	) {
	}

	public function __destruct() {
		// The sink of a finished transfer can be the body of an exception's response
		if ($this->ownsTransfer && Is::pending($this->transfer)) {
			$this->close();
		}
	}

	public function __clone() {
		$this->ownsTransfer = false;
	}

	/**
	 * Runs the transfer until more than $size bytes have arrived or the transfer has finished
	 *
	 * @throws ConnectException if the time spent waiting exceeds the timeout
	 */
	public function waitFor(int $size): void {
		// cURL writes at the current position of the sink
		$this->sink->seek(0, SEEK_END);
		while ($this->downloaded() <= $size && Is::pending($this->transfer)) {
			if ($this->timeout > 0 && $this->waitedSeconds >= $this->timeout) {
				$error = sprintf('Operation timed out after %d milliseconds', $this->waitedSeconds * 1000);
				$url = (string)Utils::redactUserInfo($this->request->getUri());
				$this->timeoutException = new ConnectException(
					sprintf('cURL error %d: %s (see https://curl.se/libcurl/c/libcurl-errors.html) for %s', CURLE_OPERATION_TIMEDOUT, $error, $url),
					$this->request,
					null,
					['errno' => CURLE_OPERATION_TIMEDOUT, 'error' => $error, 'url' => $url],
				);
				$this->transfer->cancel();
				throw $this->timeoutException;
			}

			$start = hrtime(true);
			$this->curl->tick();
			$this->waitedSeconds += (hrtime(true) - $start) / 1e9;
		}
	}

	/**
	 * Returns the error of the finished transfer, or null while it runs or if it succeeded
	 */
	public function failure(): ?\Throwable {
		if ($this->timeoutException !== null) {
			return $this->timeoutException;
		}
		if (Is::pending($this->transfer)) {
			return null;
		}

		try {
			$this->transfer->wait();
			return null;
		} catch (\Throwable $e) {
			return $e;
		}
	}

	private function succeeded(): bool {
		return !Is::pending($this->transfer) && $this->failure() === null;
	}

	private function throwFailure(): void {
		$failure = $this->failure();
		if ($failure !== null) {
			$this->failureThrown = true;
			throw $failure;
		}
	}

	private function downloaded(): int {
		return (int)$this->sink->getSize();
	}

	#[\Override]
	public function read(int $length): string {
		$this->waitFor($this->position);
		if ($this->downloaded() <= $this->position) {
			$this->throwFailure();
			return '';
		}

		$this->sink->seek($this->position);
		$data = $this->sink->read($length);
		$this->position += strlen($data);
		return $data;
	}

	#[\Override]
	public function eof(): bool {
		// A failed transfer ends once its error was thrown, so readers checking eof() still get it
		return $this->position >= $this->downloaded()
			&& !Is::pending($this->transfer)
			&& ($this->failureThrown || $this->failure() === null);
	}

	#[\Override]
	public function getContents(): string {
		return Utils::copyToString($this);
	}

	#[\Override]
	public function __toString(): string {
		$this->rewind();
		return $this->getContents();
	}

	#[\Override]
	public function close(): void {
		if ($this->ownsTransfer) {
			$this->transfer->cancel();
			$this->sink->close();
		}
	}

	#[\Override]
	public function detach() {
		$this->close();
		return null;
	}

	#[\Override]
	public function getSize(): ?int {
		return $this->succeeded() ? $this->downloaded() : null;
	}

	#[\Override]
	public function tell(): int {
		return $this->position;
	}

	#[\Override]
	public function isSeekable(): bool {
		return true;
	}

	#[\Override]
	public function seek(int $offset, int $whence = SEEK_SET): void {
		if ($whence === SEEK_END) {
			$this->waitFor(PHP_INT_MAX);
			$this->throwFailure();
			$position = $this->downloaded() + $offset;
		} elseif ($whence === SEEK_CUR) {
			$position = $this->position + $offset;
		} elseif ($whence === SEEK_SET) {
			$position = $offset;
		} else {
			throw new \InvalidArgumentException('Invalid whence');
		}

		if ($position < 0) {
			throw new \RuntimeException('Unable to seek to stream position ' . $position);
		}
		$this->position = $position;
	}

	#[\Override]
	public function rewind(): void {
		$this->seek(0);
	}

	#[\Override]
	public function isWritable(): bool {
		return false;
	}

	#[\Override]
	public function write(string $string): int {
		throw new \RuntimeException('Stream is not writable');
	}

	#[\Override]
	public function isReadable(): bool {
		return true;
	}

	#[\Override]
	public function getMetadata(?string $key = null) {
		return $key === null ? [] : null;
	}
}
