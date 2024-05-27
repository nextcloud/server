<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2024 Sebastian Krupinski <krupinski01@gmail.com>
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

/**
 * Mail Message Object
 *
 * This object is used to define a mail message that can be used to transfer data to a provider
 *
 * @since 30.0.0
 *
 */
class Message implements \OCP\Mail\Provider\IMessage {

	protected array $data;

	/**
	 * initialize the mail message object
	 *
	 * @since 30.0.0
	 *
	 * @param string|null $id          arbitrary unique text string identifying this message
	 */
	public function __construct(
		?string $id = null
	) {
		// initilize data store
		$this->data = [];
		// evaluate if id was passed otherwise generate a new id
		$this->data['id'] = isset($id) ? $id : bin2hex(random_bytes(16));
	}

	/**
	 * An arbitrary unique text string identifying this message
	 *
	 * @since 30.0.0
	 *
	 * @return string				id of this message
	 */
	public function id(): string {
		// return id of message
		return $this->data['id'];
	}

	/**
	 * sets the sender of this message
	 *
	 * @since 30.0.0
	 *
	 * @param IAddress $value		            sender's mail address object
	 *
	 * @return self                             return this object for command chaining
	 */
	public function setFrom(IAddress $value): self {
		// create or update field in data store with value
		$this->data['from'] = $value;
		// return this object for command chaining
		return $this;
	}

	/**
	 * gets the sender of this message
	 *
	 * @since 30.0.0
	 *
	 * @param IAddress|null                     sender's mail address object
	 */
	public function getFrom(): IAddress | null {
		// evaluate if data store field exists and return value(s) or null otherwise
		return (isset($this->data['from'])) ? $this->data['from'] : null;
	}

	/**
	 * sets the sender's reply to address of this message
	 *
	 * @since 30.0.0
	 *
	 * @param IAddress $value		            senders's reply to mail address object
	 *
	 * @return self                             return this object for command chaining
	 */
	public function setReplyTo(IAddress $value): self {
		// create or update field in data store with value
		$this->data['replyTo'] = $value;
		// return this object for command chaining
		return $this;
	}

	/**
	 * gets the sender's reply to address of this message
	 *
	 * @since 30.0.0
	 *
	 * @param IAddress|null                     sender's mail address object
	 */
	public function getReplyTo(): IAddress | null {
		// evaluate if data store field exists and return value(s) or null otherwise
		return (isset($this->data['replyTo'])) ? $this->data['replyTo'] : null;
	}

	/**
	 * sets the recipient(s) of this message
	 *
	 * @since 30.0.0
	 *
	 * @param IAddress ...$value		        collection of or one or more mail address objects
	 *
	 * @return self                             return this object for command chaining
	 */
	public function setTo(IAddress ...$value): self {
		// evaluate if data store field already exists and append values otherwise create field and store values
		if (isset($this->data['to']) && is_array($this->data['to'])) {
			$this->data['to'] = array_merge($this->data['to'], $value);
		} else {
			$this->data['to'] = $value;
		}
		// return this object for command chaining
		return $this;
	}

	/**
	 * gets the recipient(s) of this message
	 *
	 * @since 30.0.0
	 *
	 * @param array<int,IAddress>|null          collection of all recipient mail address objects
	 */
	public function getTo(): array | null {
		// evaluate if data store field exists and return value(s) or null otherwise
		return (isset($this->data['to'])) ? $this->data['to'] : null;
	}

	/**
	 * sets the copy to recipient(s) of this message
	 *
	 * @since 30.0.0
	 *
	 * @param IAddress ...$value		        collection of or one or more mail address objects
	 *
	 * @return self                             return this object for command chaining
	 */
	public function setCc(IAddress ...$value): self {
		// evaluate if data store field already exists and append values otherwise create field and store values
		if (isset($this->data['cc']) && is_array($this->data['cc'])) {
			$this->data['cc'] = array_merge($this->data['cc'], $value);
		} else {
			$this->data['cc'] = $value;
		}
		// return this object for command chaining
		return $this;
	}

	/**
	 * gets the copy to recipient(s) of this message
	 *
	 * @since 30.0.0
	 *
	 * @param array<int,IAddress>|null          collection of all copied recipient mail address objects
	 */
	public function getCc(): array | null {
		// evaluate if data store field exists and return value(s) or null otherwise
		return (isset($this->data['cc'])) ? $this->data['cc'] : null;
	}

	/**
	 * sets the blind copy to recipient(s) of this message
	 *
	 * @since 30.0.0
	 *
	 * @param IAddress ...$value		        collection of or one or more mail address objects
	 *
	 * @return self                             return this object for command chaining
	 */
	public function setBcc(IAddress ...$value): self {
		// evaluate if data store field already exists and append values otherwise create field and store values
		if (isset($this->data['bcc']) && is_array($this->data['bcc'])) {
			$this->data['bcc'] = array_merge($this->data['bcc'], $value);
		} else {
			$this->data['bcc'] = $value;
		}
		// return this object for command chaining
		return $this;
	}

