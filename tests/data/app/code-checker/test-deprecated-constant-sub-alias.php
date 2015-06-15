<?php

use OCP\NamespaceName as Constant;

/**
 * Class BadClass - creating an instance of a blacklisted class is not allowed
 */
class BadClass {
	public function test() {
		return Constant\ClassName::CONSTANT_NAME;
	}
}
