<?php
/**
 * Copyright (c) 2012 Frank Karlitschek <frank@owncloud.org>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

/**
 * OC_Mail
 *
 * A class to handle mail sending.
 */

require_once('class.phpmailer.php');

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
	 * @param bool $html
	 */
	public static function send($toaddress,$toname,$subject,$mailtext,$fromaddress,$fromname,$html=0,$altbody='',$ccaddress='',$ccname='',$bcc='') {

		$SMTPMODE = OC_Config::getValue( 'mail_smtpmode', 'sendmail' );
		$SMTPHOST = OC_Config::getValue( 'mail_smtphost', '127.0.0.1' );
		$SMTPAUTH = OC_Config::getValue( 'mail_smtpauth', false ); 
		$SMTPUSERNAME = OC_Config::getValue( 'mail_smtpname', '' ); 
		$SMTPPASSWORD = OC_Config::getValue( 'mail_smtppassword', '' );  


		$mailo = new PHPMailer(true);
		if($SMTPMODE=='sendmail') {
			$mailo->IsSendmail();
		}elseif($SMTPMODE=='smtp'){
			$mailo->IsSMTP();
		}elseif($SMTPMODE=='qmail'){
			$mailo->IsQmail();
		}else{
			$mailo->IsMail();
		}


		$mailo->Host = $SMTPHOST;
		$mailo->SMTPAuth = $SMTPAUTH;
		$mailo->Username = $SMTPUSERNAME;
		$mailo->Password = $SMTPPASSWORD;

		$mailo->From =$fromaddress;
		$mailo->FromName = $fromname;;
		$a=explode(' ',$toaddress);
		try {
			foreach($a as $ad) {
				$mailo->AddAddress($ad,$toname);
			}

			if($ccaddress<>'') $mailo->AddCC($ccaddress,$ccname);
			if($bcc<>'') $mailo->AddBCC($bcc);

			$mailo->AddReplyTo($fromaddress, $fromname);

			$mailo->WordWrap = 50;
			if($html==1) $mailo->IsHTML(true); else $mailo->IsHTML(false);

			$mailo->Subject = $subject;
			if($altbody=='') {
				$mailo->Body    = $mailtext.OC_MAIL::getfooter();
				$mailo->AltBody = '';
			}else{
				$mailo->Body    = $mailtext;
				$mailo->AltBody = $altbody;
			}
			$mailo->CharSet = 'UTF-8';

			$mailo->Send();
			unset($mailo);
			OC_Log::write('mail', 'Mail from '.$fromname.' ('.$fromaddress.')'.' to: '.$toname.'('.$toaddress.')'.' subject: '.$subject, OC_Log::DEBUG);
		} catch (Exception $exception) {
			OC_Log::write('mail', $exception->getMessage(), OC_Log::ERROR);
			throw($exception);
		}
	}



	/**
	 * sending a mail based on a template
	 *
	 * @param texttemplate $texttemplate
	 * @param htmltemplate $htmltemplate
	 * @param data $data
	 * @param To $toaddress
	 * @param ToName $toname
	 * @param Subject $subject
	 * @param From $fromaddress
	 * @param FromName $fromname
	 * @param ccaddress $ccaddress
	 * @param ccname $ccname
	 * @param bcc $bcc
	 */
	public static function getfooter() {

		$txt="\n--\n";
		$txt.="ownCloud\n";
		$txt.="Your Cloud, Your Data, Your Way!\n";
		return($txt);

	}



}
