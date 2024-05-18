<?php
/**
 * @copyright Copyright (c) 2018 Arthur Schiwon <blizzz@arthur-schiwon.de>
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Johannes Ernst <jernst@indiecomputing.com>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */
namespace OCP\Log;

use Psr\Log\LoggerInterface;

/**
 * Interface ILogFactory
 *
 * @since 14.0.0
 */
interface ILogFactory {
	/**
	 * @param string $type - one of: file, errorlog, syslog, systemd
	 * @return IWriter
	 * @since 14.0.0
	 */
	public function get(string $type): IWriter;

	/**
	 * @param string $path
	 * @param string $type
	 * @param string $tag
	 * @return LoggerInterface
	 * @since 22.0.0 - Parameters $type and $tag were added in 24.0.0
	 */
	public function getCustomPsrLogger(string $path, string $type = 'file', string $tag = 'Nextcloud'): LoggerInterface;
}
