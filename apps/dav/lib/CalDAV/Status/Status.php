<?php
/*
 * *
 *  * Dav App
 *  *
 *  * @copyright 2023 Anna Larch <anna.larch@gmx.net>
 *  *
 *  * @author Anna Larch <anna.larch@gmx.net>
 *  *
 *  * This library is free software; you can redistribute it and/or
 *  * modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
 *  * License as published by the Free Software Foundation; either
 *  * version 3 of the License, or any later version.
 *  *
 *  * This library is distributed in the hope that it will be useful,
 *  * but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  * GNU AFFERO GENERAL PUBLIC LICENSE for more details.
 *  *
 *  * You should have received a copy of the GNU Affero General Public
 *  * License along with this library.  If not, see <http://www.gnu.org/licenses/>.
 *  *
 *
 */

namespace OCA\DAV\CalDAV\Status;

class Status {

	public function __construct(private string $status = '', private ?string $message = null, private ?string $customMessage = null){}

	public function getStatus(): string {
		return $this->status;
	}

	public function setStatus(string $status): void {
		$this->status = $status;
	}

	public function getMessage(): ?string {
		return $this->message;
	}

	public function setMessage(?string $message): void {
		$this->message = $message;
	}

	public function getCustomMessage(): ?string {
		return $this->customMessage;
	}

	public function setCustomMessage(?string $customMessage): void {
		$this->customMessage = $customMessage;
	}


}
