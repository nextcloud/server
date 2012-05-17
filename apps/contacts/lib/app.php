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
OC_Contacts_App::$l10n = OC_L10N::get('contacts');
OC_Contacts_App::$categories = new OC_VCategories('contacts');
class OC_Contacts_App {
	/*
	 * @brief language object for calendar app
	 */

	public static $l10n;
	/*
	 * @brief categories of the user
	 */
	public static $categories = null;

	public static function getAddressbook($id) {
		$addressbook = OC_Contacts_Addressbook::find( $id );
		if( $addressbook === false || $addressbook['userid'] != OCP\USER::getUser()) {
			if ($addressbook === false) {
				OCP\Util::writeLog('contacts', 'Addressbook not found: '. $id, OCP\Util::ERROR);
				OCP\JSON::error(array('data' => array( 'message' => self::$l10n->t('Addressbook not found.'))));
			}
			else {
				OCP\Util::writeLog('contacts', 'Addressbook('.$id.') is not from '.OCP\USER::getUser(), OCP\Util::ERROR);
				OCP\JSON::error(array('data' => array( 'message' => self::$l10n->t('This is not your addressbook.'))));
			}
			exit();
		}
		return $addressbook;
	}

	public static function getContactObject($id) {
		$card = OC_Contacts_VCard::find( $id );
		if( $card === false ) {
			OCP\Util::writeLog('contacts', 'Contact could not be found: '.$id, OCP\Util::ERROR);
			OCP\JSON::error(array('data' => array( 'message' => self::$l10n->t('Contact could not be found.').' '.$id)));
			exit();
		}

		self::getAddressbook( $card['addressbookid'] );//access check
		return $card;
	}

	/**
	 * @brief Gets the VCard as an OC_VObject
	 * @returns The card or null if the card could not be parsed.
	 */
	public static function getContactVCard($id) {
		$card = self::getContactObject( $id );

		$vcard = OC_VObject::parse($card['carddata']);
		// Try to fix cards with missing 'N' field from pre ownCloud 4. Hot damn, this is ugly...
		if(!is_null($vcard) && !$vcard->__isset('N')) {
			$version = OCP\App::getAppVersion('contacts');
			if($version >= 5) {
				OCP\Util::writeLog('contacts','OC_Contacts_App::getContactVCard. Deprecated check for missing N field', OCP\Util::DEBUG);
			}
			OCP\Util::writeLog('contacts','getContactVCard, Missing N field', OCP\Util::DEBUG);
			if($vcard->__isset('FN')) {
				OCP\Util::writeLog('contacts','getContactVCard, found FN field: '.$vcard->__get('FN'), OCP\Util::DEBUG);
				$n = implode(';', array_reverse(array_slice(explode(' ', $vcard->__get('FN')), 0, 2))).';;;';
				$vcard->setString('N', $n);
				OC_Contacts_VCard::edit( $id, $vcard);
			} else { // Else just add an empty 'N' field :-P
				$vcard->setString('N', 'Unknown;Name;;;');
			}
		}
		if (!is_null($vcard) && !isset($vcard->REV)) {
			$rev = new DateTime('@'.$card['lastmodified']);
			$vcard->setString('REV', $rev->format(DateTime::W3C));
		}
		return $vcard;
	}

	public static function getPropertyLineByChecksum($vcard, $checksum) {
		$line = null;
		for($i=0;$i<count($vcard->children);$i++) {
			if(md5($vcard->children[$i]->serialize()) == $checksum ) {
				$line = $i;
				break;
			}
		}
		return $line;
	}

	/**
	 * @return array of vcard prop => label
	 */
	public static function getAddPropertyOptions() {
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
	public static function getTypesOfProperty($prop) {
		$l = self::$l10n;
		switch($prop) {
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
				'MSG'   =>  $l->t('Message'),
				'FAX'   =>  $l->t('Fax'),
				'VIDEO' =>  $l->t('Video'),
				'PAGER' =>  $l->t('Pager'),
			);
		case 'EMAIL':
			return array(
				'WORK' => $l->t('Work'),
				'HOME' => $l->t('Home'),
				'INTERNET' => $l->t('Internet'),
			);
		}
	}

	/*
	 * @brief returns the vcategories object of the user
	 * @return (object) $vcategories
	 */
	protected static function getVCategories() {
		if (is_null(self::$categories)) {
			self::$categories = new OC_VCategories('contacts');
		}
		return self::$categories;
	}
	
	/*
	 * @brief returns the categories for the user
	 * @return (Array) $categories
	 */
	public static function getCategories() {
		$categories = self::$categories->categories();
		if(count($categories) == 0) {
			self::scanCategories();
			$categories = self::$categories->categories();
		}
		return $categories;
	}

	/**
	 * scan vcards for categories.
	 * @param $vccontacts VCards to scan. null to check all vcards for the current user.
	 */
	public static function scanCategories($vccontacts = null) {
		if (is_null($vccontacts)) {
			$vcaddressbooks = OC_Contacts_Addressbook::all(OCP\USER::getUser());
			if(count($vcaddressbooks) > 0) {
				$vcaddressbookids = array();
				foreach($vcaddressbooks as $vcaddressbook) {
					$vcaddressbookids[] = $vcaddressbook['id'];
				}
				$vccontacts = OC_Contacts_VCard::all($vcaddressbookids);
			}
		}
		if(is_array($vccontacts) && count($vccontacts) > 0) {
			$cards = array();
			foreach($vccontacts as $vccontact) {
				$cards[] = $vccontact['carddata'];
			}

			self::$categories->rescan($cards);
		}
	}

	/**
	 * check VCard for new categories.
	 * @see OC_VCategories::loadFromVObject
	 */
	public static function loadCategoriesFromVCard(OC_VObject $contact) {
		self::getVCategories()->loadFromVObject($contact, true);
	}

	public static function setLastModifiedHeader($contact) {
		$rev = $contact->getAsString('REV');
		if ($rev) {
			$rev = DateTime::createFromFormat(DateTime::W3C, $rev);
			OCP\Response::setLastModifiedHeader($rev);
		}
	}
}
