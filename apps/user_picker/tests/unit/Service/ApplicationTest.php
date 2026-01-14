<?php

namespace OCA\UserPicker\Tests;

use OCA\UserPicker\AppInfo\Application;

class ApplicationTest extends \PHPUnit\Framework\TestCase {

	public function testDummy() {
		$app = new Application();
		$this->assertEquals('user_picker', $app::APP_ID);
	}
}
