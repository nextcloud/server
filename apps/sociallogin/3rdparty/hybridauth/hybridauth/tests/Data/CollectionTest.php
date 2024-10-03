<?php

namespace HybridauthTest\Hybridauth\Data;

use Hybridauth\Data\Collection;

class CollectionTest extends \PHPUnit\Framework\TestCase
{
    public function some_random_id()
    {
        return 69;
    }

    public function some_random_year()
    {
        return 2020;
    }

    public function some_random_array()
    {
        return ['id' => 69, 'slugs' => ['Γεια σας', 'Bonjour', '안녕하세요', 'year' => 2020]];
    }

    public function some_random_object()
    {
        $object = new \StdClass();
        $object->id = 69;
        $object->slugs = ['Γεια σας', 'Bonjour', '안녕하세요', 'year' => 2020];

        return $object;
    }

    public function test_instance_of()
    {
        $collection = new Collection();

        $this->assertInstanceOf('\\Hybridauth\\Data\\Collection', $collection);
    }

    public function test_identity()
    {
        $array = $this->some_random_array();

        $collection = new Collection($array);

        $result = $collection->toArray();

        $this->assertEquals($result, $array);
    }

    /**
     * @covers Collection::exists
     */
    public function test_exists()
    {
        $array = $this->some_random_array();

        $collection = new Collection($array);

        $this->assertTrue($collection->exists('id'));

        $this->assertFalse($collection->exists('_non_existant_'));

        //

        $object = $this->some_random_object();

        $collection = new Collection($object);

        $this->assertTrue($collection->exists('id'));

        $this->assertFalse($collection->exists('_non_existant_'));
    }

    /**
     * @covers Collection::get
     */
    public function test_get()
    {
        $array = $this->some_random_array();

        $collection = new Collection($array);

        $this->assertEquals($collection->get('id'), $this->some_random_id());

        $this->assertNull($collection->get('_non_existant_'));

        //

        $object = $this->some_random_object();

        $collection = new Collection($object);

        $this->assertEquals($collection->get('id'), $this->some_random_id());

        $this->assertNull($collection->get('_non_existant_'));
    }

    /**
     * @covers Collection::filter
     */
    public function test_filter()
    {
        $array = $this->some_random_array();

        $collection = new Collection($array);

        $this->assertInstanceOf('\\Hybridauth\\Data\\Collection', $collection->filter('id'));
        $this->assertInstanceOf('\\Hybridauth\\Data\\Collection', $collection->filter('slugs'));
        $this->assertInstanceOf('\\Hybridauth\\Data\\Collection', $collection->filter('_non_existant_'));

        $this->assertNull($collection->filter('slugs')->get('_non_existant_'));

        $this->assertEquals($collection->filter('slugs')->get('year'), $this->some_random_year());

        //

        $object = $this->some_random_object();

        $collection = new Collection($object);

        $this->assertInstanceOf('\\Hybridauth\\Data\\Collection', $collection->filter('id'));
        $this->assertInstanceOf('\\Hybridauth\\Data\\Collection', $collection->filter('slugs'));
        $this->assertInstanceOf('\\Hybridauth\\Data\\Collection', $collection->filter('_non_existant_'));

        $this->assertNull($collection->filter('slugs')->get('_non_existant_'));

        $this->assertEquals($collection->filter('slugs')->get('year'), $this->some_random_year());
    }
}
