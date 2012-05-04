<?php
// $Id: page_test.php 1912 2009-07-29 16:39:17Z lastcraft $
require_once(dirname(__FILE__) . '/../autorun.php');
require_once(dirname(__FILE__) . '/../page.php');
require_once(dirname(__FILE__) . '/../php_parser.php');
require_once(dirname(__FILE__) . '/../tidy_parser.php');
Mock::generate('SimpleHttpResponse');

abstract class TestOfParsing extends UnitTestCase {

    function testRawAccessor() {
        $page = $this->whenVisiting('http://host/', 'Raw HTML');
        $this->assertEqual($page->getRaw(), 'Raw HTML');
    }

    function testTextAccessor() {
        $page = $this->whenVisiting('http://host/', '<b>Some</b> &quot;messy&quot; HTML');
        $this->assertEqual($page->getText(), 'Some "messy" HTML');
    }

    function testFramesetAbsence() {
        $page = $this->whenVisiting('http://here/', '');
        $this->assertFalse($page->hasFrames());
        $this->assertIdentical($page->getFrameset(), false);
    }

    function testPageWithNoUrlsGivesEmptyArrayOfLinks() {
        $page = $this->whenVisiting('http://here/', '<html><body><p>Stuff</p></body></html>');
        $this->assertIdentical($page->getUrls(), array());
    }

    function testAddAbsoluteLink() {
        $page = $this->whenVisiting('http://host',
                                    '<html><a href="http://somewhere.com">Label</a></html>');
        $this->assertEqual(
                $page->getUrlsByLabel('Label'),
                array(new SimpleUrl('http://somewhere.com')));
    }

    function testUrlLabelsHaveHtmlTagsStripped() {
        $page = $this->whenVisiting('http://host',
                                    '<html><a href="http://somewhere.com"><b>Label</b></a></html>');
        $this->assertEqual(
                $page->getUrlsByLabel('Label'),
                array(new SimpleUrl('http://somewhere.com')));
    }

    function testAddStrictRelativeLink() {
        $page = $this->whenVisiting('http://host',
                                    '<html><a href="./somewhere.php">Label</a></html>');
        $this->assertEqual(
                $page->getUrlsByLabel('Label'),
                array(new SimpleUrl('http://host/somewhere.php')));
    }

    function testAddBareRelativeLink() {
        $page = $this->whenVisiting('http://host',
                                    '<html><a href="somewhere.php">Label</a></html>');
        $this->assertEqual(
                $page->getUrlsByLabel('Label'),
                array(new SimpleUrl('http://host/somewhere.php')));
    }

    function testAddRelativeLinkWithBaseTag() {
        $raw = '<html><head><base href="http://www.lastcraft.com/stuff/"></head>' .
               '<body><a href="somewhere.php">Label</a></body>' .
               '</html>';
        $page = $this->whenVisiting('http://host', $raw);
        $this->assertEqual(
                $page->getUrlsByLabel('Label'),
                array(new SimpleUrl('http://www.lastcraft.com/stuff/somewhere.php')));
    }

    function testAddAbsoluteLinkWithBaseTag() {
        $raw = '<html><head><base href="http://www.lastcraft.com/stuff/"></head>' .
               '<body><a href="http://here.com/somewhere.php">Label</a></body>' .
               '</html>';
        $page = $this->whenVisiting('http://host', $raw);
        $this->assertEqual(
                $page->getUrlsByLabel('Label'),
                array(new SimpleUrl('http://here.com/somewhere.php')));
    }

    function testCanFindLinkInsideForm() {
        $raw = '<html><body><form><a href="./somewhere.php">Label</a></form></body></html>';
        $page = $this->whenVisiting('http://host', $raw);
        $this->assertEqual(
                $page->getUrlsByLabel('Label'),
                array(new SimpleUrl('http://host/somewhere.php')));
    }

