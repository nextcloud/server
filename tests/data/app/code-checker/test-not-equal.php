<?php

/**
 * Class BadClass - uses equal instead of identical operator
 */
class BadClass {
	public function foo() {
		if (true != false) {
		}
	}
}
