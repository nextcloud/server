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
 * Mail Message Interface
 * 
 * This interface is a base requirement of methods and functionality used to construct a mail message object
 * 
 * @since 30.0.0
 */
interface IMessage {
	
	/**
	 * An arbitrary unique text string identifying this message
	 * 
	 * @since 30.0.0
	 * @return string				id of this message 
	 */
	public function id(): string;

	/**
	 * sets the sender of this message
	 * 
	 * @since 30.0.0
	 * @param IAddress $value		            sender's mail address object
     * @return self                             return this object for command chaining
	 */
	public function setFrom(IAddress $value): self;

	/**
	 * gets the sender of this message
	 * 
	 * @since 30.0.0
	 * @param IAddress|null                     sender's mail address object
	 */
	public function getFrom(): IAddress;

	/**
	 * sets the sender's reply to address of this message
	 * 
	 * @since 30.0.0
	 * @param IAddress $value		            senders's reply to mail address object
     * @return self                             return this object for command chaining
	 */
	public function setReplyTo(IAddress $value): self;

	/**
	 * gets the sender's reply to address of this message
	 * 
	 * @since 30.0.0
	 * @param IAddress|null                     sender's mail address object
	 */
	public function getReplyTo(): IAddress;

	/**
	 * sets the recipient(s) of this message
	 * 
	 * @since 30.0.0
	 * @param IAddress ...$value				collection of or one or more mail address objects
     * @return self                             return this object for command chaining
	 */
	public function setTo(IAddress ...$value): self;

	/**
	 * gets the recipient(s) of this message
	 * 
	 * @since 30.0.0
	 * @param array<int,IAddress>|null			collection of all recipient mail address objects
	 */
	public function getTo(): array | null;

	/**
	 * sets the copy to recipient(s) of this message
	 * 
	 * @since 30.0.0
	 * @param IAddress ...$value				collection of or one or more mail address objects
     * @return self                             return this object for command chaining
	 */
	public function setCc(IAddress ...$value): self;

	/**
	 * gets the copy to recipient(s) of this message
	 * 
	 * @since 30.0.0
	 * @param array<int,IAddress>|null			collection of all copied recipient mail address objects
	 */
	public function getCc(): array | null;

	/**
	 * sets the blind copy to recipient(s) of this message
	 * 
	 * @since 30.0.0
	 * @param IAddress ...$value				collection of or one or more mail address objects
     * @return self                             return this object for command chaining
	 */
	public function setBcc(IAddress ...$value): self;
	
	/**
	 * gets the blind copy to recipient(s) of this message
	 * 
	 * @since 30.0.0
	 * @param array<int,IAddress>|null			collection of all blind copied recipient mail address objects
	 */
	public function getBcc(): array | null;

	/**
	 * sets the subject of this message
	 * 
	 * @since 30.0.0
	 * @param string $value                     subject of mail message
     * @return self                             return this object for command chaining
	 */
	public function setSubject(string $value): self;

	/**
	 * gets the subject of this message
	 * 
	 * @since 30.0.0
	 * @param string|null                       subject of message or null if one is not set
	 */
	public function getSubject(): string | null;

	/**
	 * sets the plain text or html body of this message
	 * 
	 * @since 30.0.0
	 * @param string $value                     text or html body of message
     * @param bool $html						html flag - true for html
     * @return self                             return this object for command chaining
	 */
	public function setBody(string $value, bool $html): self;

	/**
	 * gets either the html or plain text body of this message
     * 
     * html body will be returned over plain text if html body exists 
	 * 
	 * @since 30.0.0
	 * @param string|null                       html/plain body of this message or null if one is not set
	 */
	public function getBody(): string | null;

	/**
	 * sets the html body of this message
	 * 
	 * @since 30.0.0
	 * @param string $value                     html body of message
     * @return self                             return this object for command chaining
	 */
	public function setBodyHtml(string $value): self;

	/**
	 * gets the html body of this message
	 * 
	 * @since 30.0.0
	 * @param string|null                       html body of this message or null if one is not set
	 */
	public function getBodyHtml(): string | null;

	/**
	 * sets the plain text body of this message
	 * 
	 * @since 30.0.0
	 * @param string $value         			plain text body of message
     * @return self                 			return this object for command chaining
	 */
	public function setBodyPlain(string $value): self;

	/**
	 * gets the plain text body of this message
	 * 
	 * @since 30.0.0
	 * @param string|null                       plain text body of this message or null if one is not set
	 */
	public function getBodyPlain(): string | null;

	/**
	 * sets the attachments of this message
	 * 
	 * @since 30.0.0
	 * @param IAttachment ...$value				collection of or one or more mail attachment objects
     * @return self                             return this object for command chaining
	 */
	public function setAttachments(IAttachment ...$value): self;

	/**
	 * gets the attachments of this message
	 * 
	 * @since 30.0.0
	 * @param array<int,IAttachment>			collection of all mail attachment objects
	 */
    public function getAttachments(): array | null;
}
