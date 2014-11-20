<?php
use OC\Contacts\LocalAddressBook;

/**
 * ownCloud
 *
 * @author Thomas Müller
 * @copyright 2014 Thomas Müller thomas.mueller@tmit.eu
 *
 * You should have received a copy of the GNU Affero General Public
 * License along with this library.  If not, see <http://www.gnu.org/licenses/>.
 */

class Test_LocalAddressBook extends \Test\TestCase
{

	public function testSearchFN() {
		$stub = $this->getMockForAbstractClass('\OCP\IUserManager', array('searchDisplayName'));

		$stub->expects($this->any())->method('searchDisplayName')->will($this->returnValue(array(
			new SimpleUserForTesting('tom', 'Thomas'),
			new SimpleUserForTesting('tomtom', 'Thomas T.'),
		)));

		$localAddressBook = new LocalAddressBook($stub);

		$result = $localAddressBook->search('tom', array('FN'), array());
		$this->assertEquals(2, count($result));
	}

	public function testSearchId() {
		$stub = $this->getMockForAbstractClass('\OCP\IUserManager', array('searchDisplayName'));

		$stub->expects($this->any())->method('search')->will($this->returnValue(array(
			new SimpleUserForTesting('tom', 'Thomas'),
			new SimpleUserForTesting('tomtom', 'Thomas T.'),
		)));

		$localAddressBook = new LocalAddressBook($stub);

		$result = $localAddressBook->search('tom', array('id'), array());
		$this->assertEquals(2, count($result));
	}
}


class SimpleUserForTesting implements \OCP\IUser {

	public function __construct($uid, $displayName) {

		$this->uid = $uid;
		$this->displayName = $displayName;
	}

	public function getUID() {
		return $this->uid;
	}

	public function getDisplayName() {
		return $this->displayName;
	}

	public function setDisplayName($displayName) {
	}

	public function getLastLogin() {
	}

	public function updateLastLoginTimestamp() {
	}

	public function delete() {
	}

	public function setPassword($password, $recoveryPassword = null) {
	}

	public function getHome() {
	}

	public function canChangeAvatar() {
	}

	public function canChangePassword() {
	}

	public function canChangeDisplayName() {
	}

	public function isEnabled() {
	}

	public function setEnabled($enabled) {
	}
}
