<?php

namespace OCA\BruteForceSettings\Tests\Settings;

use OCA\BruteForceSettings\Settings\IPWhitelist;
use OCP\AppFramework\Http\TemplateResponse;
use Test\TestCase;

class IPWhitelistTest extends TestCase {

	/** @var IPWhitelist */
	private $settings;

	public function setUp() {
		parent::setUp();

		$this->settings = new IPWhitelist();
	}

	public function testGetForm() {
		$expected = new TemplateResponse('bruteforcesettings', 'ipwhitelist');

		$this->assertEquals($expected, $this->settings->getForm());
	}

	public function testGetSection() {
		$this->assertSame('security', $this->settings->getSection());
	}

	public function testGetPriority() {
		$this->assertSame(50, $this->settings->getPriority());
	}
}