    function testCanGetLinksByIdOrLabel() {
        $raw = '<html><body><a href="./somewhere.php" id="33">Label</a></body></html>';
        $page = $this->whenVisiting('http://host', $raw);
        $this->assertEqual(
                $page->getUrlsByLabel('Label'),
                array(new SimpleUrl('http://host/somewhere.php')));
        $this->assertFalse($page->getUrlById(0));
        $this->assertEqual(
                $page->getUrlById(33),
                new SimpleUrl('http://host/somewhere.php'));
    }

    function testCanFindLinkByNormalisedLabel() {
        $raw = '<html><body><a href="./somewhere.php" id="33"><em>Long &amp; thin</em></a></body></html>';
        $page = $this->whenVisiting('http://host', $raw);
        $this->assertEqual(
                $page->getUrlsByLabel('Long & thin'),
                array(new SimpleUrl('http://host/somewhere.php')));
    }

    function testCanFindLinkByImageAltText() {
        $raw = '<a href="./somewhere.php" id="33"><img src="pic.jpg" alt="&lt;A picture&gt;"></a>';
        $page = $this->whenVisiting('http://host', $raw);
        $this->assertEqual(
                array_map(array($this, 'urlToString'), $page->getUrlsByLabel('<A picture>')),
                array('http://host/somewhere.php'));
    }

    function testTitle() {
        $page = $this->whenVisiting('http://host',
                                    '<html><head><title>Me</title></head></html>');
        $this->assertEqual($page->getTitle(), 'Me');
    }

    function testTitleWithEntityReference() {
        $page = $this->whenVisiting('http://host',
                                    '<html><head><Title>Me&amp;Me</TITLE></head></html>');
        $this->assertEqual($page->getTitle(), "Me&Me");
    }

    function testOnlyFramesInFramesetAreRecognised() {
        $raw =
            '<frameset>' .
            '  <frame src="2.html"></frame>' .
            '  <frame src="3.html"></frame>' .
            '</frameset>' .
            '<frame src="4.html"></frame>';
        $page = $this->whenVisiting('http://here', $raw);
        $this->assertTrue($page->hasFrames());
        $this->assertSameFrameset($page->getFrameset(), array(
                1 => new SimpleUrl('http://here/2.html'),
                2 => new SimpleUrl('http://here/3.html')));
    }

    function testReadsNamesInFrames() {
        $raw =
            '<frameset>' .
            '  <frame src="1.html"></frame>' .
            '  <frame src="2.html" name="A"></frame>' .
            '  <frame src="3.html" name="B"></frame>' .
            '  <frame src="4.html"></frame>' .
            '</frameset>';
        $page = $this->whenVisiting('http://here', $raw);
        $this->assertTrue($page->hasFrames());
        $this->assertSameFrameset($page->getFrameset(), array(
                1 => new SimpleUrl('http://here/1.html'),
                'A' => new SimpleUrl('http://here/2.html'),
                'B' => new SimpleUrl('http://here/3.html'),
                4 => new SimpleUrl('http://here/4.html')));
    }

    function testRelativeFramesRespectBaseTag() {
        $raw = '<base href="https://there.com/stuff/"><frameset><frame src="1.html"></frameset>';
        $page = $this->whenVisiting('http://here', $raw);
        $this->assertSameFrameset(
                $page->getFrameset(),
                array(1 => new SimpleUrl('https://there.com/stuff/1.html')));
    }

    function testSingleFrameInNestedFrameset() {
        $raw = '<html><frameset><frameset>' .
                '<frame src="a.html">' .
                '</frameset></frameset></html>';
        $page = $this->whenVisiting('http://host', $raw);
        $this->assertTrue($page->hasFrames());
        $this->assertIdentical(
                $page->getFrameset(),
                array(1 => new SimpleUrl('http://host/a.html')));
    }

