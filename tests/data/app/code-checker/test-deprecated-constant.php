<?php

/**
 * Class BadClass - creating an instance of a blacklisted class is not allowed
 */
class BadClass {
	public function test() {
		return \OCP\NamespaceName\ClassName::CONSTANT_NAME;
	}
}
