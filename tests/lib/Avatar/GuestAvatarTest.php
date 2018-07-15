<?php
declare(strict_types=1);

namespace Test\Avatar;
use OC\Avatar\GuestAvatar;
use OC\Files\SimpleFS\InMemoryFile;
use OCP\ILogger;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

/**
 * This class provides test cases for the GuestAvatar class.
 *
 * @package Test\Avatar
 */
class GuestAvatarTest extends TestCase {
	/**
	 * Asserts that testGet() returns the expected avatar.
	 *
	 * For the test a static name "einstein" is used and
	 * the generated image is compared with an expected one.
	 *
	 * @return void
	 */
	public function testGet() {
		/* @var MockObject|ILogger $logger */
		$logger = $this->getMockBuilder(ILogger::class)->getMock();
		$guestAvatar = new GuestAvatar('einstein', $logger);
		$avatar = $guestAvatar->getFile(32);
		self::assertInstanceOf(InMemoryFile::class, $avatar);
		$expectedFile = file_get_contents(
			__DIR__ . '/../../data/guest_avatar_einstein_32.svg'
		);
		self::assertEquals($expectedFile, $avatar->getContent());
	}
}
