<?php
// $Id: cookies_test.php 1506 2007-05-07 00:58:03Z lastcraft $
require_once(dirname(__FILE__) . '/../autorun.php');
require_once(dirname(__FILE__) . '/../arguments.php');

class TestOfCommandLineArgumentParsing extends UnitTestCase {
    function testArgumentListWithJustProgramNameGivesFalseToEveryName() {
        $arguments = new SimpleArguments(array('me'));
        $this->assertIdentical($arguments->a, false);
        $this->assertIdentical($arguments->all(), array());
    }
    
    function testSingleArgumentNameRecordedAsTrue() {
        $arguments = new SimpleArguments(array('me', '-a'));
        $this->assertIdentical($arguments->a, true);
    }
    
    function testSingleArgumentCanBeGivenAValue() {
        $arguments = new SimpleArguments(array('me', '-a=AAA'));
        $this->assertIdentical($arguments->a, 'AAA');
    }
    
    function testSingleArgumentCanBeGivenSpaceSeparatedValue() {
        $arguments = new SimpleArguments(array('me', '-a', 'AAA'));
        $this->assertIdentical($arguments->a, 'AAA');
    }
    
    function testWillBuildArrayFromRepeatedValue() {
        $arguments = new SimpleArguments(array('me', '-a', 'A', '-a', 'AA'));
        $this->assertIdentical($arguments->a, array('A', 'AA'));
    }
    
    function testWillBuildArrayFromMultiplyRepeatedValues() {
        $arguments = new SimpleArguments(array('me', '-a', 'A', '-a', 'AA', '-a', 'AAA'));
        $this->assertIdentical($arguments->a, array('A', 'AA', 'AAA'));
    }
    
    function testCanParseLongFormArguments() {
        $arguments = new SimpleArguments(array('me', '--aa=AA', '--bb', 'BB'));
        $this->assertIdentical($arguments->aa, 'AA');
        $this->assertIdentical($arguments->bb, 'BB');
    }
    
    function testGetsFullSetOfResultsAsHash() {
        $arguments = new SimpleArguments(array('me', '-a', '-b=1', '-b', '2', '--aa=AA', '--bb', 'BB', '-c'));
        $this->assertEqual($arguments->all(),
                           array('a' => true, 'b' => array('1', '2'), 'aa' => 'AA', 'bb' => 'BB', 'c' => true));
    }
}

class TestOfHelpOutput extends UnitTestCase {
    function testDisplaysGeneralHelpBanner() {
        $help = new SimpleHelp('Cool program');
        $this->assertEqual($help->render(), "Cool program\n");
    }
    
    function testDisplaysOnlySingleLineEndings() {
        $help = new SimpleHelp("Cool program\n");
        $this->assertEqual($help->render(), "Cool program\n");
    }
    
    function testDisplaysHelpOnShortFlag() {
        $help = new SimpleHelp('Cool program');
        $help->explainFlag('a', 'Enables A');
        $this->assertEqual($help->render(), "Cool program\n-a    Enables A\n");
    }
    
    function testHasAtleastFourSpacesAfterLongestFlag() {
        $help = new SimpleHelp('Cool program');
        $help->explainFlag('a', 'Enables A');
        $help->explainFlag('long', 'Enables Long');
        $this->assertEqual($help->render(),
                           "Cool program\n-a        Enables A\n--long    Enables Long\n");
    }
    
    function testCanDisplaysMultipleFlagsForEachOption() {
        $help = new SimpleHelp('Cool program');
        $help->explainFlag(array('a', 'aa'), 'Enables A');
        $this->assertEqual($help->render(), "Cool program\n-a      Enables A\n  --aa\n");
    }
}
?>