<?php
class OC_Search_Provider_Contacts extends OC_Search_Provider{
	function search($query){
		$addressbooks = OC_Contacts_Addressbook::all(OCP\USER::getUser(), 1);
// 		if(count($calendars)==0 || !OCP\App::isEnabled('contacts')){
// 			//return false;
// 		}
		// NOTE: Does the following do anything
		$results=array();
		$searchquery=array();
		if(substr_count($query, ' ') > 0){
			$searchquery = explode(' ', $query);
		}else{
			$searchquery[] = $query;
		}
		$l = new OC_l10n('contacts');
		foreach($addressbooks as $addressbook){
			$vcards = OC_Contacts_VCard::all($addressbook['id']);
			foreach($vcards as $vcard){
				if(substr_count(strtolower($vcard['fullname']), strtolower($query)) > 0){
					$link = OCP\Util::linkTo('contacts', 'index.php').'?id='.urlencode($vcard['id']);
					$results[]=new OC_Search_Result($vcard['fullname'],'', $link,$l->t('Contact'));//$name,$text,$link,$type
				}
			}
		}
		return $results;
	}
}
