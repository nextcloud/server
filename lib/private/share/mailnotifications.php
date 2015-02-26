<?php
/**
* ownCloud
*
* @author Bjoern Schiessle
* @copyright 2014 Bjoern Schiessle <schiessle@owncloud.com>
*
* This library is free software; you can redistribute it and/or
* modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
* License as published by the Free Software Foundation; either
* version 3 of the License, or any later version.
*
* This library is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
* GNU AFFERO GENERAL PUBLIC LICENSE for more details.
*
* You should have received a copy of the GNU Affero General Public
* License along with this library.  If not, see <http://www.gnu.org/licenses/>.
*/

namespace OC\Share;

use DateTime;

class MailNotifications {

	/**
	 * sender userId
	 * @var null|string
	 */
	private $senderId;

	/**
	 * sender email address
	 * @var string
	 */
	private $from;

	/**
	 * @var string
	 */
	private $senderDisplayName;

	/**
	 * @var \OC_L10N
	 */
	private $l;

	/**
	 *
	 * @param string $sender user id (if nothing is set we use the currently logged-in user)
	 */
	public function __construct($sender = null) {
		$this->l = \OC::$server->getL10N('lib');

		$this->senderId = $sender;

		$this->from = \OCP\Util::getDefaultEmailAddress('sharing-noreply');
		if ($this->senderId) {
			$this->from = \OCP\Config::getUserValue($this->senderId, 'settings', 'email', $this->from);
			$this->senderDisplayName = \OCP\User::getDisplayName($this->senderId);
		} else {
			$this->senderDisplayName = \OCP\User::getDisplayName();
		}
	}

	/**
	 * inform users if a file was shared with them
	 *
	 * @param array $recipientList list of recipients
	 * @param string $itemSource shared item source
	 * @param string $itemType shared item type
	 * @return array list of user to whom the mail send operation failed
	 */
	public function sendInternalShareMail($recipientList, $itemSource, $itemType) {

		$noMail = array();

		foreach ($recipientList as $recipient) {
			$recipientDisplayName = \OCP\User::getDisplayName($recipient);
			$to = \OC::$server->getConfig()->getUserValue($recipient, 'settings', 'email', '');

			if ($to === '') {
				$noMail[] = $recipientDisplayName;
				continue;
			}

			$items = \OCP\Share::getItemSharedWithUser($itemType, $itemSource, $recipient);
			$filename = trim($items[0]['file_target'], '/');
			$subject = (string) $this->l->t('%s shared »%s« with you', array($this->senderDisplayName, $filename));
			$expiration = null;
			if (isset($items[0]['expiration'])) {
				try {
					$date = new DateTime($items[0]['expiration']);
					$expiration = $date->getTimestamp();
				} catch (\Exception $e) {
					\OCP\Util::writeLog('sharing', "Couldn't read date: " . $e->getMessage(), \OCP\Util::ERROR);
				}
			}

			// Link to folder, or root folder if a file

			if ($itemType === 'folder') {
				$args = array(
					'dir' => $filename,
				);
			} else {
				$args = array(
					'dir' => '/',
					'scrollto' => $filename,
				);
			}

			$link = \OCP\Util::linkToAbsolute('files', 'index.php', $args);

			list($htmlMail, $alttextMail) = $this->createMailBody($filename, $link, $expiration);

			// send it out now
			try {
				\OCP\Util::sendMail($to, $recipientDisplayName, $subject, $htmlMail, $this->from, $this->senderDisplayName, 1, $alttextMail);
			} catch (\Exception $e) {
				\OCP\Util::writeLog('sharing', "Can't send mail to inform the user about an internal share: " . $e->getMessage() , \OCP\Util::ERROR);
				$noMail[] = $recipientDisplayName;
			}
		}

		return $noMail;

	}

	/**
	 * inform recipient about public link share
	 *
	 * @param string $recipient recipient email address
	 * @param string $filename the shared file
	 * @param string $link the public link
	 * @param int $expiration expiration date (timestamp)
	 * @return array $result of failed recipients
	 */
	public function sendLinkShareMail($recipient, $filename, $link, $expiration) {
		$subject = (string)$this->l->t('%s shared »%s« with you', array($this->senderDisplayName, $filename));
		list($htmlMail, $alttextMail) = $this->createMailBody($filename, $link, $expiration);
		$rs = explode(' ', $recipient);
		$failed = array();
		foreach ($rs as $r) {
			try {
				\OCP\Util::sendMail($r, $r, $subject, $htmlMail, $this->from, $this->senderDisplayName, 1, $alttextMail);
			} catch (\Exception $e) {
				\OCP\Util::writeLog('sharing', "Can't send mail with public link to $r: " . $e->getMessage(), \OCP\Util::ERROR);
				$failed[] = $r;
			}
		}
		return $failed;
	}

	/**
	 * create mail body for plain text and html mail
	 *
	 * @param string $filename the shared file
	 * @param string $link link to the shared file
	 * @param int $expiration expiration date (timestamp)
	 * @return array an array of the html mail body and the plain text mail body
	 */
	private function createMailBody($filename, $link, $expiration) {

		$formatedDate = $expiration ? $this->l->l('date', $expiration) : null;

		$html = new \OC_Template("core", "mail", "");
		$html->assign ('link', $link);
		$html->assign ('user_displayname', $this->senderDisplayName);
		$html->assign ('filename', $filename);
		$html->assign('expiration',  $formatedDate);
		$htmlMail = $html->fetchPage();

		$alttext = new \OC_Template("core", "altmail", "");
		$alttext->assign ('link', $link);
		$alttext->assign ('user_displayname', $this->senderDisplayName);
		$alttext->assign ('filename', $filename);
		$alttext->assign('expiration', $formatedDate);
		$alttextMail = $alttext->fetchPage();

		return array($htmlMail, $alttextMail);
	}

}
