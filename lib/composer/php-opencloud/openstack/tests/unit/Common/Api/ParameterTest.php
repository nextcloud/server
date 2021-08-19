<?php

namespace OpenStack\Test\Common\Api;

use OpenStack\Common\Api\Parameter;
use OpenStack\Test\Fixtures\ComputeV2Api;

class ParameterTest extends \PHPUnit\Framework\TestCase
{
    private $param;
    private $data;
    private $api;

    public function setUp(): void
    {
        $this->api = new ComputeV2Api();

        $this->data = $this->api->postServer()['params']['name'] + ['name' => 'name'];
        $this->param = new Parameter($this->data);
    }

    public function test_exception_is_thrown_for_invalid_locations()
    {
        $data = $this->data;
        $data['location'] = 'foo';
		$this->expectException(\RuntimeException::class);
        new Parameter($data);
    }

    public function test_it_should_provide_access_to_a_name()
    {
        self::assertEquals($this->data['name'], $this->param->getName());
    }

    public function test_it_should_use_sentAs_alias_for_name_if_one_is_set()
    {
        $data = $this->data + ['sentAs' => 'foo'];
        $param = new Parameter($data);

        self::assertEquals($data['sentAs'], $param->getName());
    }

    public function test_it_indicates_whether_it_is_required_or_not()
    {
        self::assertTrue($this->param->isRequired());
    }

    public function test_it_indicates_its_item_schema()
    {
        $data = $this->api->postServer()['params']['networks'] + ['name' => 'networks'];
        $param = new Parameter($data);

        self::assertInstanceOf(Parameter::class, $param->getItemSchema());
    }

    public function test_it_allows_property_retrieval()
    {
        $definition = $this->api->postServer()['params']['networks']['items'] + ['name' => 'network'];
        $param = new Parameter($definition);

        self::assertInstanceOf(Parameter::class, $param->getProperty('uuid'));
    }

    public function test_it_indicates_its_path()
    {
        $path = 'foo.bar.baz';
        $param = new Parameter($this->data + ['path' => $path]);

        self::assertEquals($path, $param->getPath());
    }

    public function test_it_verifies_a_given_location_with_a_boolean()
    {
        self::assertFalse($this->param->hasLocation('foo'));
        self::assertTrue($this->param->hasLocation('json'));
    }

    public function test_it_should_return_true_when_required_attributes_are_provided_and_match_their_definitions()
    {
        self::assertTrue($this->param->validate('TestName'));
    }

    public function test_it_throws_exception_when_values_do_not_match_their_definition_types()
    {
        $data = $this->api->postServer()['params']['networks'] + ['name' => 'networks'];
        $param = new Parameter($data);
		$this->expectException(\Exception::class);

        $param->validate('a_network!'); // should be an array
    }

    public function test_it_throws_exception_when_deeply_nested_values_have_wrong_types()
    {
        $data = $this->api->postServer()['params']['networks'] + ['name' => 'networks'];
        $param = new Parameter($data);
		$this->expectException(\Exception::class);

        $param->validate(['name' => false]); // value should be a string, not bool
    }

    public function test_metadata_properties_are_handled_differently()
    {
        $params = [
            'name'       => 'metadata',
            'type'       => 'object',
            'properties' => [
                'type' => 'string',
            ],
        ];

        $userValues = ['some' => 'value'];

        $param = new Parameter($params);
        self::assertTrue($param->validate($userValues));
    }

    public function test_it_passes_validation_when_array_values_pass()
    {
        $params = [
            'name'  => 'foo',
            'type'  => 'array',
            'items' => ['type' => 'string'],
        ];

        $userVals = ['1', '2', '3'];

        $param = new Parameter($params);
        self::assertTrue($param->validate($userVals));
    }

    public function test_an_exception_is_thrown_when_an_undefined_property_is_provided()
    {
        $params = ['type' => 'object', 'properties' => ['foo' => ['type' => 'string']]];
        $userVals = ['bar' => 'baz'];
        $param = new Parameter($params);
		$this->expectException(\Exception::class);

        $param->validate($userVals);
    }

    public function test_it_passes_validation_when_all_subproperties_pass()
    {
        $params = ['type' => 'object', 'properties' => ['foo' => ['type' => 'string']]];
        $userVals = ['foo' => 'baz'];

        $param = new Parameter($params);
        self::assertTrue($param->validate($userVals));
    }

    public function test_it_sets_name()
    {
        $this->param->setName('foo');
        self::assertEquals($this->param->getName(), 'foo');
    }

    public function test_it_gets_property()
    {
        $property = new Parameter([
            'name'       => 'metadata',
            'properties' => [
                'type'   => 'string',
                'prefix' => 'foo',
            ],
        ]);

        $prop = $property->getProperty('metadata');

        self::assertInstanceOf(Parameter::class, $prop);
        self::assertEquals('foo', $prop->getPrefix());
    }

    public function test_it_gets_prefixed_name()
    {
        $property = new Parameter([
            'name'   => 'metadata',
            'prefix' => 'foo-',
        ]);

        self::assertEquals('foo-metadata', $property->getPrefixedName());
    }

    public function test_exception_is_thrown_when_value_is_not_in_enum_list()
    {
        $data = $this->data;
        $data['enum'] = ['foo'];
        $param = new Parameter($data);
		$this->expectException(\Exception::class);

        $param->validate('blah');
    }
}
