<?php

declare(strict_types=1);

/**
 *  The MIT License (MIT)
 *
 * Copyright (c) 2014 Christian Kampka <christian@kampka.net>
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

namespace OC\Log;

use OC\SystemConfig;
use OCP\Log\IWriter;

class Errorlog extends LogDetails implements IWriter {
	public function __construct(
		SystemConfig $config,
		protected string $tag = 'nextcloud',
	) {
		parent::__construct($config);
	}

	/**
	 * Write a message in the log
	 *
	 * @param string|array $message
	 */
	public function write(string $app, $message, int $level): void {
		error_log('[' . $this->tag . ']['.$app.']['.$level.'] '.$this->logDetailsAsJSON($app, $message, $level));
	}
}