	/**
	 * gets the blind copy to recipient(s) of this message
	 *
	 * @since 30.0.0
	 *
	 * @param array<int,IAddress>|null          collection of all blind copied recipient mail address objects
	 */
	public function getBcc(): array | null {
		// evaluate if data store field exists and return value(s) or null otherwise
		return (isset($this->data['bcc'])) ? $this->data['bcc'] : null;
	}

	/**
	 * sets the subject of this message
	 *
	 * @since 30.0.0
	 *
	 * @param string $value                     subject of mail message
	 *
	 * @return self                             return this object for command chaining
	 */
	public function setSubject(string $value): self {
		// create or update field in data store with value
		$this->data['subject'] = $value;
		// return this object for command chaining
		return $this;
	}

	/**
	 * gets the subject of this message
	 *
	 * @since 30.0.0
	 *
	 * @param string|null                       subject of message or null if one is not set
	 */
	public function getSubject(): string | null {
		// evaluate if data store field exists and return value(s) or null otherwise
		return (isset($this->data['subject'])) ? $this->data['subject'] : null;
	}

	/**
	 * sets the plain text or html body of this message
	 *
	 * @since 30.0.0
	 *
	 * @param string $value                     text or html body of message
	 * @param bool $html                        html flag - true for html
	 *
	 * @return self                             return this object for command chaining
	 */
	public function setBody(string $value, bool $html = false): self {
		// evaluate html flag and create or update appropriate field in data store with value
		if ($html) {
			$this->data['bodyHtml'] = $value;
		} else {
			$this->data['bodyPlain'] = $value;
		}
		// return this object for command chaining
		return $this;
	}

	/**
	 * gets either the html or plain text body of this message
	 *
	 * html body will be returned over plain text if html body exists
	 *
	 * @since 30.0.0
	 *
	 * @param string|null                       html/plain body of this message or null if one is not set
	 */
	public function getBody(): string | null {
		// evaluate if data store field(s) exists and return value
		if (isset($this->data['bodyHtml'])) {
			return $this->data['bodyHtml'];
		} elseif (isset($this->data['bodyPlain'])) {
			return $this->data['bodyPlain'];
		}
		// return null if data fields did not exist in data store
		return null;
	}

	/**
	 * sets the html body of this message
	 *
	 * @since 30.0.0
	 *
	 * @param string $value                     html body of message
	 *
	 * @return self                             return this object for command chaining
	 */
	public function setBodyHtml(string $value): self {
		// create or update field in data store with value
		$this->data['bodyHtml'] = $value;
		// return this object for command chaining
		return $this;
	}

	/**
	 * gets the html body of this message
	 *
	 * @since 30.0.0
	 *
	 * @param string|null                       html body of this message or null if one is not set
	 */
	public function getBodyHtml(): string | null {
		// evaluate if data store field exists and return value(s) or null otherwise
		return (isset($this->data['bodyHtml'])) ? $this->data['bodyHtml'] : null;
	}

	/**
	 * sets the plain text body of this message
	 *
	 * @since 30.0.0
	 *
	 * @param string $value         plain text body of message
	 *
	 * @return self                 return this object for command chaining
	 */
	public function setBodyPlain(string $value): self {
		// create or update field in data store with value
		$this->data['bodyPlain'] = $value;
		// return this object for command chaining
		return $this;
	}

	/**
	 * gets the plain text body of this message
	 *
	 * @since 30.0.0
	 *
	 * @param string|null                       plain text body of this message or null if one is not set
	 */
	public function getBodyPlain(): string | null {
		// evaluate if data store field exists and return value(s) or null otherwise
		return (isset($this->data['bodyPlain'])) ? $this->data['bodyPlain'] : null;
	}

	/**
	 * sets the attachments of this message
	 *
	 * @since 30.0.0
	 *
	 * @param IAttachment ...$value                     collection of or one or more mail attachment objects
	 *
	 * @return self                                     return this object for command chaining
	 */
	public function setAttachments(IAttachment ...$value): self {
		// evaluate if data store field already exists and append values otherwise create field and store values
		if (isset($this->data['attachments']) && is_array($this->data['attachments'])) {
			$this->data['attachments'] = array_merge($this->data['attachments'], $value);
		} else {
			$this->data['attachments'] = $value;
		}
		// return this object for command chaining
		return $this;
	}

	/**
	 * gets the attachments of this message
	 *
	 * @since 30.0.0
	 *
	 * @param array<int,IAttachment>		collection of all mail attachment objects
	 */
	public function getAttachments(): array | null {
		// evaluate if data store field exists and return value(s) or null otherwise
		return (isset($this->data['attachments'])) ? $this->data['attachments'] : null;
	}

}
