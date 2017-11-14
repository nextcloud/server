<?php

/**
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
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

namespace OC\Support\CrashReport;

use Exception;
use OCP\Support\CrashReport\IRegistry;
use OCP\Support\CrashReport\IReporter;
use Throwable;

class Registry implements IRegistry {

	/** @var array<IReporter> */
	private $reporters = [];

	/**
	 * Register a reporter instance
	 *
	 * @param IReporter $reporter
	 */
	public function register(IReporter $reporter) {
		$this->reporters[] = $reporter;
	}

	/**
	 * Delegate crash reporting to all registered reporters
	 *
	 * @param Exception|Throwable $exception
	 * @param array $context
	 */
	public function delegateReport($exception, array $context = []) {
		/** @var IReporter $reporter */
		foreach ($this->reporters as $reporter) {
			$reporter->report($exception, $context);
		}
	}

}
