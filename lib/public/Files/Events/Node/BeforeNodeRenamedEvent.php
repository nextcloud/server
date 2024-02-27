<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2020, Roeland Jago Douma <roeland@famdouma.nl>
 *
 * @author Roeland Jago Douma <roeland@famdouma.nl>
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
namespace OCP\Files\Events\Node;

use Exception;
use OCP\Files\Node;

/**
 * @since 20.0.0
 */
class BeforeNodeRenamedEvent extends AbstractNodesEvent {
	/**
	 * @since 20.0.0
	 */
	public function __construct(Node $source, Node $target, private bool &$run) {
		parent::__construct($source, $target);
	}

	/**
	 * @since 28.0.0
	 * @return never
	 */
	public function abortOperation(\Throwable $ex = null) {
		$this->stopPropagation();
		$this->run = false;
		if ($ex !== null) {
			throw $ex;
		} else {
			throw new Exception('Operation aborted');
		}
	}
}