    function testFramesCollectedWithNestedFramesetTags() {
        $raw = '<html><frameset>' .
                '<frame src="a.html">' .
                '<frameset><frame src="b.html"></frameset>' .
                '<frame src="c.html">' .
                '</frameset></html>';
        $page = $this->whenVisiting('http://host', $raw);
        $this->assertTrue($page->hasFrames());
        $this->assertIdentical($page->getFrameset(), array(
                1 => new SimpleUrl('http://host/a.html'),
                2 => new SimpleUrl('http://host/b.html'),
                3 => new SimpleUrl('http://host/c.html')));
    }

    function testNamedFrames() {
        $raw = '<html><frameset>' .
                '<frame src="a.html">' .
                '<frame name="_one" src="b.html">' .
                '<frame src="c.html">' .
                '<frame src="d.html" name="_two">' .
                '</frameset></html>';
        $page = $this->whenVisiting('http://host', $raw);
        $this->assertTrue($page->hasFrames());
        $this->assertIdentical($page->getFrameset(), array(
                1 => new SimpleUrl('http://host/a.html'),
                '_one' => new SimpleUrl('http://host/b.html'),
                3 => new SimpleUrl('http://host/c.html'),
                '_two' => new SimpleUrl('http://host/d.html')));
    }

    function testCanReadElementOfCompleteForm() {
        $raw = '<html><head><form>' .
                '<input type="text" name="here" value="Hello">' .
                '</form></head></html>';
        $page = $this->whenVisiting('http://host', $raw);
        $this->assertEqual($page->getField(new SimpleByName('here')), "Hello");
    }

    function testCanReadElementOfUnclosedForm() {
        $raw = '<html><head><form>' .
                '<input type="text" name="here" value="Hello">' .
                '</head></html>';
        $page = $this->whenVisiting('http://host', $raw);
        $this->assertEqual($page->getField(new SimpleByName('here')), "Hello");
    }

    function testCanReadElementByLabel() {
        $raw = '<html><head><form>' .
                '<label>Where<input type="text" name="here" value="Hello"></label>' .
                '</head></html>';
        $page = $this->whenVisiting('http://host', $raw);
        $this->assertEqual($page->getField(new SimpleByLabel('Where')), "Hello");
    }

    function testCanFindFormByLabel() {
        $raw = '<html><head><form><input type="submit"></form></head></html>';
        $page = $this->whenVisiting('http://host', $raw);
        $this->assertNull($page->getFormBySubmit(new SimpleByLabel('submit')));
        $this->assertNull($page->getFormBySubmit(new SimpleByName('submit')));
        $this->assertIsA(
                $page->getFormBySubmit(new SimpleByLabel('Submit')),
                'SimpleForm');
    }

    function testConfirmSubmitAttributesAreCaseSensitive() {
        $raw = '<html><head><FORM><INPUT TYPE="SUBMIT" NAME="S" VALUE="S"></FORM></head></html>';
        $page = $this->whenVisiting('http://host', $raw);
        $this->assertIsA(
                $page->getFormBySubmit(new SimpleByName('S')),
                'SimpleForm');
        $this->assertIsA(
                $page->getFormBySubmit(new SimpleByLabel('S')),
                'SimpleForm');
    }

    function testCanFindFormByImage() {
        $raw = '<html><head><form>' .
                '<input type="image" id=100 alt="Label" name="me">' .
                '</form></head></html>';
        $page = $this->whenVisiting('http://host', $raw);
        $this->assertIsA(
                $page->getFormByImage(new SimpleByLabel('Label')),
                'SimpleForm');
        $this->assertIsA(
                $page->getFormByImage(new SimpleByName('me')),
                'SimpleForm');
        $this->assertIsA(
                $page->getFormByImage(new SimpleById(100)),
                'SimpleForm');
    }

    function testCanFindFormByButtonTag() {
        $raw = '<html><head><form>' .
                '<button type="submit" name="b" value="B">BBB</button>' .
                '</form></head></html>';
        $page = $this->whenVisiting('http://host', $raw);
        $this->assertNull($page->getFormBySubmit(new SimpleByLabel('b')));
        $this->assertNull($page->getFormBySubmit(new SimpleByLabel('B')));
        $this->assertIsA(
                $page->getFormBySubmit(new SimpleByName('b')),
                'SimpleForm');
        $this->assertIsA(
                $page->getFormBySubmit(new SimpleByLabel('BBB')),
                'SimpleForm');
    }

