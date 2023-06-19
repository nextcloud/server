<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2017 Joas Schilling <coding@schilljs.com>
 *
 * @author Joas Schilling <coding@schilljs.com>
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
namespace OCP\Mail;

/**
 * Interface IMessage
 *
 * @since 13.0.0
 */
interface IMessage {
	/**
	 * Set the subject of this message
	 *
	 * @param string $subject
	 *
	 * @return self
	 * @since 28.0.0
	 */
	public function setSubject(string $subject): IMessage;

	/**
	 * Set the plain-text body of this message
	 *
	 * @param string $body
	 *
	 * @return self
	 * @since 28.0.0
	 */
	public function setPlainBody(string $body): IMessage;

	/**
	 * Set the HTML body of this message. Consider also sending a plain-text body instead of only an HTML one.
	 *
	 * @param string $body
	 *
	 * @return self
	 * @since 28.0.0
	 */
	public function setHtmlBody(string $body): IMessage;

	/**
	 * @param IAttachment $attachment
	 * @return IMessage
	 * @since 13.0.0
	 */
	public function attach(IAttachment $attachment): IMessage;

	/**
	 * Can be used to "attach content inline" as message parts with specific MIME type and encoding.
	 *
	 * @param string $body body of the MIME part
	 * @param string $name the file name
	 * @param string|null $contentType MIME Content-Type (e.g. text/plain or text/calendar)
	 *
	 * @return IMessage
	 * @since 27.0.0
	 */
	public function attachInline(string $body, string $name, string $contentType = null): IMessage;

	/**
	 * Set the from address of this message.
	 *
	 * If no "From" address is used \OC\Mail\Mailer will use mail_from_address and mail_domain from config.php
	 *
	 * @param array $addresses Example: array('sender@domain.org', 'other@domain.org' => 'A name')
	 * @return IMessage
	 * @since 13.0.0
	 */
	public function setFrom(array $addresses): IMessage;

	/**
	 * Set the Reply-To address of this message
	 *
	 * @param array $addresses
	 * @return IMessage
	 * @since 13.0.0
	 */
	public function setReplyTo(array $addresses): IMessage;

	/**
	 * Set the to addresses of this message.
	 *
	 * @param array $recipients Example: array('recipient@domain.org', 'other@domain.org' => 'A name')
	 * @return IMessage
	 * @since 13.0.0
	 */
	public function setTo(array $recipients): IMessage;

	/**
	 * Set the CC recipients of this message.
	 *
	 * @param array $recipients Example: array('recipient@domain.org', 'other@domain.org' => 'A name')
	 * @return IMessage
	 * @since 13.0.0
	 */
	public function setCc(array $recipients): IMessage;

	/**
	 * Set the BCC recipients of this message.
	 *
	 * @param array $recipients Example: array('recipient@domain.org', 'other@domain.org' => 'A name')
	 * @return IMessage
	 * @since 13.0.0
	 */
	public function setBcc(array $recipients): IMessage;

	/**
	 * @param IEMailTemplate $emailTemplate
	 * @return IMessage
	 * @since 13.0.0
	 */
	public function useTemplate(IEMailTemplate $emailTemplate): IMessage;

	/**
	 * Add the Auto-Submitted header to the email, preventing most automated
	 * responses to automated messages.
	 *
	 * @param Headers\AutoSubmitted::VALUE_* $value (one of AutoSubmitted::VALUE_NO, AutoSubmitted::VALUE_AUTO_GENERATED, AutoSubmitted::VALUE_AUTO_REPLIED)
	 * @return IMessage
	 * @since 26.0.0
	 */
	public function setAutoSubmitted(string $value): IMessage;
}
