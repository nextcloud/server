<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Robin Appelman <robin@icewind.nl>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 *
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 */
namespace OC\Command;

use OC\BackgroundJob\QueuedJob;
use Laravel\SerializableClosure\SerializableClosure as LaravelClosure;
use Opis\Closure\SerializableClosure as OpisClosure;

class ClosureJob extends QueuedJob {
	protected function run($serializedCallable) {
		$callable = unserialize($serializedCallable, [LaravelClosure::class, OpisClosure::class]);
		$callable = $callable->getClosure();
		if (is_callable($callable)) {
			$callable();
		} else {
			throw new \InvalidArgumentException('Invalid serialized callable');
		}
	}
}
