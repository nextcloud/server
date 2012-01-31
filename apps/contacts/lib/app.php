<?php
/**
 * Copyright (c) 2011 Bart Visscher bartv@thisnet.nl
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

/**
 * This class manages our app actions
 */
OC_Contacts_App::$l10n = new OC_L10N('contacts');
class OC_Contacts_App{
	public static $l10n;

	/**
	* Render templates/part.details to json output
	* @param int $id of contact
	* @param Sabre_VObject_Component $vcard to render
	*/
	public static function renderDetails($id, $vcard, $new=false){
		$property_types = self::getAddPropertyOptions();
		$adr_types = self::getTypesOfProperty('ADR');
		$phone_types = self::getTypesOfProperty('TEL');
		$upload_max_filesize = OC_Helper::computerFileSize(ini_get('upload_max_filesize'));
		$post_max_size = OC_Helper::computerFileSize(ini_get('post_max_size'));
		$maxUploadFilesize = min($upload_max_filesize, $post_max_size);

		$freeSpace=OC_Filesystem::free_space('/');
		$freeSpace=max($freeSpace,0);
		$maxUploadFilesize = min($maxUploadFilesize ,$freeSpace);

		$details = OC_Contacts_VCard::structureContact($vcard);
		$name = $details['FN'][0]['value'];
		$t = $new ? 'part.contact' : 'part.details';
		$tmpl = new OC_Template('contacts',$t);
		$tmpl->assign('details',$details);
		$tmpl->assign('id',$id);
		$tmpl->assign( 'uploadMaxFilesize', $maxUploadFilesize);
		$tmpl->assign( 'uploadMaxHumanFilesize', OC_Helper::humanFileSize($maxUploadFilesize));
		$tmpl->assign('property_types',$property_types);
		$tmpl->assign('adr_types',$adr_types);
		$tmpl->assign('phone_types',$phone_types);
		$page = $tmpl->fetchPage();

		OC_JSON::success(array('data' => array( 'id' => $id, 'name' => $name, 'page' => $page )));
	}

	public static function getAddressbook($id){
		$addressbook = OC_Contacts_Addressbook::find( $id );
		if( $addressbook === false || $addressbook['userid'] != OC_User::getUser()){
			OC_JSON::error(array('data' => array( 'message' => self::$l10n->t('This is not your addressbook.')))); // Same here (as with the contact error). Could this error be improved?
			exit();
		}
		return $addressbook;
	}

	public static function getContactObject($id){
		$card = OC_Contacts_VCard::find( $id );
		if( $card === false ){
			OC_JSON::error(array('data' => array( 'message' => self::$l10n->t('Contact could not be found.'))));
			exit();
		}

		self::getAddressbook( $card['addressbookid'] );
		return $card;
	}

	/**
	 * @brief Gets the VCard as text
	 * @returns The card or null if the card could not be parsed.
	 */
	public static function getContactVCard($id){
		$card = self::getContactObject( $id );

		$vcard = OC_VObject::parse($card['carddata']);
		return $vcard;
	}

	public static function getPropertyLineByChecksum($vcard, $checksum){
		$line = null;
		for($i=0;$i<count($vcard->children);$i++){
			if(md5($vcard->children[$i]->serialize()) == $checksum ){
				$line = $i;
				break;
			}
		}
		/*if(is_null($line)){
			OC_JSON::error(array('data' => array( 'message' => self::$l10n->t('Information about vCard is incorrect. Please reload the page.'))));
			exit();
		}*/
		return $line;
	}

	/**
	 * @return array of vcard prop => label
	 */
	public static function getAddPropertyOptions(){
		$l10n = self::$l10n;
		return array(
				'ADR'   => $l10n->t('Address'),
				'TEL'   => $l10n->t('Telephone'),
				'EMAIL' => $l10n->t('Email'),
				'ORG'   => $l10n->t('Organization'),
		     );
	}

	/**
	 * @return types for property $prop
	 */
	public static function getTypesOfProperty($prop){
		$l = self::$l10n;
		switch($prop){
		case 'ADR':
			return array(
				'WORK' => $l->t('Work'),
				'HOME' => $l->t('Home'),
			);
		case 'TEL':
			return array(
				'HOME'  =>  $l->t('Home'),
				'CELL'  =>  $l->t('Mobile'),
				'WORK'  =>  $l->t('Work'),
				'TEXT'  =>  $l->t('Text'),
				'VOICE' =>  $l->t('Voice'),
				'FAX'   =>  $l->t('Fax'),
				'VIDEO' =>  $l->t('Video'),
				'PAGER' =>  $l->t('Pager'),
			);
		}
	}
}
