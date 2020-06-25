<?php
/**
 * @copyright Copyright (c) 2017 Lukas Reschke <lukas@statuscode.ch>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

$expected = <<<EOT
build/.phan/tests/SqlInjectionCheckerTest.php:23 SqlInjectionChecker Potential SQL injection detected - neither a parameter nor a string
build/.phan/tests/SqlInjectionCheckerTest.php:35 SqlInjectionChecker Potential SQL injection detected - neither a parameter nor a string
build/.phan/tests/SqlInjectionCheckerTest.php:37 SqlInjectionChecker Potential SQL injection detected - neither a parameter nor a string
build/.phan/tests/SqlInjectionCheckerTest.php:39 SqlInjectionChecker Potential SQL injection detected - neither a parameter nor a string
build/.phan/tests/SqlInjectionCheckerTest.php:41 SqlInjectionChecker Potential SQL injection detected - neither a parameter nor a string
build/.phan/tests/SqlInjectionCheckerTest.php:43 SqlInjectionChecker Potential SQL injection detected - neither a parameter nor a string
build/.phan/tests/SqlInjectionCheckerTest.php:54 SqlInjectionChecker Potential SQL injection detected - neither a parameter nor a string
build/.phan/tests/SqlInjectionCheckerTest.php:61 SqlInjectionChecker Potential SQL injection detected - method: no child method
build/.phan/tests/SqlInjectionCheckerTest.php:62 SqlInjectionChecker Potential SQL injection detected - method: no child method
build/.phan/tests/SqlInjectionCheckerTest.php:69 SqlInjectionChecker Potential SQL injection detected - method: no child method
build/.phan/tests/SqlInjectionCheckerTest.php:70 SqlInjectionChecker Potential SQL injection detected - method: no child method

EOT;

$result = shell_exec('php '. __DIR__ . '/../../lib/composer/phan/phan/phan -k build/.phan/config.php --include-analysis-file-list build/.phan/tests/* --directory build/.phan/tests/');

if($result !== $expected) {
	echo("Output of phan doesn't match expectation\n");
	echo("Expected: $expected\n");
	echo("Result: $result\n");
	exit(1);
}
