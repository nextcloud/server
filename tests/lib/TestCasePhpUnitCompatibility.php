<?php
/**
 * @copyright Copyright (c) 2016 Joas Schilling <coding@schilljs.com>
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

namespace Test;

/**
 * FIXME Remove this once phpunit 5 is the lowest supported version, by reverting:
 * https://github.com/nextcloud/server/pull/2137
 */
if (version_compare(\PHPUnit_Runner_Version::id(), '5.0.0', '>=')) {
	abstract class TestCasePhpUnitCompatibility extends TestCasePhpUnit5 {}
} else {
	abstract class TestCasePhpUnitCompatibility extends TestCasePhpUnit4 {}
}
