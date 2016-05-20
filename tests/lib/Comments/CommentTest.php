<?php

namespace Test\Comments;

use OCP\Comments\IComment;
use Test\TestCase;

class CommentTest extends TestCase {

	public function testSettersValidInput() {
		$comment = new \OC\Comments\Comment();

		$id = 'comment23';
		$parentId = 'comment11.5';
		$childrenCount = 6;
		$message = 'I like to comment comment';
		$verb = 'comment';
		$actor = ['type' => 'users', 'id' => 'alice'];
		$creationDT = new \DateTime();
		$latestChildDT = new \DateTime('yesterday');
		$object = ['type' => 'files', 'id' => 'file64'];

		$comment
			->setId($id)
			->setParentId($parentId)
			->setChildrenCount($childrenCount)
			->setMessage($message)
			->setVerb($verb)
			->setActor($actor['type'], $actor['id'])
			->setCreationDateTime($creationDT)
			->setLatestChildDateTime($latestChildDT)
			->setObject($object['type'], $object['id']);

		$this->assertSame($id, $comment->getId());
		$this->assertSame($parentId, $comment->getParentId());
		$this->assertSame($childrenCount, $comment->getChildrenCount());
		$this->assertSame($message, $comment->getMessage());
		$this->assertSame($verb, $comment->getVerb());
		$this->assertSame($actor['type'], $comment->getActorType());
		$this->assertSame($actor['id'], $comment->getActorId());
		$this->assertSame($creationDT, $comment->getCreationDateTime());
		$this->assertSame($latestChildDT, $comment->getLatestChildDateTime());
		$this->assertSame($object['type'], $comment->getObjectType());
		$this->assertSame($object['id'], $comment->getObjectId());
	}

	/**
	 * @expectedException \OCP\Comments\IllegalIDChangeException
	 */
	public function testSetIdIllegalInput() {
		$comment = new \OC\Comments\Comment();

		$comment->setId('c23');
		$comment->setId('c17');
	}

	public function testResetId() {
		$comment = new \OC\Comments\Comment();
		$comment->setId('c23');
		$comment->setId('');

		$this->assertSame('', $comment->getId());
	}

	public function simpleSetterProvider() {
		return [
			['Id', true],
			['ParentId', true],
			['Message', true],
			['Verb', true],
			['Verb', ''],
			['ChildrenCount', true],
		];
	}

	/**
	 * @dataProvider simpleSetterProvider
	 * @expectedException \InvalidArgumentException
	 */
	public function testSimpleSetterInvalidInput($field, $input) {
		$comment = new \OC\Comments\Comment();
		$setter = 'set' . $field;

		$comment->$setter($input);
	}

	public function roleSetterProvider() {
		return [
			['Actor', true, true],
			['Actor', 'users', true],
			['Actor', true, 'alice'],
			['Actor', ' ', ' '],
			['Object', true, true],
			['Object', 'files', true],
			['Object', true, 'file64'],
			['Object', ' ', ' '],
		];
	}

	/**
	 * @dataProvider roleSetterProvider
	 * @expectedException \InvalidArgumentException
	 */
	public function testSetRoleInvalidInput($role, $type, $id){
		$comment = new \OC\Comments\Comment();
		$setter = 'set' . $role;
		$comment->$setter($type, $id);
	}

	/**
	 * @expectedException \OCP\Comments\MessageTooLongException
	 */
	public function testSetUberlongMessage() {
		$comment = new \OC\Comments\Comment();
		$msg = str_pad('', IComment::MAX_MESSAGE_LENGTH + 1, 'x');
		$comment->setMessage($msg);
	}



}
