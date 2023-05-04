<?php

/**
 * Class GoodClass - uses identical operator
 */
class GoodClass {
	public function foo() {
		if (true === false) {
		}
		if (true !== false) {
		}
	}
}
