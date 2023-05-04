<?php

/**
 * Class BadClass - creating an instance of a blacklisted class is not allowed
 */
class BadClass {
	public function foo() {
		$bar = new OC_AppConfig();
	}
}
