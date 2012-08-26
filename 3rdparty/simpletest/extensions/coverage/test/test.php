<?php
// $Id: $
require_once(dirname(__FILE__) . '/../../../autorun.php');

class CoverageUnitTests extends TestSuite {
    function CoverageUnitTests() {
        $this->TestSuite('Coverage Unit tests');
        $path = dirname(__FILE__) . '/*_test.php';
        foreach(glob($path) as $test) {
            $this->addFile($test);
        }
    }
}
?>