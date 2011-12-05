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
	public static function renderDetails($id, $vcard){
		$property_types = self::getAddPropertyOptions(self::$l10n);
		$adr_types = self::getTypesOfProperty(self::$l10n, 'ADR');
		$phone_types = self::getTypesOfProperty(self::$l10n, 'TEL');

		$details = OC_Contacts_VCard::structureContact($vcard);
		$name = $details['FN'][0]['value'];
		$tmpl = new OC_Template('contacts','part.details');
		$tmpl->assign('details',$details);
		$tmpl->assign('id',$id);
		$tmpl->assign('property_types',$property_types);
		$tmpl->assign('adr_types',$adr_types);
		$tmpl->assign('phone_types',$phone_types);
		$page = $tmpl->fetchPage();

		OC_JSON::success(array('data' => array( 'id' => $id, 'name' => $name, 'page' => $page )));
	}

	/**
	 * @return array of vcard prop => label
	 */
	public static function getAddPropertyOptions($l10n){
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
	public static function getTypesOfProperty($l, $prop){
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