    function testCanFindFormById() {
        $raw = '<html><head><form id="55"><input type="submit"></form></head></html>';
        $page = $this->whenVisiting('http://host', $raw);
        $this->assertNull($page->getFormById(54));
        $this->assertIsA($page->getFormById(55), 'SimpleForm');
    }

    function testFormCanBeSubmitted() {
        $raw = '<html><head><form method="GET" action="here.php">' .
                '<input type="submit" name="s" value="Submit">' .
                '</form></head></html>';
        $page = $this->whenVisiting('http://host', $raw);
        $form = $page->getFormBySubmit(new SimpleByLabel('Submit'));
        $this->assertEqual(
                $form->submitButton(new SimpleByLabel('Submit')),
                new SimpleGetEncoding(array('s' => 'Submit')));
    }

    function testUnparsedTagDoesNotCrash() {
        $raw = '<form><input type="reset" name="Clear"></form>';
        $this->whenVisiting('http://host', $raw);
    }

    function testReadingTextField() {
        $raw = '<html><head><form>' .
                '<input type="text" name="a">' .
                '<input type="text" name="b" value="bbb" id=3>' .
                '</form></head></html>';
        $page = $this->whenVisiting('http://host', $raw);
        $this->assertNull($page->getField(new SimpleByName('missing')));
        $this->assertIdentical($page->getField(new SimpleByName('a')), '');
        $this->assertIdentical($page->getField(new SimpleByName('b')), 'bbb');
    }

    function testEntitiesAreDecodedInDefaultTextFieldValue() {
        $raw = '<form><input type="text" name="a" value="&amp;\'&quot;&lt;&gt;"></form>';
        $page = $this->whenVisiting('http://host', $raw);
        $this->assertEqual($page->getField(new SimpleByName('a')), '&\'"<>');
    }

    function testReadingTextFieldIsCaseInsensitive() {
        $raw = '<html><head><FORM>' .
                '<INPUT TYPE="TEXT" NAME="a">' .
                '<INPUT TYPE="TEXT" NAME="b" VALUE="bbb" id=3>' .
                '</FORM></head></html>';
        $page = $this->whenVisiting('http://host', $raw);
        $this->assertNull($page->getField(new SimpleByName('missing')));
        $this->assertIdentical($page->getField(new SimpleByName('a')), '');
        $this->assertIdentical($page->getField(new SimpleByName('b')), 'bbb');
    }

    function testSettingTextField() {
        $raw = '<html><head><form>' .
                '<input type="text" name="a">' .
                '<input type="text" name="b" id=3>' .
                '<input type="submit">' .
                '</form></head></html>';
        $page = $this->whenVisiting('http://host', $raw);
        $this->assertTrue($page->setField(new SimpleByName('a'), 'aaa'));
        $this->assertEqual($page->getField(new SimpleByName('a')), 'aaa');
        $this->assertTrue($page->setField(new SimpleById(3), 'bbb'));
        $this->assertEqual($page->getField(new SimpleBYId(3)), 'bbb');
        $this->assertFalse($page->setField(new SimpleByName('z'), 'zzz'));
        $this->assertNull($page->getField(new SimpleByName('z')));
    }

    function testSettingTextFieldByEnclosingLabel() {
        $raw = '<html><head><form>' .
                '<label>Stuff' .
                '<input type="text" name="a" value="A">' .
                '</label>' .
                '</form></head></html>';
        $page = $this->whenVisiting('http://host', $raw);
        $this->assertEqual($page->getField(new SimpleByName('a')), 'A');
        $this->assertEqual($page->getField(new SimpleByLabel('Stuff')), 'A');
        $this->assertTrue($page->setField(new SimpleByLabel('Stuff'), 'aaa'));
        $this->assertEqual($page->getField(new SimpleByLabel('Stuff')), 'aaa');
    }

