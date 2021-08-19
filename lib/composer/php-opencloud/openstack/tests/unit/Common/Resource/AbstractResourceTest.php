<?php

namespace OpenStack\Test\Common\Resource;

use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Utils;
use OpenStack\Common\Resource\AbstractResource;
use OpenStack\Common\Resource\Alias;
use OpenStack\Common\Resource\ResourceInterface;
use OpenStack\Test\TestCase;

class AbstractResourceTest extends TestCase
{
    /** @var TestResource */
    private $resource;

    public function setUp(): void
    {
        parent::setUp();

        $this->rootFixturesDir = __DIR__;

        $this->resource = new TestResource();
    }

    public function test_it_populates_from_response()
    {
        $response = new Response(200, ['Content-Type' => 'application/json'], Utils::streamFor(
            json_encode(['foo' => ['bar' => '1']])
        ));

        $this->resource->populateFromResponse($response);

        self::assertEquals('1', $this->resource->bar);
    }

    public function test_it_populates_datetimes_from_arrays()
    {
        $ca = new \DateTimeImmutable('2015');
        $ua = new \DateTimeImmutable('2016');

        $this->resource->populateFromArray(['created_at' => '2015']);
        $this->resource->populateFromArray(['updated_at' => '2016']);

        self::assertEquals($this->resource->createdAt, $ca);
        self::assertEquals($this->resource->updatedAt, $ua);
    }

    public function test_it_populates_model_objects_from_arrays()
    {
        $tr = new TestResource();
        $this->resource->populateFromArray(['child' => $tr]);

        self::assertEquals($this->resource->child, $tr);
    }

    public function test_it_populates_arrays_from_arrays()
    {
        $this->resource->populateFromArray(['children' => [$this->resource, $this->resource]]);

        self::assertInstanceOf(TestResource::class, $this->resource->children[0]);
    }

    public function test_it_gets_attrs()
    {
        $this->resource->bar = 'foo';

        self::assertEquals(['bar' => 'foo'], $this->resource->getAttrs(['bar']));
    }

    public function test_it_returns_a_model_instance()
    {
        self::assertInstanceOf(ResourceInterface::class, $this->resource->model(TestResource::class));
    }

    public function test_it_populates_models_from_response()
    {
        self::assertInstanceOf(ResourceInterface::class, $this->resource->model(TestResource::class, new Response(200)));
    }

    public function test_it_populates_models_from_arrays()
    {
        $data = [
            'bar'        => 'this-is-bar',
            'camel_attr' => 'this-is-camel-attr',
            'child'      => ['bar' => 'child-bar', 'camel_attr' => 'child-camel'],
            'children'   => [
                ['bar' => 'child1-bar', 'camel_attr' => 'child1-camel'],
                ['bar' => 'child2-bar', 'camel_attr' => 'child2-camel'],
            ],
        ];

        /** @var TestResource $model */
        $model = $this->resource->model(TestResource::class, $data);

        self::assertInstanceOf(ResourceInterface::class, $model);

        self::assertEquals('this-is-bar', $model->bar);
        self::assertEquals('this-is-camel-attr', $model->camelAttr);

        $child = $model->child;
        self::assertInstanceOf(TestResource::class, $child);
        self::assertEquals('child-bar', $child->bar);
        self::assertEquals('child-camel', $child->camelAttr);

        self::assertContainsOnlyInstancesOf(TestResource::class, $model->children);
        self::assertCount(2, $model->children);
        self::assertEquals('child1-bar', $model->children[0]->bar);
        self::assertEquals('child1-camel', $model->children[0]->camelAttr);
        self::assertEquals('child2-bar', $model->children[1]->bar);
        self::assertEquals('child2-camel', $model->children[1]->camelAttr);

    }
}

class TestResource extends AbstractResource
{
    protected $resourceKey = 'foo';

    /** @var string */
    public $bar;

    /** @var string */
    public $camelAttr;

    public $id;

    /** @var \DateTimeImmutable */
    public $createdAt;

    /** @var \DateTimeImmutable */
    public $updatedAt;

    /** @var []TestResource */
    public $children;

    /** @var TestResource */
    public $child;

    protected $aliases = [
        'camel_attr'  => 'camelAttr',
    ];

    protected function getAliases(): array
    {
        return parent::getAliases() + [
                'created_at' => new Alias('createdAt', \DateTimeImmutable::class),
                'updated_at' => new Alias('updatedAt', \DateTimeImmutable::class),
                'child'      => new Alias('child', TestResource::class),
                'children'   => new Alias('children', TestResource::class, true),
            ];
    }

    public function getAttrs(array $keys)
    {
        return parent::getAttrs($keys);
    }
}
