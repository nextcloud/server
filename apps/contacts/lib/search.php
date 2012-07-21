<?php
class OC_Search_Provider_Contacts extends OC_Search_Provider{
	function search($query){
		$addressbooks = OC_Contacts_Addressbook::all(OCP\USER::getUser(), 1);
		if(count($addressbooks)==0 || !OCP\App::isEnabled('contacts')) {
			return array();
		}
		$results=array();
		$l = new OC_l10n('contacts');
		foreach($addressbooks as $addressbook){
			$vcards = OC_Contacts_VCard::all($addressbook['id']);
			foreach($vcards as $vcard){
				if(substr_count(strtolower($vcard['fullname']), strtolower($query)) > 0) {
					$link = OCP\Util::linkTo('contacts', 'index.php').'&id='.urlencode($vcard['id']);
					$results[]=new OC_Search_Result($vcard['fullname'], '', $link, (string)$l->t('Contact'));//$name,$text,$link,$type
				}
			}
		}
		return $results;
	}
}
