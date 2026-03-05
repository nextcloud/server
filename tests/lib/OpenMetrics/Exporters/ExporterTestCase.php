<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\OpenMetrics\Exporters;

use OCP\OpenMetrics\IMetricFamily;
use Test\TestCase;

abstract class ExporterTestCase extends TestCase {
	protected IMetricFamily $exporter;
	/** @var IMetric[] */
	protected array $metrics;

	abstract protected function getExporter(): IMetricFamily;

	protected function setUp(): void {
		parent::setUp();
		$this->exporter = $this->getExporter();
		$this->metrics = iterator_to_array($this->exporter->metrics());
	}

	public function testNotEmptyData(): void {
		$this->assertNotEmpty($this->metrics);
	}

	public function testValidExporterName(): void {
		$exporterName = $this->exporter->name();
		$this->assertMatchesRegularExpression('/^[a-z_:][a-z0-9_:]*$/i', $exporterName, );

		$unit = $this->exporter->unit();
		if ($unit === '') {
			return;
		}
		// Unit name must follow metric name format
		$this->assertMatchesRegularExpression('/^[a-z_:][a-z0-9_:]*$/i', $unit);
		// Unit name must be a suffix in exporter name
		$this->assertMatchesRegularExpression(
			'/(^|_)' . $unit . '$/',
			$exporterName,
			'Metric name "' . $exporterName . '" must contains unit "' . $unit . '" as a suffix',
		);
	}

	public function testValidLabelKey(): void {
		$labelNames = [];
		foreach ($this->metrics as $metric) {
			foreach ($metric->labels as $label => $value) {
				$labelNames[$label] = $label;
			}
		}

		if (empty($labelNames)) {
			$this->expectNotToPerformAssertions();
			return;
		}
		foreach ($labelNames as $label) {
			$this->assertMatchesRegularExpression('/^[a-z_][a-z0-9_]*$/i', $label);
		}

	}

	protected function assertLabelsAre(array $expectedLabels): void {
		$foundLabels = [];
		foreach ($this->metrics as $metric) {
			$foundLabels[] = $metric->labels;
		}

		$this->assertSame($foundLabels, $expectedLabels);
	}
}
