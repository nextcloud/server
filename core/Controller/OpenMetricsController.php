<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Core\Controller;

use OC\OpenMetrics\ExporterManager;
use OC\Security\Ip\Address;
use OC\Security\Ip\Range;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\FrontpageRoute;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\Attribute\PublicPage;
use OCP\IConfig;
use OCP\IRequest;
use OCP\OpenMetrics\IMetricFamily;
use OCP\OpenMetrics\Metric;
use OCP\OpenMetrics\MetricType;
use OCP\OpenMetrics\MetricValue;
use Psr\Log\LoggerInterface;

/**
 * OpenMetrics controller
 *
 * Gather and display metrics
 *
 * @package OC\Core\Controller
 */
class OpenMetricsController extends Controller {
	public function __construct(
		string $appName,
		IRequest $request,
		private IConfig $config,
		private ExporterManager $exporterManager,
		private LoggerInterface $logger,
	) {
		parent::__construct($appName, $request);
	}

	#[NoCSRFRequired]
	#[PublicPage]
	#[FrontpageRoute(verb: 'GET', url: '/metrics')]
	public function export(): Http\Response {
		if (!$this->isRemoteAddressAllowed()) {
			return new Http\Response(Http::STATUS_FORBIDDEN);
		}

		return new Http\StreamTraversableResponse(
			$this->generate(),
			Http::STATUS_OK,
			[
				'Content-Type' => 'application/openmetrics-text; version=1.0.0; charset=utf-8',
			]
		);
	}

	private function isRemoteAddressAllowed(): bool {
		$clientAddress = new Address($this->request->getRemoteAddress());
		$allowedRanges = $this->config->getSystemValue('openmetrics_allowed_clients', ['127.0.0.0/16', '::1/128']);
		if (!is_array($allowedRanges)) {
			$this->logger->warning('Invalid configuration for "openmetrics_allowed_clients"');
			return false;
		}

		foreach ($allowedRanges as $range) {
			$range = new Range($range);
			if ($range->contains($clientAddress)) {
				return true;
			}
		}

		return false;
	}

	private function generate(): \Generator {
		foreach ($this->exporterManager->export() as $family) {
			yield $this->formatFamily($family);
		}

		$elapsed = (string)(microtime(true) - $_SERVER['REQUEST_TIME_FLOAT']);
		yield <<<SUMMARY
			# TYPE nextcloud_exporter_duration gauge
			# UNIT nextcloud_exporter_duration seconds
			# HELP nextcloud_exporter_duration Exporter run time
			nextcloud_exporter_duration $elapsed
			# EOF

			SUMMARY;
	}

	private function formatFamily(IMetricFamily $family): string {
		$output = '';
		$name = $family->name();
		if ($family->type() !== MetricType::unknown) {
			$output = '# TYPE nextcloud_' . $name . ' ' . $family->type()->name . "\n";
		}
		if ($family->unit() !== '') {
			$output .= '# UNIT nextcloud_' . $name . ' ' . $family->unit() . "\n";
		}
		if ($family->help() !== '') {
			$output .= '# HELP nextcloud_' . $name . ' ' . $family->help() . "\n";
		}
		foreach ($family->metrics() as $metric) {
			$output .= 'nextcloud_' . $name . $this->formatLabels($metric) . ' ' . $this->formatValue($metric);
			if ($metric->timestamp !== null) {
				$output .= ' ' . $this->formatTimestamp($metric);
			}
			$output .= "\n";
		}

		return $output;
	}

	private function formatLabels(Metric $metric): string {
		if (empty($metric->labels)) {
			return '';
		}

		$labels = [];
		foreach ($metric->labels as $label => $value) {
			$labels[] .= $label . '=' . $this->escapeString((string)$value);
		}

		return '{' . implode(',', $labels) . '}';
	}

	private function escapeString(string $string): string {
		return json_encode(
			$string,
			JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR,
			1
		);
	}

	private function formatValue(Metric $metric): string {
		if (is_bool($metric->value)) {
			return $metric->value ? '1' : '0';
		}
		if ($metric->value instanceof MetricValue) {
			return $metric->value->value;
		}

		return (string)$metric->value;
	}

	private function formatTimestamp(Metric $metric): string {
		return (string)$metric->timestamp;
	}
}
