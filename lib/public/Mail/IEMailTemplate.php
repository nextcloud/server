<?php
/**
 * @copyright 2017, Morris Jobke <hey@morrisjobke.de>
 *
 * @author Joas Schilling <coding@schilljs.com>
 * @author Leon Klingele <leon@struktur.de>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Morris Jobke <hey@morrisjobke.de>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCP\Mail;

/**
 * Interface IEMailTemplate
 *
 * Interface to a class that allows to build HTML emails
 *
 * Example:
 *
 * <?php
 *
 * $emailTemplate = new EMailTemplate($this->defaults, $this->urlGenerator, $this->l10n);
 *
 * $emailTemplate->addHeader();
 * $emailTemplate->addHeading('Welcome aboard');
 * $emailTemplate->addBodyText('Welcome to your Nextcloud account, you can add, protect, and share your data.');
 *
 * $emailTemplate->addBodyButtonGroup(
 *     'Set your password', 'https://example.org/resetPassword/q1234567890qwertz',
 *     'Install Client', 'https://nextcloud.com/install/#install-clients'
 * );
 *
 * $emailTemplate->addFooter('Optional footer text');
 *
 * $htmlContent = $emailTemplate->renderHtml();
 * $plainContent = $emailTemplate->renderText();
 *
 * @since 12.0.0
 */
interface IEMailTemplate {

	/**
	 * Sets the subject of the email
	 *
	 * @param string $subject
	 *
	 * @since 13.0.0
	 */
	public function setSubject($subject);

	/**
	 * Adds a header to the email
	 *
	 * @since 12.0.0
	 */
	public function addHeader();

	/**
	 * Adds a heading to the email
	 *
	 * @param string $title
	 * @param string|bool $plainTitle Title that is used in the plain text email
	 *   if empty the $title is used, if false none will be used
	 *
	 * @since 12.0.0
	 */
	public function addHeading($title, $plainTitle = '');

	/**
	 * Adds a paragraph to the body of the email
	 *
	 * @param string $text
	 * @param string|bool $plainText Text that is used in the plain text email
	 *   if empty the $text is used, if false none will be used
	 *
	 * @since 12.0.0
	 */
	public function addBodyText($text, $plainText = '');

	/**
	 * Adds a list item to the body of the email
	 *
	 * @param string $text
	 * @param string $metaInfo
	 * @param string $icon Absolute path, must be 16*16 pixels
	 * @param string $plainText Text that is used in the plain text email
	 *   if empty the $text is used, if false none will be used
	 * @param string $plainMetaInfo Meta info that is used in the plain text email
	 *   if empty the $metaInfo is used, if false none will be used
	 * @since 12.0.0
	 */
	public function addBodyListItem($text, $metaInfo = '', $icon = '', $plainText = '', $plainMetaInfo = '');

	/**
	 * Adds a button group of two buttons to the body of the email
	 *
	 * @param string $textLeft Text of left button
	 * @param string $urlLeft URL of left button
	 * @param string $textRight Text of right button
	 * @param string $urlRight URL of right button
	 * @param string $plainTextLeft Text of left button that is used in the plain text version - if empty the $textLeft is used
	 * @param string $plainTextRight Text of right button that is used in the plain text version - if empty the $textRight is used
	 *
	 * @since 12.0.0
	 */
	public function addBodyButtonGroup($textLeft, $urlLeft, $textRight, $urlRight, $plainTextLeft = '', $plainTextRight = '');

	/**
	 * Adds a button to the body of the email
	 *
	 * @param string $text Text of button
	 * @param string $url URL of button
	 * @param string $plainText Text of button in plain text version
	 * 		if empty the $text is used, if false none will be used
	 *
	 * @since 12.0.0
	 */
	public function addBodyButton($text, $url, $plainText = '');

	/**
	 * Adds a logo and a text to the footer. <br> in the text will be replaced by new lines in the plain text email
	 *
	 * @param string $text If the text is empty the default "Name - Slogan<br>This is an automatically sent email" will be used
	 *
	 * @since 12.0.0
	 */
	public function addFooter($text = '');

	/**
	 * Returns the rendered email subject as string
	 *
	 * @return string
	 *
	 * @since 13.0.0
	 */
	public function renderSubject();

	/**
	 * Returns the rendered HTML email as string
	 *
	 * @return string
	 *
	 * @since 12.0.0
	 */
	public function renderHtml();

	/**
	 * Returns the rendered plain text email as string
	 *
	 * @return string
	 *
	 * @since 12.0.0
	 */
	public function renderText();
}
