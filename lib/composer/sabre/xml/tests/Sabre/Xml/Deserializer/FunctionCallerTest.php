<?php

declare(strict_types=1);

namespace Sabre\Xml\Deserializer;

use
    Sabre\Xml\Reader;

class FunctionCallerTest extends \PHPUnit\Framework\TestCase
{
    public function testDeserializeFunctionCaller()
    {
        $input = <<<XML
<?xml version="1.0"?>
<person xmlns="urn:foo">
 <name>John</name>
 <age>18</age>
 <address>
  <street>X</street>
  <number>12</number>
 </address>
 <languages>
  <language>
   <value>English</value>
  </language>
  <language>
   <value>Portuguese</value>
  </language>
  <language>
   <value>German</value>
  </language>
 </languages>
</person>
XML;

        $reader = new Reader();
        $reader->xml($input);
        $reader->elementMap['{urn:foo}person'] = function (Reader $reader) {
            return functionCaller($reader, [Person::class, 'fromXml'], 'urn:foo');
        };
        $reader->elementMap['{urn:foo}address'] = function (Reader $reader) {
            return functionCaller($reader, [Address::class, 'fromXml'], 'urn:foo');
        };
        $reader->elementMap['{urn:foo}language'] = function (Reader $reader) {
            return functionCaller($reader, [Language::class, 'fromXml'], 'urn:foo');
        };
        $reader->elementMap['{urn:foo}languages'] = function (Reader $reader) {
            return repeatingElements($reader, '{urn:foo}language');
        };

        $output = $reader->parse();

        $person = new Person(
            'John',
            18,
            new Address('X', 12),
            [new Language('English'), new Language('Portuguese'), new Language('German')]
        );

        $expected = [
            'name' => '{urn:foo}person',
            'value' => $person,
            'attributes' => [],
        ];

        $this->assertEquals(
            $expected,
            $output
        );
    }

    public function testDeserializeFunctionCallerWithDifferentTypesOfCallable()
    {
        $input = <<<XML
<?xml version="1.0"?>
<person xmlns="urn:foo">
 <name>John</name>
 <age>18</age>
 <address>
  <street>X</street>
  <number>12</number>
 </address>
 <languages>
  <language>
   <value>English</value>
  </language>
  <language>
   <value>Portuguese</value>
  </language>
  <language>
   <value>German</value>
  </language>
 <language />
 </languages>
</person>
XML;

        $reader = new Reader();
        $reader->xml($input);
        $reader->elementMap['{urn:foo}person'] = function (Reader $reader) {
            return functionCaller($reader, Person::class.'::fromXml', 'urn:foo');
        };
        $reader->elementMap['{urn:foo}address'] = function (Reader $reader) {
            return functionCaller($reader, __NAMESPACE__.'\newAddressFromXml', 'urn:foo');
        };
        $reader->elementMap['{urn:foo}language'] = function (Reader $reader) {
            return functionCaller($reader, function (string $value): Language {
                return new Language($value);
            }, 'urn:foo');
        };
        $reader->elementMap['{urn:foo}languages'] = function (Reader $reader) {
            return repeatingElements($reader, '{urn:foo}language');
        };

        $output = $reader->parse();

        $person = new Person(
            'John',
            18,
            new Address('X', 12),
            [new Language('English'), new Language('Portuguese'), new Language('German'), null]
        );

        $expected = [
            'name' => '{urn:foo}person',
            'value' => $person,
            'attributes' => [],
        ];

        $this->assertEquals(
            $expected,
            $output
        );
    }
}

final class Person
{
    private $name;
    private $age;
    private $address;
    private $languages = [];

    public function __construct(string $name, int $age, Address $address, array $languages)
    {
        $this->name = $name;
        $this->age = $age;
        $this->address = $address;
        $this->languages = $languages;
    }

    public static function fromXml(string $name, string $age, Address $address, array $languages): self
    {
        return new self($name, (int) $age, $address, $languages);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getAge(): int
    {
        return $this->age;
    }

    public function getAddress(): Address
    {
        return $this->address;
    }

    public function getLanguages(): array
    {
        return $this->languages;
    }
}
final class Address
{
    private $street;
    private $number;

    public function __construct(string $street, int $number)
    {
        $this->street = $street;
        $this->number = $number;
    }

    public static function fromXml(string $street, string $number): self
    {
        return new self($street, (int) $number);
    }

    public function getStreet(): string
    {
        return $this->street;
    }

    public function getNumber(): int
    {
        return $this->number;
    }
}
final class Language
{
    private $value;

    public function __construct(string $value)
    {
        $this->value = $value;
    }

    public static function fromXml(string $value): self
    {
        return new self($value);
    }

    public function getValue(): string
    {
        return $this->value;
    }
}

function newAddressFromXml(string $street, string $number): Address
{
    return new Address($street, (int) $number);
}