    function testLabelsWithoutForDoNotAttachToInputsWithNoId() {
        $raw = '<form action="network_confirm.php?x=X&y=Y" method="post">
            <label>Text A <input type="text" name="a" value="one"></label>
            <label>Text B <input type="text" name="b" value="two"></label>
        </form>';
        $page = $this->whenVisiting('http://host', $raw);
        $this->assertEqual($page->getField(new SimpleByLabelOrName('Text A')), 'one');
        $this->assertEqual($page->getField(new SimpleByLabelOrName('Text B')), 'two');
        $this->assertTrue($page->setField(new SimpleByLabelOrName('Text A'), '1'));
        $this->assertTrue($page->setField(new SimpleByLabelOrName('Text B'), '2'));
        $this->assertEqual($page->getField(new SimpleByLabelOrName('Text A')), '1');
        $this->assertEqual($page->getField(new SimpleByLabelOrName('Text B')), '2');
    }

    function testGettingTextFieldByEnclosingLabelWithConflictingOtherFields() {
        $raw = '<html><head><form>' .
                '<label>Stuff' .
                '<input type="text" name="a" value="A">' .
                '</label>' .
                '<input type="text" name="b" value="B">' .
                '</form></head></html>';
        $page = $this->whenVisiting('http://host', $raw);
        $this->assertEqual($page->getField(new SimpleByName('a')), 'A');
        $this->assertEqual($page->getField(new SimpleByName('b')), 'B');
        $this->assertEqual($page->getField(new SimpleByLabel('Stuff')), 'A');
    }

    function testSettingTextFieldByExternalLabel() {
        $raw = '<html><head><form>' .
                '<label for="aaa">Stuff</label>' .
                '<input id="aaa" type="text" name="a" value="A">' .
                '</form></head></html>';
        $page = $this->whenVisiting('http://host', $raw);
        $this->assertEqual($page->getField(new SimpleByLabel('Stuff')), 'A');
        $this->assertTrue($page->setField(new SimpleByLabel('Stuff'), 'aaa'));
        $this->assertEqual($page->getField(new SimpleByLabel('Stuff')), 'aaa');
    }

    function testReadingTextArea() {
        $raw = '<html><head><form>' .
                '<textarea name="a">aaa</textarea>' .
                '<input type="submit">' .
                '</form></head></html>';
        $page = $this->whenVisiting('http://host', $raw);
        $this->assertEqual($page->getField(new SimpleByName('a')), 'aaa');
    }

    function testEntitiesAreDecodedInTextareaValue() {
        $raw = '<form><textarea name="a">&amp;\'&quot;&lt;&gt;</textarea></form>';
        $page = $this->whenVisiting('http://host', $raw);
        $this->assertEqual($page->getField(new SimpleByName('a')), '&\'"<>');
    }

    function testNewlinesPreservedInTextArea() {
        $raw = "<form><textarea name=\"a\">hello\r\nworld</textarea></form>";
        $page = $this->whenVisiting('http://host', $raw);
        $this->assertEqual($page->getField(new SimpleByName('a')), "hello\r\nworld");
    }

    function testWhitespacePreservedInTextArea() {
        $raw = '<form><textarea name="a">     </textarea></form>';
        $page = $this->whenVisiting('http://host', $raw);
        $this->assertEqual($page->getField(new SimpleByName('a')), '     ');
    }

    function testComplexWhitespaceInTextArea() {
        $raw = "<html>\n" .
                "    <head><title></title></head>\n" .
                "    <body>\n" .
                "        <form>\n".
                "            <label>Text area C\n" .
                "                <textarea name='c'>\n" .
                "                </textarea>\n" .
                "            </label>\n" .
                "        </form>\n" .
                "    </body>\n" .
                "</html>";
        $page = $this->whenVisiting('http://host', $raw);
        $this->assertEqual($page->getField(new SimpleByName('c')), "                ");
    }

