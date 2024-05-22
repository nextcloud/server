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
namespace OC\Mail\Provider;

use OCP\Mail\Provider\IAddress;
use OCP\Mail\Provider\IAttachment;

class Message implements \OCP\Mail\Provider\IMessage {

    protected array $data;

	public function __construct(

	) {
        $this->data = [];
	}

    public function setFrom(IAddress $value): self {
        $this->data['from'] = $value;
        return $this;
    }

    public function getFrom(): IAddress {
        return (isset($this->data['from'])) ? $this->data['from'] : null;
    }

    public function setReplyTo(IAddress $value): self {
        $this->data['replyTo'] = $value;
        return $this;
    }

    public function getReplyTo(): IAddress {
        return (isset($this->data['replyTo'])) ? $this->data['replyTo'] : null;
    }

    public function setTo(IAddress ...$value): self {
        if (isset($this->data['to']) && is_array($this->data['to'])) {
            $this->data['to'] = array_merge($this->data['to'], $value);
        } else {
            $this->data['to'] = $value;
        }
        return $this;
    }

    public function getTo(): array | null {
        return (isset($this->data['to'])) ? $this->data['to'] : null;
    }

    public function setCc(IAddress ...$value): self {
        if (isset($this->data['cc']) && is_array($this->data['cc'])) {
            $this->data['cc'] = array_merge($this->data['cc'], $value);
        } else {
            $this->data['cc'] = $value;
        }
        return $this;
    }

    public function getCc(): array | null {
        return (isset($this->data['cc'])) ? $this->data['cc'] : null;
    }

    public function setBcc(IAddress ...$value): self {
        if (isset($this->data['bcc']) && is_array($this->data['bcc'])) {
            $this->data['bcc'] = array_merge($this->data['bcc'], $value);
        } else {
            $this->data['bcc'] = $value;
        }
        return $this;
    }

    public function getBcc(): array | null {
        return (isset($this->data['bcc'])) ? $this->data['bcc'] : null;
    }

    public function setSubject(string $value): self {
        $this->data['subject'] = $value;
        return $this;
    }

    public function getSubject(): string | null {
        return (isset($this->data['subject'])) ? $this->data['subject'] : null;
    }

    public function setBody(string $value, bool $html = false): self {
        if ($html) {
            $this->data['bodyHtml'] = $value;
        } else {
            $this->data['bodyPlain'] = $value;
        }
        return $this;
    }

    public function getBody(): string | null {
        if (isset($this->data['bodyHtml'])) {
            return $this->data['bodyHtml'];
        } elseif (isset($this->data['bodyPlain'])) {
            return $this->data['bodyPlain'];
        }
        return null;
    }

    public function setBodyHtml(string $value): self {
        $this->data['bodyHtml'] = $value;
        return $this;
    }

    public function getBodyHtml(): string | null {
        return (isset($this->data['bodyHtml'])) ? $this->data['bodyHtml'] : null;
    }

    public function setBodyPlain(string $value): self {
        $this->data['bodyPlain'] = $value;
        return $this;
    }

    public function getBodyPlain(): string | null {
        return (isset($this->data['bodyPlain'])) ? $this->data['bodyPlain'] : null;
    }

    public function setAttachments(IAttachment ...$value): self {
        if (isset($this->data['attachments']) && is_array($this->data['attachments'])) {
            $this->data['attachments'] = array_merge($this->data['attachments'], $value);
        } else {
            $this->data['attachments'] = $value;
        }
        return $this;
    }

    public function getAttachments(): array | null {
        return (isset($this->data['attachments'])) ? $this->data['attachments'] : null;
    }
	
}
