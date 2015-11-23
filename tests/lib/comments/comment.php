<?php

class Test_Comments_Comment extends Test\TestCase
{

	public function testSettersValidInput() {
		$comment = new \OC\Comments\Comment();

		$id = 'comment23';
		$parentId = 'comment11.5';
		$childrenCount = 6;
		$message = 'I like to comment comment';
		$verb = 'comment';
		$actor = ['type' => 'user', 'id' => 'alice'];
		$creationDT = new \DateTime();
		$latestChildDT = new \DateTime('yesterday');
		$object = ['type' => 'file', 'id' => 'file64'];

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

	public function testSetIdIllegalInput() {
		$comment = new \OC\Comments\Comment();

		$this->setExpectedException('\OCP\Comments\IllegalIDChangeException');
		$comment->setId('c23');
		$comment->setId('c17');
	}

	public function testResetId() {
		$comment = new \OC\Comments\Comment();
		$comment->setId('c23');
		$comment->setId('');
	}

	public function simpleSetterProvider() {
		return [
			['Id'],
			['ParentId'],
			['Message'],
			['Verb'],
			['ChildrenCount'],
		];
	}

	/**
	 * @dataProvider simpleSetterProvider
	 */
	public function testSimpleSetterInvalidInput($field) {
		$comment = new \OC\Comments\Comment();
		$setter = 'set' . $field;

		$this->setExpectedException('InvalidArgumentException');
		// we have no field that is supposed to accept a Bool
		$comment->$setter(true);
	}

	public function roleSetterProvider() {
		return [
			['Actor', true, true],
			['Actor', 'user', true],
			['Actor', true, 'alice'],
			['Object', true, true],
			['Object', 'file', true],
			['Object', true, 'file64'],
		];
	}

	/**
	 * @dataProvider roleSetterProvider
	 */
	public function testSetRoleInvalidInput($role, $type, $id){
		$comment = new \OC\Comments\Comment();
		$setter = 'set' . $role;
		$this->setExpectedException('InvalidArgumentException');
		$comment->$setter($type, $id);
	}



}
