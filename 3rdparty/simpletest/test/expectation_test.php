<?php
// $Id: expectation_test.php 2009 2011-04-28 08:57:25Z pp11 $
require_once(dirname(__FILE__) . '/../autorun.php');
require_once(dirname(__FILE__) . '/../expectation.php');

class TestOfEquality extends UnitTestCase {

    function testBoolean() {
        $is_true = new EqualExpectation(true);
        $this->assertTrue($is_true->test(true));
        $this->assertFalse($is_true->test(false));
    }

    function testStringMatch() {
        $hello = new EqualExpectation("Hello");
        $this->assertTrue($hello->test("Hello"));
        $this->assertFalse($hello->test("Goodbye"));
    }

    function testInteger() {
        $fifteen = new EqualExpectation(15);
        $this->assertTrue($fifteen->test(15));
        $this->assertFalse($fifteen->test(14));
    }

    function testFloat() {
        $pi = new EqualExpectation(3.14);
        $this->assertTrue($pi->test(3.14));
        $this->assertFalse($pi->test(3.15));
    }

    function testArray() {
        $colours = new EqualExpectation(array("r", "g", "b"));
        $this->assertTrue($colours->test(array("r", "g", "b")));
        $this->assertFalse($colours->test(array("g", "b", "r")));
    }

    function testHash() {
        $is_blue = new EqualExpectation(array("r" => 0, "g" => 0, "b" => 255));
        $this->assertTrue($is_blue->test(array("r" => 0, "g" => 0, "b" => 255)));
        $this->assertFalse($is_blue->test(array("r" => 0, "g" => 255, "b" => 0)));
    }

    function testHashWithOutOfOrderKeysShouldStillMatch() {
        $any_order = new EqualExpectation(array('a' => 1, 'b' => 2));
        $this->assertTrue($any_order->test(array('b' => 2, 'a' => 1)));
    }
}

class TestOfWithin extends UnitTestCase {

    function testWithinFloatingPointMargin() {
        $within = new WithinMarginExpectation(1.0, 0.2);
        $this->assertFalse($within->test(0.7));
        $this->assertTrue($within->test(0.8));
        $this->assertTrue($within->test(0.9));
        $this->assertTrue($within->test(1.1));
        $this->assertTrue($within->test(1.2));
        $this->assertFalse($within->test(1.3));
    }

    function testOutsideFloatingPointMargin() {
        $within = new OutsideMarginExpectation(1.0, 0.2);
        $this->assertTrue($within->test(0.7));
        $this->assertFalse($within->test(0.8));
        $this->assertFalse($within->test(1.2));
        $this->assertTrue($within->test(1.3));
    }
}

class TestOfInequality extends UnitTestCase {

    function testStringMismatch() {
        $not_hello = new NotEqualExpectation("Hello");
        $this->assertTrue($not_hello->test("Goodbye"));
        $this->assertFalse($not_hello->test("Hello"));
    }
}

class RecursiveNasty {
    private $me;

    function RecursiveNasty() {
        $this->me = $this;
    }
}

class OpaqueContainer {
    private $stuff;
    private $value;

    public function __construct($value) {
        $this->value = $value;
    }
}

class DerivedOpaqueContainer extends OpaqueContainer {
    // Deliberately have a variable whose name with the same suffix as a later
    // variable
    private $new_value = 1;

    // Deliberately obscures the variable of the same name in the base
    // class.
    private $value;

    public function __construct($value, $base_value) {
        parent::__construct($base_value);
        $this->value = $value;
    }
}

class TestOfIdentity extends UnitTestCase {

    function testType() {
        $string = new IdenticalExpectation("37");
        $this->assertTrue($string->test("37"));
        $this->assertFalse($string->test(37));
        $this->assertFalse($string->test("38"));
    }

    function _testNastyPhp5Bug() {
        $this->assertFalse(new RecursiveNasty() != new RecursiveNasty());
    }

    function _testReallyHorribleRecursiveStructure() {
        $hopeful = new IdenticalExpectation(new RecursiveNasty());
        $this->assertTrue($hopeful->test(new RecursiveNasty()));
    }

    function testCanComparePrivateMembers() {
        $expectFive = new IdenticalExpectation(new OpaqueContainer(5));
        $this->assertTrue($expectFive->test(new OpaqueContainer(5)));
        $this->assertFalse($expectFive->test(new OpaqueContainer(6)));
    }

    function testCanComparePrivateMembersOfObjectsInArrays() {
        $expectFive = new IdenticalExpectation(array(new OpaqueContainer(5)));
        $this->assertTrue($expectFive->test(array(new OpaqueContainer(5))));
        $this->assertFalse($expectFive->test(array(new OpaqueContainer(6))));
    }

    function testCanComparePrivateMembersOfObjectsWherePrivateMemberOfBaseClassIsObscured() {
        $expectFive = new IdenticalExpectation(array(new DerivedOpaqueContainer(1,2)));
        $this->assertTrue($expectFive->test(array(new DerivedOpaqueContainer(1,2))));
        $this->assertFalse($expectFive->test(array(new DerivedOpaqueContainer(0,2))));
        $this->assertFalse($expectFive->test(array(new DerivedOpaqueContainer(0,9))));
        $this->assertFalse($expectFive->test(array(new DerivedOpaqueContainer(1,0))));
    }
}

