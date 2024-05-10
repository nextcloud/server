<?php
/**
 * SPDX-FileCopyrightText: 2015 Christian Kampka <christian@kampka.net>
 * SPDX-License-Identifier: MIT
 */
namespace OC\Core\Command\Background;

class Cron extends Base {
	protected function getMode(): string {
		return 'cron';
	}
}
