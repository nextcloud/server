<?php
declare(strict_types=1);

/**
* @copyright Copyright (c) 2023 Sebastian Krupinski <krupinski01@gmail.com>
*
* @author Sebastian Krupinski <krupinski01@gmail.com>
*
* @license AGPL-3.0-or-later
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
namespace OCP\Mail\Provider;

use OCP\Mail\Provider\IAddress;
use OCP\Mail\Provider\IAttachment;

/**
 * Interface IMessage
 *
 * @since 30.0.0
 */
interface IMessage {
	
	public function setFrom(IAddress $value): self;

	public function getFrom(): IAddress;

	public function setReplyTo(IAddress $value): self;

	public function getReplyTo(): IAddress;

	public function setTo(IAddress ...$value): self;

	public function getTo(): array | null;

	public function setCc(IAddress ...$value): self;

	public function getCc(): array | null;

	public function setBcc(IAddress ...$value): self;
	
	public function getBcc(): array | null;

	public function setSubject(string $value): self;

	public function setBody(string $value, bool $html): self;

	public function setBodyPlain(string $value): self;

	public function setBodyHtml(string $value): self;

	public function setAttachments(IAttachment ...$value): self;

    public function getAttachments(): array | null;
}
