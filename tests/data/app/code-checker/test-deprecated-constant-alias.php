<?php

use OCP\NamespaceName\ClassName as Constant;

/**
 * Class BadClass - creating an instance of a blacklisted class is not allowed
 */
class BadClass {
	public function test() {
		return Constant::CONSTANT_NAME;
	}
}
