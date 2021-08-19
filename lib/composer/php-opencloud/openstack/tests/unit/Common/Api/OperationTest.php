<?php

namespace OpenStack\Test\Common\Api;

use OpenStack\Common\Api\Operation;
use OpenStack\Common\Api\Parameter;
use OpenStack\Test\Fixtures\ComputeV2Api;

class OperationTest extends \PHPUnit\Framework\TestCase
{
    private $operation;

    public function setUp(): void
    {
        $def = (new ComputeV2Api())->postServer();

        $this->operation = new Operation($def);
    }

    public function test_it_reveals_whether_params_are_set_or_not()
    {
        self::assertFalse($this->operation->hasParam('foo'));
        self::assertTrue($this->operation->hasParam('name'));
    }

    public function test_it_gets_params()
    {
        self::assertInstanceOf(Parameter::class, $this->operation->getParam('name'));
    }

    public function test_it_validates_params()
    {
        self::assertTrue($this->operation->validate([
            'name'     => 'foo',
            'imageId'  => 'bar',
            'flavorId' => 'baz',
        ]));
    }

    public function test_exceptions_are_propagated()
    {
		$this->expectException(\Exception::class);
        self::assertFalse($this->operation->validate([
            'name'     => true,
            'imageId'  => 'bar',
            'flavorId' => 'baz',
        ]));
    }

    public function test_an_exception_is_thrown_when_user_does_not_provide_required_options()
    {
		$this->expectException(\Exception::class);
        $this->operation->validate([]);
    }

    public function test_it_throws_exception_when_user_provides_undefined_options()
    {
        $userData = ['name' => 'new_server', 'undefined_opt' => 'bah'];
		$this->expectException(\Exception::class);

        $this->operation->validate($userData);
    }

    public function test_it_gets_json_key()
    {
        self::assertEquals('server', $this->operation->getJsonKey());
    }
}