    function testSettingTextArea() {
        $raw = '<form>' .
                '<textarea name="a">aaa</textarea>' .
                '<input type="submit">' .
                '</form>';
        $page = $this->whenVisiting('http://host', $raw);
        $this->assertTrue($page->setField(new SimpleByName('a'), 'AAA'));
        $this->assertEqual($page->getField(new SimpleByName('a')), 'AAA');
    }

    function testDontIncludeTextAreaContentInLabel() {
        $raw = '<form><label>Text area C<textarea id=3 name="c">mouse</textarea></label></form>';
        $page = $this->whenVisiting('http://host', $raw);
        $this->assertEqual($page->getField(new SimpleByLabel('Text area C')), 'mouse');
    }

    function testSettingSelectionField() {
        $raw = '<form>' .
                '<select name="a">' .
                '<option>aaa</option>' .
                '<option selected>bbb</option>' .
                '</select>' .
                '<input type="submit">' .
                '</form>';
        $page = $this->whenVisiting('http://host', $raw);
        $this->assertEqual($page->getField(new SimpleByName('a')), 'bbb');
        $this->assertFalse($page->setField(new SimpleByName('a'), 'ccc'));
        $this->assertTrue($page->setField(new SimpleByName('a'), 'aaa'));
        $this->assertEqual($page->getField(new SimpleByName('a')), 'aaa');
    }

    function testSelectionOptionsAreNormalised() {
        $raw = '<form>' .
                '<select name="a">' .
                '<option selected><b>Big</b> bold</option>' .
                '<option>small <em>italic</em></option>' .
                '</select>' .
                '</form>';
        $page = $this->whenVisiting('http://host', $raw);
        $this->assertEqual($page->getField(new SimpleByName('a')), 'Big bold');
        $this->assertTrue($page->setField(new SimpleByName('a'), 'small italic'));
        $this->assertEqual($page->getField(new SimpleByName('a')), 'small italic');
    }

    function testCanParseBlankOptions() {
        $raw = '<form>
                <select id=4 name="d">
                    <option value="d1">D1</option>
                    <option value="d2">D2</option>
                    <option></option>
                </select>
                </form>';
        $page = $this->whenVisiting('http://host', $raw);
        $this->assertTrue($page->setField(new SimpleByName('d'), ''));
    }

    function testTwoSelectionFieldsAreIndependent() {
        $raw = '<form>
                    <select id=4 name="d">
                        <option value="d1" selected>D1</option>
                        <option value="d2">D2</option>
                    </select>
                    <select id=11 name="h">
                        <option value="h1">H1</option>
                        <option value="h2" selected>H2</option>
                    </select>
                </form>';
        $page = $this->whenVisiting('http://host', $raw);
        $this->assertTrue($page->setField(new SimpleByName('d'), 'd2'));
        $this->assertTrue($page->setField(new SimpleByName('h'), 'h1'));
        $this->assertEqual($page->getField(new SimpleByName('d')), 'd2');
    }

    function testEmptyOptionDoesNotScrewUpTwoSelectionFields() {
        $raw = '<form>
                    <select name="d">
                        <option value="d1" selected>D1</option>
                        <option value="d2">D2</option>
                        <option></option>
                    </select>
                    <select name="h">
                        <option value="h1">H1</option>
                        <option value="h2" selected>H2</option>
                    </select>
                </form>';
        $page = $this->whenVisiting('http://host', $raw);
        $this->assertTrue($page->setField(new SimpleByName('d'), 'd2'));
        $this->assertTrue($page->setField(new SimpleByName('h'), 'h1'));
        $this->assertEqual($page->getField(new SimpleByName('d')), 'd2');
    }

    function testSettingSelectionFieldByEnclosingLabel() {
        $raw = '<form>' .
                '<label>Stuff' .
                '<select name="a"><option selected>A</option><option>B</option></select>' .
                '</label>' .
                '</form>';
        $page = $this->whenVisiting('http://host', $raw);
        $this->assertEqual($page->getField(new SimpleByLabel('Stuff')), 'A');
        $this->assertTrue($page->setField(new SimpleByLabel('Stuff'), 'B'));
        $this->assertEqual($page->getField(new SimpleByLabel('Stuff')), 'B');
    }

