<?php

declare(strict_types = 1);
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace Test\Notification;

use OC\Notification\Notification;
use OCP\Notification\IAction;
use OCP\Notification\INotification;
use OCP\RichObjectStrings\IRichTextFormatter;
use OCP\RichObjectStrings\IValidator;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class NotificationTest extends TestCase {
	/** @var INotification */
	protected $notification;
	protected IValidator&MockObject $validator;
	protected IRichTextFormatter&MockObject $richTextFormatter;

	protected function setUp(): void {
		parent::setUp();
		$this->validator = $this->createMock(IValidator::class);
		$this->richTextFormatter = $this->createMock(IRichTextFormatter::class);
		$this->notification = new Notification($this->validator, $this->richTextFormatter);
	}

	protected static function dataValidString($maxLength): array {
		$dataSets = [
			['test1'],
			['1564'],
			[str_repeat('a', 1)],
		];
		if ($maxLength !== false) {
			$dataSets[] = [str_repeat('a', $maxLength)];
		}
		return $dataSets;
	}

	protected static function dataInvalidString($maxLength): array {
		$dataSets = [
			['']
		];
		if ($maxLength !== false) {
			$dataSets[] = [str_repeat('a', $maxLength + 1)];
		}
		return $dataSets;
	}

	public static function dataSetApp(): array {
		return self::dataValidString(32);
	}

	/**
	 * @param string $app
	 */
	#[\PHPUnit\Framework\Attributes\DataProvider('dataSetApp')]
	public function testSetApp($app): void {
		$this->assertSame('', $this->notification->getApp());
		$this->assertSame($this->notification, $this->notification->setApp($app));
		$this->assertSame($app, $this->notification->getApp());
	}

	public static function dataSetAppInvalid(): array {
		return self::dataInvalidString(32);
	}

	/**
	 * @param mixed $app
	 *
	 */
	#[\PHPUnit\Framework\Attributes\DataProvider('dataSetAppInvalid')]
	public function testSetAppInvalid($app): void {
		$this->expectException(\InvalidArgumentException::class);

		$this->notification->setApp($app);
	}


	public static function dataSetUser(): array {
		return self::dataValidString(64);
	}

	/**
	 * @param string $user
	 */
	#[\PHPUnit\Framework\Attributes\DataProvider('dataSetUser')]
	public function testSetUser($user): void {
		$this->assertSame('', $this->notification->getUser());
		$this->assertSame($this->notification, $this->notification->setUser($user));
		$this->assertSame($user, $this->notification->getUser());
	}

	public static function dataSetUserInvalid(): array {
		return self::dataInvalidString(64);
	}

	/**
	 * @param mixed $user
	 *
	 */
	#[\PHPUnit\Framework\Attributes\DataProvider('dataSetUserInvalid')]
	public function testSetUserInvalid($user): void {
		$this->expectException(\InvalidArgumentException::class);

		$this->notification->setUser($user);
	}

	public static function dataSetDateTime(): array {
		$past = new \DateTime();
		$past->sub(new \DateInterval('P1Y'));
		$current = new \DateTime();
		$future = new \DateTime();
		$future->add(new \DateInterval('P1Y'));

		return [
			[$past],
			[$current],
			[$future],
		];
	}

	/**
	 * @param \DateTime $dateTime
	 */
	#[\PHPUnit\Framework\Attributes\DataProvider('dataSetDateTime')]
	public function testSetDateTime(\DateTime $dateTime): void {
		$this->assertSame(0, $this->notification->getDateTime()->getTimestamp());
		$this->assertSame($this->notification, $this->notification->setDateTime($dateTime));
		$this->assertSame($dateTime, $this->notification->getDateTime());
	}

	public static function dataSetDateTimeZero(): array {
		$nineTeenSeventy = new \DateTime();
		$nineTeenSeventy->setTimestamp(0);
		return [
			[$nineTeenSeventy],
		];
	}

	/**
	 * @param \DateTime $dateTime
	 * @expectedMessage 'The given date time is invalid'
	 */
	#[\PHPUnit\Framework\Attributes\DataProvider('dataSetDateTimeZero')]
	public function testSetDateTimeZero($dateTime): void {
		$this->expectException(\InvalidArgumentException::class);

		$this->notification->setDateTime($dateTime);
	}

	public static function dataSetObject(): array {
		return [
			['a', '21'],
			[str_repeat('a', 64), '42'],
		];
	}

	/**
	 * @param string $type
	 * @param string $id
	 */
	#[\PHPUnit\Framework\Attributes\DataProvider('dataSetObject')]
	public function testSetObject($type, $id): void {
		$this->assertSame('', $this->notification->getObjectType());
		$this->assertSame('', $this->notification->getObjectId());
		$this->assertSame($this->notification, $this->notification->setObject($type, $id));
		$this->assertSame($type, $this->notification->getObjectType());
		$this->assertSame($id, $this->notification->getObjectId());
	}

	public static function dataSetObjectTypeInvalid(): array {
		return self::dataInvalidString(64);
	}

	public static function dataSetObjectIdInvalid(): array {
		return [
			[''],
			[str_repeat('a', 64 + 1)],
		];
	}

	/**
	 * @param mixed $id
	 * @expectedMessage 'The given object id is invalid'
	 */
	#[\PHPUnit\Framework\Attributes\DataProvider('dataSetObjectIdInvalid')]
	public function testSetObjectIdInvalid($id): void {
		$this->expectException(\InvalidArgumentException::class);

		$this->notification->setObject('object', $id);
	}

	public static function dataSetSubject(): array {
		return [
			['a', []],
			[str_repeat('a', 64), [str_repeat('a', 160)]],
			[str_repeat('a', 64), array_fill(0, 160, 'a')],
		];
	}

	/**
	 * @param string $subject
	 * @param array $parameters
	 */
	#[\PHPUnit\Framework\Attributes\DataProvider('dataSetSubject')]
	public function testSetSubject($subject, $parameters): void {
		$this->assertSame('', $this->notification->getSubject());
		$this->assertSame([], $this->notification->getSubjectParameters());
		$this->assertSame($this->notification, $this->notification->setSubject($subject, $parameters));
		$this->assertSame($subject, $this->notification->getSubject());
		$this->assertSame($parameters, $this->notification->getSubjectParameters());
	}

	public static function dataSetSubjectInvalidSubject(): array {
		return self::dataInvalidString(64);
	}

	/**
	 * @param mixed $subject
	 *
	 */
	#[\PHPUnit\Framework\Attributes\DataProvider('dataSetSubjectInvalidSubject')]
	public function testSetSubjectInvalidSubject($subject): void {
		$this->expectException(\InvalidArgumentException::class);

		$this->notification->setSubject($subject, []);
	}

	public static function dataSetParsedSubject(): array {
		return self::dataValidString(false);
	}

	/**
	 * @param string $subject
	 */
	#[\PHPUnit\Framework\Attributes\DataProvider('dataSetParsedSubject')]
	public function testSetParsedSubject($subject): void {
		$this->assertSame('', $this->notification->getParsedSubject());
		$this->assertSame($this->notification, $this->notification->setParsedSubject($subject));
		$this->assertSame($subject, $this->notification->getParsedSubject());
	}

	public static function dataSetParsedSubjectInvalid(): array {
		return self::dataInvalidString(false);
	}

	/**
	 * @param mixed $subject
	 *
	 */
	#[\PHPUnit\Framework\Attributes\DataProvider('dataSetParsedSubjectInvalid')]
	public function testSetParsedSubjectInvalid($subject): void {
		$this->expectException(\InvalidArgumentException::class);

		$this->notification->setParsedSubject($subject);
	}

	public static function dataSetMessage(): array {
		return [
			['a', []],
			[str_repeat('a', 64), [str_repeat('a', 160)]],
			[str_repeat('a', 64), array_fill(0, 160, 'a')],
		];
	}

	/**
	 * @param string $message
	 * @param array $parameters
	 */
	#[\PHPUnit\Framework\Attributes\DataProvider('dataSetMessage')]
	public function testSetMessage($message, $parameters): void {
		$this->assertSame('', $this->notification->getMessage());
		$this->assertSame([], $this->notification->getMessageParameters());
		$this->assertSame($this->notification, $this->notification->setMessage($message, $parameters));
		$this->assertSame($message, $this->notification->getMessage());
		$this->assertSame($parameters, $this->notification->getMessageParameters());
	}

	public static function dataSetMessageInvalidMessage(): array {
		return self::dataInvalidString(64);
	}

	/**
	 * @param mixed $message
	 *
	 */
	#[\PHPUnit\Framework\Attributes\DataProvider('dataSetMessageInvalidMessage')]
	public function testSetMessageInvalidMessage($message): void {
		$this->expectException(\InvalidArgumentException::class);

		$this->notification->setMessage($message, []);
	}

	public static function dataSetParsedMessage(): array {
		return self::dataValidString(false);
	}

	/**
	 * @param string $message
	 */
	#[\PHPUnit\Framework\Attributes\DataProvider('dataSetParsedMessage')]
	public function testSetParsedMessage($message): void {
		$this->assertSame('', $this->notification->getParsedMessage());
		$this->assertSame($this->notification, $this->notification->setParsedMessage($message));
		$this->assertSame($message, $this->notification->getParsedMessage());
	}

	public static function dataSetParsedMessageInvalid(): array {
		return self::dataInvalidString(false);
	}

	/**
	 * @param mixed $message
	 *
	 */
	#[\PHPUnit\Framework\Attributes\DataProvider('dataSetParsedMessageInvalid')]
	public function testSetParsedMessageInvalid($message): void {
		$this->expectException(\InvalidArgumentException::class);

		$this->notification->setParsedMessage($message);
	}

	public static function dataSetLink(): array {
		return self::dataValidString(4000);
	}

	/**
	 * @param string $link
	 */
	#[\PHPUnit\Framework\Attributes\DataProvider('dataSetLink')]
	public function testSetLink($link): void {
		$this->assertSame('', $this->notification->getLink());
		$this->assertSame($this->notification, $this->notification->setLink($link));
		$this->assertSame($link, $this->notification->getLink());
	}

	public static function dataSetLinkInvalid(): array {
		return self::dataInvalidString(4000);
	}

	/**
	 * @param mixed $link
	 *
	 */
	#[\PHPUnit\Framework\Attributes\DataProvider('dataSetLinkInvalid')]
	public function testSetLinkInvalid($link): void {
		$this->expectException(\InvalidArgumentException::class);

		$this->notification->setLink($link);
	}

	public static function dataSetIcon(): array {
		return self::dataValidString(4000);
	}

	/**
	 * @param string $icon
	 */
	#[\PHPUnit\Framework\Attributes\DataProvider('dataSetIcon')]
	public function testSetIcon($icon): void {
		$this->assertSame('', $this->notification->getIcon());
		$this->assertSame($this->notification, $this->notification->setIcon($icon));
		$this->assertSame($icon, $this->notification->getIcon());
	}

	public static function dataSetIconInvalid(): array {
		return self::dataInvalidString(4000);
	}

	/**
	 * @param mixed $icon
	 *
	 */
	#[\PHPUnit\Framework\Attributes\DataProvider('dataSetIconInvalid')]
	public function testSetIconInvalid($icon): void {
		$this->expectException(\InvalidArgumentException::class);

		$this->notification->setIcon($icon);
	}

	public function testCreateAction(): void {
		$action = $this->notification->createAction();
		$this->assertInstanceOf(IAction::class, $action);
	}

	public function testAddAction(): void {
		/** @var IAction|\PHPUnit\Framework\MockObject\MockObject $action */
		$action = $this->createMock(IAction::class);
		$action->expects($this->once())
			->method('isValid')
			->willReturn(true);
		$action->expects($this->never())
			->method('isValidParsed');

		$this->assertSame($this->notification, $this->notification->addAction($action));

		$this->assertEquals([$action], $this->notification->getActions());
		$this->assertEquals([], $this->notification->getParsedActions());
	}


	public function testAddActionInvalid(): void {
		$this->expectException(\InvalidArgumentException::class);

		/** @var IAction|\PHPUnit\Framework\MockObject\MockObject $action */
		$action = $this->createMock(IAction::class);
		$action->expects($this->once())
			->method('isValid')
			->willReturn(false);
		$action->expects($this->never())
			->method('isValidParsed');

		$this->notification->addAction($action);
	}

	public function testAddActionSecondPrimary(): void {
		/** @var IAction|\PHPUnit\Framework\MockObject\MockObject $action */
		$action = $this->createMock(IAction::class);
		$action->expects($this->exactly(2))
			->method('isValid')
			->willReturn(true);
		$action->expects($this->exactly(2))
			->method('isPrimary')
			->willReturn(true);

		$this->assertSame($this->notification, $this->notification->addAction($action));

		$this->expectException(\InvalidArgumentException::class);
		$this->notification->addAction($action);
	}

	public function testAddParsedAction(): void {
		/** @var IAction|\PHPUnit\Framework\MockObject\MockObject $action */
		$action = $this->createMock(IAction::class);
		$action->expects($this->once())
			->method('isValidParsed')
			->willReturn(true);
		$action->expects($this->never())
			->method('isValid');

		$this->assertSame($this->notification, $this->notification->addParsedAction($action));

		$this->assertEquals([$action], $this->notification->getParsedActions());
		$this->assertEquals([], $this->notification->getActions());
	}


	public function testAddParsedActionInvalid(): void {
		$this->expectException(\InvalidArgumentException::class);

		/** @var IAction|\PHPUnit\Framework\MockObject\MockObject $action */
		$action = $this->createMock(IAction::class);
		$action->expects($this->once())
			->method('isValidParsed')
			->willReturn(false);
		$action->expects($this->never())
			->method('isValid');

		$this->notification->addParsedAction($action);
	}

	public function testAddActionSecondParsedPrimary(): void {
		/** @var IAction|\PHPUnit\Framework\MockObject\MockObject $action */
		$action = $this->createMock(IAction::class);
		$action->expects($this->exactly(2))
			->method('isValidParsed')
			->willReturn(true);
		$action->expects($this->exactly(2))
			->method('isPrimary')
			->willReturn(true);

		$this->assertSame($this->notification, $this->notification->addParsedAction($action));

		$this->expectException(\InvalidArgumentException::class);
		$this->notification->addParsedAction($action);
	}

	public function testAddActionParsedPrimaryEnd(): void {
		/** @var IAction|\PHPUnit\Framework\MockObject\MockObject $action */
		$action1 = $this->createMock(IAction::class);
		$action1->expects($this->exactly(2))
			->method('isValidParsed')
			->willReturn(true);
		$action1->expects($this->exactly(2))
			->method('isPrimary')
			->willReturn(false);
		/** @var IAction|\PHPUnit\Framework\MockObject\MockObject $action */
		$action2 = $this->createMock(IAction::class);
		$action2->expects($this->once())
			->method('isValidParsed')
			->willReturn(true);
		$action2->expects($this->once())
			->method('isPrimary')
			->willReturn(true);

		$this->assertSame($this->notification, $this->notification->addParsedAction($action1));
		$this->assertSame($this->notification, $this->notification->addParsedAction($action2));
		$this->assertSame($this->notification, $this->notification->addParsedAction($action1));

		$this->assertEquals([$action2, $action1, $action1], $this->notification->getParsedActions());
	}

	public static function dataIsValid(): array {
		return [
			[false, '', false],
			[true, '', false],
			[false, 'a', false],
			[true, 'a', true],
		];
	}

	/**
	 *
	 * @param bool $isValidCommon
	 * @param string $subject
	 * @param bool $expected
	 */
	#[\PHPUnit\Framework\Attributes\DataProvider('dataIsValid')]
	public function testIsValid($isValidCommon, $subject, $expected): void {
		/** @var INotification|\PHPUnit\Framework\MockObject\MockObject $notification */
		$notification = $this->getMockBuilder(Notification::class)
			->onlyMethods([
				'isValidCommon',
				'getSubject',
				'getParsedSubject',
			])
			->setConstructorArgs([$this->validator, $this->richTextFormatter])
			->getMock();

		$notification->expects($this->once())
			->method('isValidCommon')
			->willReturn($isValidCommon);

		$notification->expects(!$isValidCommon ? $this->never() : $this->once())
			->method('getSubject')
			->willReturn($subject);

		$notification->expects($this->never())
			->method('getParsedSubject')
			->willReturn($subject);

		$this->assertEquals($expected, $notification->isValid());
	}

	/**
	 *
	 * @param bool $isValidCommon
	 * @param string $subject
	 * @param bool $expected
	 */
	#[\PHPUnit\Framework\Attributes\DataProvider('dataIsValid')]
	public function testIsParsedValid($isValidCommon, $subject, $expected): void {
		/** @var INotification|\PHPUnit\Framework\MockObject\MockObject $notification */
		$notification = $this->getMockBuilder(Notification::class)
			->onlyMethods([
				'isValidCommon',
				'getParsedSubject',
				'getSubject',
			])
			->setConstructorArgs([$this->validator, $this->richTextFormatter])
			->getMock();

		$notification->expects($this->once())
			->method('isValidCommon')
			->willReturn($isValidCommon);

		$notification->expects(!$isValidCommon ? $this->never() : $this->once())
			->method('getParsedSubject')
			->willReturn($subject);

		$notification->expects($this->never())
			->method('getSubject')
			->willReturn($subject);

		$this->assertEquals($expected, $notification->isValidParsed());
	}

	public static function dataIsValidCommon(): array {
		return [
			['', '', 0, '', '', false],
			['app', '', 0, '', '', false],
			['app', 'user', 0, '', '', false],
			['app', 'user', time(), '', '', false],
			['app', 'user', time(), 'type', '', false],
			['app', 'user', time(), 'type', '42', true],
		];
	}

	/**
	 *
	 * @param string $app
	 * @param string $user
	 * @param int $timestamp
	 * @param string $objectType
	 * @param string $objectId
	 * @param bool $expected
	 */
	#[\PHPUnit\Framework\Attributes\DataProvider('dataIsValidCommon')]
	public function testIsValidCommon($app, $user, $timestamp, $objectType, $objectId, $expected): void {
		/** @var INotification|\PHPUnit\Framework\MockObject\MockObject $notification */
		$notification = $this->getMockBuilder(Notification::class)
			->onlyMethods([
				'getApp',
				'getUser',
				'getDateTime',
				'getObjectType',
				'getObjectId',
			])
			->setConstructorArgs([$this->validator, $this->richTextFormatter])
			->getMock();

		$notification->expects($this->any())
			->method('getApp')
			->willReturn($app);

		$notification->expects($this->any())
			->method('getUser')
			->willReturn($user);

		$dateTime = new \DateTime();
		$dateTime->setTimestamp($timestamp);

		$notification->expects($this->any())
			->method('getDateTime')
			->willReturn($dateTime);

		$notification->expects($this->any())
			->method('getObjectType')
			->willReturn($objectType);

		$notification->expects($this->any())
			->method('getObjectId')
			->willReturn($objectId);

		$this->assertEquals($expected, $this->invokePrivate($notification, 'isValidCommon'));
	}
}
