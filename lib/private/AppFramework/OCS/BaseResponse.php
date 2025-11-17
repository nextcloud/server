<?php

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\AppFramework\OCS;

use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\Response;

/**
 * @psalm-import-type DataResponseType from DataResponse
 * @template S of Http::STATUS_*
 * @template-covariant T of DataResponseType
 * @template H of array<string, mixed>
 * @template-extends Response<Http::STATUS_*, array<string, mixed>>
 */
abstract class BaseResponse extends Response {
	/** @var array */
	protected $data;

	/**
	 * BaseResponse constructor.
	 *
	 * @param DataResponse<S, T, H> $dataResponse
	 */
	public function __construct(
		DataResponse $dataResponse,
		protected string $format = 'xml',
		protected ?string $statusMessage = null,
		protected ?int $itemsCount = null,
		protected ?int $itemsPerPage = null,
	) {
		parent::__construct();

		$this->data = $dataResponse->getData();

		$this->setHeaders($dataResponse->getHeaders());
		$this->setStatus($dataResponse->getStatus());
		$this->setETag($dataResponse->getETag());
		$this->setLastModified($dataResponse->getLastModified());
		$this->setCookies($dataResponse->getCookies());

		if ($dataResponse->isThrottled()) {
			$throttleMetadata = $dataResponse->getThrottleMetadata();
			$this->throttle($throttleMetadata);
		}

		if ($this->format === 'json') {
			$this->addHeader(
				'Content-Type', 'application/json; charset=utf-8'
			);
		} else {
			$this->addHeader(
				'Content-Type', 'application/xml; charset=utf-8'
			);
		}
	}

	/**
	 * @param array<string,string|int> $meta
	 * @return string
	 */
	protected function renderResult(array $meta): string {
		$status = $this->getStatus();
		if ($status === Http::STATUS_NO_CONTENT
			|| $status === Http::STATUS_NOT_MODIFIED
			|| ($status >= 100 && $status <= 199)) {
			// Those status codes are not supposed to have a body:
			// https://stackoverflow.com/q/8628725
			return '';
		}

		$response = [
			'ocs' => [
				'meta' => $meta,
				'data' => $this->data,
			],
		];

		if ($this->format === 'json') {
			return $this->toJson($response);
		}

		$writer = new \XMLWriter();
		$writer->openMemory();
		$writer->setIndent(true);
		$writer->startDocument();
		$this->toXML($response, $writer);
		$writer->endDocument();
		return $writer->outputMemory(true);
	}

	/**
	 * @psalm-taint-escape has_quotes
	 * @psalm-taint-escape html
	 */
	protected function toJson(array $array): string {
		return \json_encode($array, \JSON_HEX_TAG);
	}

	protected function toXML(array $array, \XMLWriter $writer): void {
		foreach ($array as $k => $v) {
			if ($k === '@attributes' && is_array($v)) {
				foreach ($v as $k2 => $v2) {
					$writer->writeAttribute($k2, $v2);
				}
				continue;
			}

			if (\is_string($k) && str_starts_with($k, '@')) {
				$writer->writeAttribute(substr($k, 1), $v);
				continue;
			}

			if (\is_numeric($k)) {
				$k = 'element';
			}

			if ($v instanceof \stdClass) {
				$v = [];
			}

			if ($k === '$comment') {
				$writer->writeComment($v);
			} elseif (\is_array($v)) {
				$writer->startElement($k);
				$this->toXML($v, $writer);
				$writer->endElement();
			} elseif ($v instanceof \JsonSerializable) {
				$writer->startElement($k);
				$this->toXML($v->jsonSerialize(), $writer);
				$writer->endElement();
			} elseif ($v === null) {
				$writer->writeElement($k);
			} else {
				$writer->writeElement($k, (string)$v);
			}
		}
	}

	public function getOCSStatus() {
		return parent::getStatus();
	}
}
