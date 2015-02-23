<?php
/**
 * @author Bart Visscher <bartv@thisnet.nl>
 * @author Björn Schießle <schiessle@owncloud.com>
 * @author Frank Karlitschek <frank@owncloud.org>
 * @author j-ed <juergen@eisfair.org>
 * @author Joas Schilling <nickvergessen@gmx.de>
 * @author Jörn Friedrich Dreyer <jfd@butonic.de>
 * @author Lukas Reschke <lukas@owncloud.com>
 * @author Michael Gapczynski <gapczynskim@gmail.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 *
 * @copyright Copyright (c) 2015, ownCloud, Inc.
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */
class OC_Mail {

	/**
	 * send an email
	 *
	 * @param string $toaddress
	 * @param string $toname
	 * @param string $subject
	 * @param string $mailtext
	 * @param string $fromaddress
	 * @param string $fromname
	 * @param integer $html
	 * @param string $altbody
	 * @param string $ccaddress
	 * @param string $ccname
	 * @param string $bcc
	 * @throws Exception
	 */
	public static function send($toaddress, $toname, $subject, $mailtext, $fromaddress, $fromname,
		$html=0, $altbody='', $ccaddress='', $ccname='', $bcc='') {

		$SMTPMODE = OC_Config::getValue( 'mail_smtpmode', 'sendmail' );
		$SMTPHOST = OC_Config::getValue( 'mail_smtphost', '127.0.0.1' );
		$SMTPPORT = OC_Config::getValue( 'mail_smtpport', 25 );
		$SMTPAUTH = OC_Config::getValue( 'mail_smtpauth', false );
		$SMTPAUTHTYPE = OC_Config::getValue( 'mail_smtpauthtype', 'LOGIN' );
		$SMTPUSERNAME = OC_Config::getValue( 'mail_smtpname', '' );
		$SMTPPASSWORD = OC_Config::getValue( 'mail_smtppassword', '' );
		$SMTPDEBUG    = OC_Config::getValue( 'mail_smtpdebug', false );
		$SMTPTIMEOUT  = OC_Config::getValue( 'mail_smtptimeout', 10 );
		$SMTPSECURE   = OC_Config::getValue( 'mail_smtpsecure', '' );


		$mailo = new PHPMailer(true);
		if($SMTPMODE=='sendmail') {
			$mailo->IsSendmail();
		}elseif($SMTPMODE=='smtp') {
			$mailo->IsSMTP();
		}elseif($SMTPMODE=='qmail') {
			$mailo->IsQmail();
		}else{
			$mailo->IsMail();
		}


		$mailo->Host = $SMTPHOST;
		$mailo->Port = $SMTPPORT;
		$mailo->SMTPAuth = $SMTPAUTH;
		$mailo->SMTPDebug = $SMTPDEBUG;
		$mailo->Debugoutput = 'error_log';
		$mailo->SMTPSecure = $SMTPSECURE;
		$mailo->AuthType = $SMTPAUTHTYPE;
		$mailo->Username = $SMTPUSERNAME;
		$mailo->Password = $SMTPPASSWORD;
		$mailo->Timeout  = $SMTPTIMEOUT;

		$mailo->From = $fromaddress;
		$mailo->FromName = $fromname;;
		$mailo->Sender = $fromaddress;
		$mailo->XMailer = ' ';
		try {
			$toaddress = self::buildAsciiEmail($toaddress);
			$mailo->AddAddress($toaddress, $toname);

			if($ccaddress != '') $mailo->AddCC($ccaddress, $ccname);
			if($bcc != '') $mailo->AddBCC($bcc);

			$mailo->AddReplyTo($fromaddress, $fromname);

			$mailo->WordWrap = 78;
			$mailo->IsHTML($html == 1);

			$mailo->Subject = $subject;
			if($altbody == '') {
				$mailo->Body    = $mailtext.OC_MAIL::getfooter();
				$mailo->AltBody = '';
			}else{
				$mailo->Body    = $mailtext;
				$mailo->AltBody = $altbody;
			}
			$mailo->CharSet = 'UTF-8';

			$mailo->Send();
			unset($mailo);
			OC_Log::write('mail',
				'Mail from '.$fromname.' ('.$fromaddress.')'.' to: '.$toname.'('.$toaddress.')'.' subject: '.$subject,
				OC_Log::DEBUG);
		} catch (Exception $exception) {
			OC_Log::write('mail', $exception->getMessage(), OC_Log::ERROR);
			throw($exception);
		}
	}

	/**
	 * return the footer for a mail
	 *
	 */
	public static function getfooter() {

		$defaults = new OC_Defaults();

		$txt="\n--\n";
		$txt.=$defaults->getName() . "\n";
		$txt.=$defaults->getSlogan() . "\n";

		return($txt);

	}

	/**
	 * @param string $emailAddress a given email address to be validated
	 * @return bool
	 */
	public static function validateAddress($emailAddress) {
		if (strpos($emailAddress, '@') === false) {
			return false;
		}
		$emailAddress = self::buildAsciiEmail($emailAddress);
		return PHPMailer::ValidateAddress($emailAddress);
	}

	/**
	 * IDN domains will be properly converted to ascii domains.
	 *
	 * @param string $emailAddress
	 * @return string
	 */
	public static function buildAsciiEmail($emailAddress) {
		if (!function_exists('idn_to_ascii')) {
			return $emailAddress;
		}

		list($name, $domain) = explode('@', $emailAddress, 2);
		$domain = idn_to_ascii($domain);

		return "$name@$domain";
	}

}
