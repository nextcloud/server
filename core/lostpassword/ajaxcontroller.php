<?php
/**
 * @author Victor Dubiniuk
 * @copyright 2013 Victor Dubiniuk victor.dubiniuk@gmail.com
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */
 
namespace OC\Core\LostPassword;

class AjaxController {
	public static function lost()	{
		\OCP\JSON::callCheck();
	
		try {
			Controller::sendEmail(@$_POST['user'], @$_POST['proceed']);
			\OCP\JSON::success();
		} catch (EncryptedDataException $e){
			\OCP\JSON::error(
				array('encryption' => '1')
			);
		} catch (\Exception $e){
			\OCP\JSON::error(
				array('msg'=> $e->getMessage())
			);
		}
		
		exit();
	}
	
	public static function resetPassword($args) {
		\OCP\JSON::callCheck();
		try {
			Controller::resetPassword($args);
			\OCP\JSON::success();
		} catch (Exception $e){
			\OCP\JSON::error(
				array('msg'=> $e->getMessage())
			);
		}
		exit();
	}
}