class TransparentContainer {
    public $value;

    public function __construct($value) {
        $this->value = $value;
    }
}

class TestOfMemberComparison extends UnitTestCase {

    function testMemberExpectationCanMatchPublicMember() {
        $expect_five = new MemberExpectation('value', 5);
        $this->assertTrue($expect_five->test(new TransparentContainer(5)));
        $this->assertFalse($expect_five->test(new TransparentContainer(8)));
    }

    function testMemberExpectationCanMatchPrivateMember() {
        $expect_five = new MemberExpectation('value', 5);
        $this->assertTrue($expect_five->test(new OpaqueContainer(5)));
        $this->assertFalse($expect_five->test(new OpaqueContainer(8)));
    }

    function testMemberExpectationCanMatchPrivateMemberObscuredByDerivedClass() {
        $expect_five = new MemberExpectation('value', 5);
        $this->assertTrue($expect_five->test(new DerivedOpaqueContainer(5,8)));
        $this->assertTrue($expect_five->test(new DerivedOpaqueContainer(5,5)));
        $this->assertFalse($expect_five->test(new DerivedOpaqueContainer(8,8)));
        $this->assertFalse($expect_five->test(new DerivedOpaqueContainer(8,5)));
    }

}

class DummyReferencedObject{}

class TestOfReference extends UnitTestCase {

    function testReference() {
        $foo = "foo";
        $ref = &$foo;
        $not_ref = $foo;
        $bar = "bar";

        $expect = new ReferenceExpectation($foo);
        $this->assertTrue($expect->test($ref));
        $this->assertFalse($expect->test($not_ref));
        $this->assertFalse($expect->test($bar));
    }
}

class TestOfNonIdentity extends UnitTestCase {

    function testType() {
        $string = new NotIdenticalExpectation("37");
        $this->assertTrue($string->test("38"));
        $this->assertTrue($string->test(37));
        $this->assertFalse($string->test("37"));
    }
}

class TestOfPatterns extends UnitTestCase {

    function testWanted() {
        $pattern = new PatternExpectation('/hello/i');
        $this->assertTrue($pattern->test("Hello world"));
        $this->assertFalse($pattern->test("Goodbye world"));
    }

    function testUnwanted() {
        $pattern = new NoPatternExpectation('/hello/i');
        $this->assertFalse($pattern->test("Hello world"));
        $this->assertTrue($pattern->test("Goodbye world"));
    }
}

class ExpectedMethodTarget {
    function hasThisMethod() {}
}

class TestOfMethodExistence extends UnitTestCase {

    function testHasMethod() {
        $instance = new ExpectedMethodTarget();
        $expectation = new MethodExistsExpectation('hasThisMethod');
        $this->assertTrue($expectation->test($instance));
        $expectation = new MethodExistsExpectation('doesNotHaveThisMethod');
        $this->assertFalse($expectation->test($instance));
    }
}

class TestOfIsA extends UnitTestCase {

    function testString() {
        $expectation = new IsAExpectation('string');
        $this->assertTrue($expectation->test('Hello'));
        $this->assertFalse($expectation->test(5));
    }

    function testBoolean() {
        $expectation = new IsAExpectation('boolean');
        $this->assertTrue($expectation->test(true));
        $this->assertFalse($expectation->test(1));
    }

    function testBool() {
        $expectation = new IsAExpectation('bool');
        $this->assertTrue($expectation->test(true));
        $this->assertFalse($expectation->test(1));
    }

    function testDouble() {
        $expectation = new IsAExpectation('double');
        $this->assertTrue($expectation->test(5.0));
        $this->assertFalse($expectation->test(5));
    }

    function testFloat() {
        $expectation = new IsAExpectation('float');
        $this->assertTrue($expectation->test(5.0));
        $this->assertFalse($expectation->test(5));
    }

    function testReal() {
        $expectation = new IsAExpectation('real');
        $this->assertTrue($expectation->test(5.0));
        $this->assertFalse($expectation->test(5));
    }

    function testInteger() {
        $expectation = new IsAExpectation('integer');
        $this->assertTrue($expectation->test(5));
        $this->assertFalse($expectation->test(5.0));
    }

    function testInt() {
        $expectation = new IsAExpectation('int');
        $this->assertTrue($expectation->test(5));
        $this->assertFalse($expectation->test(5.0));
    }

    function testScalar() {
        $expectation = new IsAExpectation('scalar');
        $this->assertTrue($expectation->test(5));
        $this->assertFalse($expectation->test(array(5)));
    }

    function testNumeric() {
        $expectation = new IsAExpectation('numeric');
        $this->assertTrue($expectation->test(5));
        $this->assertFalse($expectation->test('string'));
    }

    function testNull() {
        $expectation = new IsAExpectation('null');
        $this->assertTrue($expectation->test(null));
        $this->assertFalse($expectation->test('string'));
    }
}

class TestOfNotA extends UnitTestCase {

    function testString() {
        $expectation = new NotAExpectation('string');
        $this->assertFalse($expectation->test('Hello'));
        $this->assertTrue($expectation->test(5));
    }
}
?>