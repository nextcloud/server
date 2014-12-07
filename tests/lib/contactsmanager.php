<?php


class Test_ContactsManager extends \Test\TestCase {

	/** @var \OC\ContactsManager */
	private $cm;

	protected function setUp() {
		parent::setUp();
		$this->cm = new \OC\ContactsManager();
	}

	public function searchProvider(){
		$search1 = array(
			0 => array(
				'N' => array(0 => '', 1 => 'Jan', 2 => 'Jansen', 3 => '', 4 => '',),
				'UID' => '04ada7f5-01f9-4309-9c82-6b555b2170ed',
				'FN' => 'Jan Jansen',
				'id' => '1',
				'addressbook-key' => 'simple:1',
			),
			0 => array(
				'N' => array(0 => '', 1 => 'Tom', 2 => 'Peeters', 3 => '', 4 => '',),
				'UID' => '04ada7f5-01f9-4309-9c82-2345-2345--6b555b2170ed',
				'FN' => 'Tom Peeters',
				'id' => '2',
				'addressbook-key' => 'simple:1',
			),
		);

		$search2 = array(
			0 => array(
				'N' => array(0 => '', 1 => 'fg', 2 => '', 3 => '', 4 => '',),
				'UID' => '04ada234h5jh357f5-01f9-4309-9c82-6b555b2170ed',
				'FN' => 'Jan Rompuy',
				'id' => '1',
				'addressbook-key' => 'simple:2',
			),
			0 => array(
				'N' => array(0 => '', 1 => 'fg', 2 => '', 3 => '', 4 => '',),
				'UID' => '04ada7f5-01f9-4309-345kj345j9c82-2345-2345--6b555b2170ed',
				'FN' => 'Tim Peeters',
				'id' => '2',
				'addressbook-key' => 'simple:2',
			),
		);

		$expectedResult =  array_merge($search1, $search2);

		return array(
			array(
				array(
					new SimpleAddressbook('simple:1', 'Simeple Addressbook 1', $search1, \OCP\Constants::PERMISSION_ALL),
					new SimpleAddressbook('simple:2', 'Simeple Addressbook 2', $search2, \OCP\Constants::PERMISSION_ALL),
				),
				$expectedResult
			)
		);
	}

	/**
	 * @dataProvider searchProvider
	 */
	public function testSearch(array $addressBooks,$expectedResult ){
		foreach ($addressBooks as $addressBook) {
			$this->cm->registerAddressBook($addressBook);
		}
		$result =  $this->cm->search('');
		$this->assertEquals($expectedResult, $result);
	}
	

	public function testDeleteHavePermission(){
		$addressbook = $this->getMockBuilder('SimpleAddressbook')
			->disableOriginalConstructor()
			->getMock();

		$addressbook->expects($this->any())
			->method('getPermissions')
			->willReturn(\OCP\Constants::PERMISSION_ALL);

		$addressbook->expects($this->once())
			->method('delete')
			->willReturn('returnMe');


		$this->cm->registerAddressBook($addressbook);
		$result = $this->cm->delete(1, $addressbook->getKey());
		$this->assertEquals($result, 'returnMe');
	}

	public function testDeleteNoPermission(){
		$addressbook = $this->getMockBuilder('SimpleAddressbook')
			->disableOriginalConstructor()
			->getMock();

		$addressbook->expects($this->any())
			->method('getPermissions')
			->willReturn(\OCP\Constants::PERMISSION_READ);

		$addressbook->expects($this->never())
			->method('delete');

		$this->cm->registerAddressBook($addressbook);
		$result = $this->cm->delete(1, $addressbook->getKey());
		$this->assertEquals($result, null);
	}

	public function testDeleteNoAddressbook(){
		$addressbook = $this->getMockBuilder('SimpleAddressbook')
			->disableOriginalConstructor()
			->getMock();

		$addressbook->expects($this->never())
			->method('delete');

		$this->cm->registerAddressBook($addressbook);
		$result = $this->cm->delete(1, 'noaddressbook');
		$this->assertEquals($result, null);

	}

	public function testCreateOrUpdateHavePermission(){
		$addressbook = $this->getMockBuilder('SimpleAddressbook')
			->disableOriginalConstructor()
			->getMock();

		$addressbook->expects($this->any())
			->method('getPermissions')
			->willReturn(\OCP\Constants::PERMISSION_ALL);

		$addressbook->expects($this->once())
			->method('createOrUpdate')
			->willReturn('returnMe');

		$this->cm->registerAddressBook($addressbook);
		$result = $this->cm->createOrUpdate(array(), $addressbook->getKey());
		$this->assertEquals($result, 'returnMe');
	}

	public function testCreateOrUpdateNoPermission(){
		$addressbook = $this->getMockBuilder('SimpleAddressbook')
			->disableOriginalConstructor()
			->getMock();

		$addressbook->expects($this->any())
			->method('getPermissions')
			->willReturn(\OCP\Constants::PERMISSION_READ);

		$addressbook->expects($this->never())
			->method('createOrUpdate');

		$this->cm->registerAddressBook($addressbook);
		$result = $this->cm->createOrUpdate(array(), $addressbook->getKey());
		$this->assertEquals($result, null);

	}

	public function testCreateOrUpdateNOAdressbook(){
		$addressbook = $this->getMockBuilder('SimpleAddressbook')
			->disableOriginalConstructor()
			->getMock();

		$addressbook->expects($this->never())
			->method('createOrUpdate');

		$this->cm->registerAddressBook($addressbook);
		$result = $this->cm->createOrUpdate(array(), 'noaddressbook');
		$this->assertEquals($result, null);
	}

	public function testIsEnabledIfNot(){
		$result = $this->cm->isEnabled();
		$this->assertFalse($result);
	}

	public function testIsEnabledIfSo(){
		$addressbook = $this->getMockBuilder('SimpleAddressbook')
			->disableOriginalConstructor()
			->getMock();

		$this->cm->registerAddressBook($addressbook);
		$result = $this->cm->isEnabled();
		$this->assertTrue($result);
	}






}


class SimpleAddressbook implements \OCP\IAddressBook {

	public function __construct($key, $displayName, $contacts, $permissions){
		$this->key = $key;
		$this->contacts = $contacts;
		$this->displayName = $displayName;
		$this->permissions = $permissions;
	}


	public function getKey(){
		return $this->key;
	}

	public function getDisplayName(){
		return $this->displayName;
	}

	public function search($pattern, $searchProperties, $options){
		return $this->contacts;
	}

	public function createOrUpdate($properties){
	}

	public function getPermissions(){
		return $this->permissions;
	}

	public function delete($id){
	}
}