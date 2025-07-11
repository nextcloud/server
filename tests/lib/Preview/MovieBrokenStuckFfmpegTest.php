<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Preview;

/**
 * Class MovieTest
 *
 * @group DB
 *
 * @package Test\Preview
 */
class MovieBrokenStuckFfmpegTest extends MovieTest {
	protected string $fileName = 'broken-video.webm';
}
