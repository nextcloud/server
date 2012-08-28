<?php
class OC_Migration_Provider_Contacts extends OC_Migration_Provider{
	
	// Create the xml for the user supplied
	function export( ) {
		$options = array(
			'table'=>'contacts_addressbooks',
			'matchcol'=>'userid',
			'matchval'=>$this->uid,
			'idcol'=>'id'
		);
		$ids = $this->content->copyRows( $options );

		$options = array(
			'table'=>'contacts_cards',
			'matchcol'=>'addressbookid',
			'matchval'=>$ids
		);
		
		// Export tags
		$ids2 = $this->content->copyRows( $options );
		
		// If both returned some ids then they worked
		if(is_array($ids) && is_array($ids2)) {
			return true;
		} else {
			return false;
		}
		
	}
	
	// Import function for contacts
	function import( ) {
		switch( $this->appinfo->version ) {
			default:
				// All versions of the app have had the same db structure, so all can use the same import function
				$query = $this->content->prepare( 'SELECT * FROM `contacts_addressbooks` WHERE `userid` LIKE ?' );
				$results = $query->execute( array( $this->olduid ) );
				$idmap = array();
				while( $row = $results->fetchRow() ) {
					// Import each addressbook	
					$addressbookquery = OCP\DB::prepare( 'INSERT INTO `*PREFIX*contacts_addressbooks` (`userid`, `displayname`, `uri`, `description`, `ctag`) VALUES (?, ?, ?, ?, ?)' );
					$addressbookquery->execute( array( $this->uid, $row['displayname'], $row['uri'], $row['description'], $row['ctag'] ) );
					// Map the id
					$idmap[$row['id']] = OCP\DB::insertid('*PREFIX*contacts_addressbooks');
					// Make the addressbook active
					OC_Contacts_Addressbook::setActive($idmap[$row['id']], true);
				}
				// Now tags
				foreach($idmap as $oldid => $newid) {
					
					$query = $this->content->prepare( 'SELECT * FROM `contacts_cards` WHERE `addressbookid` LIKE ?' );
					$results = $query->execute( array( $oldid ) );
					while( $row = $results->fetchRow() ){
						// Import the contacts
						$contactquery = OCP\DB::prepare( 'INSERT INTO `*PREFIX*contacts_cards` (`addressbookid`, `fullname`, `carddata`, `uri`, `lastmodified`) VALUES (?, ?, ?, ?, ?)' );
						$contactquery->execute( array( $newid, $row['fullname'], $row['carddata'], $row['uri'], $row['lastmodified'] ) );	
					}		
				}
				// All done!
			break;
		}
		
		return true;
	}
	
}

// Load the provider
new OC_Migration_Provider_Contacts( 'contacts' );