    function testTwoSelectionFieldsWithLabelsAreIndependent() {
        $raw = '<form>
                    <label>Labelled D
                        <select id=4 name="d">
                            <option value="d1" selected>D1</option>
                            <option value="d2">D2</option>
                        </select>
                    </label>
                    <label>Labelled H
                        <select id=11 name="h">
                            <option value="h1">H1</option>
                            <option value="h2" selected>H2</option>
                        </select>
                    </label>
                </form>';
        $page = $this->whenVisiting('http://host', $raw);
        $this->assertTrue($page->setField(new SimpleByLabel('Labelled D'), 'd2'));
        $this->assertTrue($page->setField(new SimpleByLabel('Labelled H'), 'h1'));
        $this->assertEqual($page->getField(new SimpleByLabel('Labelled D')), 'd2');
    }

    function testSettingRadioButtonByEnclosingLabel() {
        $raw = '<form>' .
                '<label>A<input type="radio" name="r" value="a" checked></label>' .
                '<label>B<input type="radio" name="r" value="b"></label>' .
                '</form>';
        $page = $this->whenVisiting('http://host', $raw);
        $this->assertEqual($page->getField(new SimpleByLabel('A')), 'a');
        $this->assertTrue($page->setField(new SimpleBylabel('B'), 'b'));
        $this->assertEqual($page->getField(new SimpleByLabel('B')), 'b');
    }

    function testCanParseInputsWithAllKindsOfAttributeQuoting() {
        $raw = '<form>' .
                '<input type="checkbox" name=\'first\' value=one checked></input>' .
                '<input type=checkbox name="second" value="two"></input>' .
                '<input type=checkbox name="third" value=\'three\' checked="checked" />' .
                '</form>';
        $page = $this->whenVisiting('http://host', $raw);
        $this->assertEqual($page->getField(new SimpleByName('first')), 'one');
        $this->assertEqual($page->getField(new SimpleByName('second')), false);
        $this->assertEqual($page->getField(new SimpleByName('third')), 'three');
    }

    function urlToString($url) {
        return $url->asString();
    }

    function assertSameFrameset($actual, $expected) {
        $this->assertIdentical(array_map(array($this, 'urlToString'), $actual),
                               array_map(array($this, 'urlToString'), $expected));
    }
}

class TestOfParsingUsingPhpParser extends TestOfParsing {

    function whenVisiting($url, $content) {
        $response = new MockSimpleHttpResponse();
        $response->setReturnValue('getContent', $content);
        $response->setReturnValue('getUrl', new SimpleUrl($url));
        $builder = new SimplePhpPageBuilder();
        return $builder->parse($response);
    }

    function testNastyTitle() {
        $page = $this->whenVisiting('http://host',
                                    '<html><head><Title> <b>Me&amp;Me </TITLE></b></head></html>');
        $this->assertEqual($page->getTitle(), "Me&Me");
    }

    function testLabelShouldStopAtClosingLabelTag() {
        $raw = '<form><label>start<textarea id=3 name="c" wrap="hard">stuff</textarea>end</label>stuff</form>';
        $page = $this->whenVisiting('http://host', $raw);
        $this->assertEqual($page->getField(new SimpleByLabel('startend')), 'stuff');
    }
}

class TestOfParsingUsingTidyParser extends TestOfParsing {

    function skip() {
        $this->skipUnless(extension_loaded('tidy'), 'Install \'tidy\' php extension to enable html tidy based parser');
    }

    function whenVisiting($url, $content) {
        $response = new MockSimpleHttpResponse();
        $response->setReturnValue('getContent', $content);
        $response->setReturnValue('getUrl', new SimpleUrl($url));
        $builder = new SimpleTidyPageBuilder();
        return $builder->parse($response);
    }
}
?>