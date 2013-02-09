<?php
OC_PHPUnit_Loader::checkIncludePath();
OC_PHPUnit_Loader::detectPHPUnitVersionId();

//load PHPUnit
switch (OC_PHPUnit_Loader::$PHPUnitVersionId) {
    case "36": {
        OC_PHPUnit_Loader::load36();
        break;
    }
    case "37": {
        OC_PHPUnit_Loader::load37();
        break;
    }
}

//load custom implementation of the PHPUnit_TextUI_ResultPrinter
switch (OC_PHPUnit_Loader::$PHPUnitVersionId) {
    case "36":
    case "37": {
        class OC_PHPUnit_TextUI_ResultPrinter extends PHPUnit_TextUI_ResultPrinter
        {
            function __construct()
            {
                parent::__construct('php://stderr');
            }

            public function printResult(PHPUnit_Framework_TestResult $result)
            {
                $this->printHeader();
                $this->printFooter($result);
            }

            protected function writeProgress($progress)
            {
                //ignore
            }
        }
        break;
    }
}

//loading of OC_PHPUnit_TextUI_Command
switch (OC_PHPUnit_Loader::$PHPUnitVersionId) {
    case "36":
    case "37": {
        class OC_PHPUnit_TextUI_Command extends PHPUnit_TextUI_Command
        {

            public static function main($exit = TRUE)
            {
                $command = new OC_PHPUnit_TextUI_Command();
                $command->run($_SERVER['argv'], $exit);
            }

            protected function handleArguments(array $argv)
            {
                parent::handleArguments($argv);
                $this->arguments['listeners'][] = new OC_PHPUnit_Framework_TestListener();
                $this->arguments['printer'] = new OC_PHPUnit_TextUI_ResultPrinter();
            }

            protected function createRunner()
            {
                $coverage_Filter = new PHP_CodeCoverage_Filter();
                $coverage_Filter->addFileToBlacklist(__FILE__);
                $runner = new PHPUnit_TextUI_TestRunner($this->arguments['loader'], $coverage_Filter);
                return $runner;
            }
        }
        break;
    }
}

class OC_PHPUnit_Loader
{

    const SUCCESS_EXIT = 0;
    const FAILURE_EXIT = 1;
    const EXCEPTION_EXIT = 2;

    public static $PHPUnitVersionId;

    /**
     * @return void
     */
    public static function checkIncludePath()
    {
        //check include path
        $PHPUnitParentDirectory = self::getPHPUnitParentDirectory();
        if (is_null($PHPUnitParentDirectory)) {
            echo "Cannot find PHPUnit in include path (" . ini_get('include_path') . ")";
            exit(OC_PHPUnit_Loader::FAILURE_EXIT);
        }
    }

    /**
     * @return null | string
     */
    private static function getPHPUnitParentDirectory()
    {
        $pathArray = explode(PATH_SEPARATOR, ini_get('include_path'));
        foreach ($pathArray as $path)
        {
            if (file_exists($path . DIRECTORY_SEPARATOR . 'PHPUnit/')) {
                return $path;
            }
        }
        return null;
    }

    /**
     * @return void
     */
    public static function detectPHPUnitVersionId()
    {
        require_once 'PHPUnit/Runner/Version.php';

        $PHPUnitVersion = PHPUnit_Runner_Version::id();

        if ($PHPUnitVersion === "@package_version@") {

            self::$PHPUnitVersionId = "37";
        }
        else if (version_compare($PHPUnitVersion, '3.7.0') >= 0) {

            self::$PHPUnitVersionId = "37";
        }
        else if (version_compare($PHPUnitVersion, '3.6.0') >= 0) {

            self::$PHPUnitVersionId = "36";
        }
        else if (version_compare($PHPUnitVersion, '3.6.0') >= 0) {

            echo "unsupported PHPUnit version:  $PHPUnitVersion";
            exit(OC_PHPUnit_Loader::FAILURE_EXIT);
        }
    }

    /**
     * @return void
     */
    public static function load37()
    {

        require 'PHPUnit/Autoload.php';

    }


    /**
     * @return void
     */
    public static function load36()
    {
        define('PHPUnit_MAIN_METHOD', 'OC_PHPUnit_TextUI_Command::main');

        require 'PHPUnit/Autoload.php';

    }
}

class OC_PHPUnit_Framework_TestListener implements PHPUnit_Framework_TestListener
{

    private $isSummaryTestCountPrinted = false;

    public static function printEvent($eventName, $params = array())
    {
        self::printText("\n[$eventName");
        foreach ($params as $key => $value) {
            self::printText(" $key='$value'");
        }
        self::printText("]\n");
    }

    public static function printText($text)
    {
        file_put_contents('php://stderr', $text);
    }

    private static function getMessage(Exception $e)
    {
        $message = "";
        if (strlen(get_class($e)) != 0) {
            $message = $message . get_class($e);
        }
        if (strlen($message) != 0 && strlen($e->getMessage()) != 0) {
            $message = $message . " : ";
        }
        $message = $message . $e->getMessage();
        return self::escapeValue($message);
    }

    private static function getDetails(Exception $e)
    {
        return self::escapeValue($e->getTraceAsString());
    }

    public static function getValueAsString($value)
    {
        if (is_null($value)) {
            return "null";
        }
        else if (is_bool($value)) {
            return $value == true ? "true" : "false";
        }
        else if (is_array($value) || is_string($value)) {
            $valueAsString = print_r($value, true);
            if (strlen($valueAsString) > 10000) {
                return null;
            }
            return $valueAsString;
        }
        else if (is_scalar($value)){
            return print_r($value, true);
        }
        return null;
    }

    private static function escapeValue($text) {
        $text = str_replace("|", "||", $text);
        $text = str_replace("'", "|'", $text);
        $text = str_replace("\n", "|n", $text);
        $text = str_replace("\r", "|r", $text);
        $text = str_replace("]", "|]", $text);
        return $text;
    }

    public static function getFileName($className)
    {
        $reflectionClass = new ReflectionClass($className);
        $fileName = $reflectionClass->getFileName();
        return $fileName;
    }

    public function addError(PHPUnit_Framework_Test $test, Exception $e, $time)
    {
        self::printEvent("testFailed", array(
            "name" => $test->getName(),
            "message" => self::getMessage($e),
            "details" => self::getDetails($e)
        ));
    }

    public function addFailure(PHPUnit_Framework_Test $test, PHPUnit_Framework_AssertionFailedError $e, $time)
    {
        $params = array(
            "name" => $test->getName(),
            "message" => self::getMessage($e),
            "details" => self::getDetails($e)
        );
        if ($e instanceof PHPUnit_Framework_ExpectationFailedException) {
            $comparisonFailure = $e->getComparisonFailure();
            if ($comparisonFailure instanceof PHPUnit_Framework_ComparisonFailure) {
                $actualResult = $comparisonFailure->getActual();
                $expectedResult = $comparisonFailure->getExpected();
                $actualString = self::getValueAsString($actualResult);
                $expectedString = self::getValueAsString($expectedResult);
                if (!is_null($actualString) && !is_null($expectedString)) {
                    $params['actual'] = self::escapeValue($actualString);
                    $params['expected'] = self::escapeValue($expectedString);
                }
            }
        }
        self::printEvent("testFailed", $params);
    }

    public function addIncompleteTest(PHPUnit_Framework_Test $test, Exception $e, $time)
    {
        self::printEvent("testIgnored", array(
            "name" => $test->getName(),
            "message" => self::getMessage($e),
            "details" => self::getDetails($e)
        ));
    }

    public function addSkippedTest(PHPUnit_Framework_Test $test, Exception $e, $time)
    {
        self::printEvent("testIgnored", array(
            "name" => $test->getName(),
            "message" => self::getMessage($e),
            "details" => self::getDetails($e)
        ));
    }

    public function startTest(PHPUnit_Framework_Test $test)
    {
        $testName = $test->getName();
        $params = array(
            "name" => $testName
        );
        if ($test instanceof PHPUnit_Framework_TestCase) {
            $className = get_class($test);
            $fileName = self::getFileName($className);
            $params['locationHint'] = "php_qn://$fileName::\\$className::$testName";
        }
        self::printEvent("testStarted", $params);
    }

    public function endTest(PHPUnit_Framework_Test $test, $time)
    {
        self::printEvent("testFinished", array(
            "name" => $test->getName(),
            "duration" => (int)(round($time, 2) * 1000)
        ));
    }

    public function startTestSuite(PHPUnit_Framework_TestSuite $suite)
    {
        if (!$this->isSummaryTestCountPrinted) {
            $this->isSummaryTestCountPrinted = true;
            //print tests count
            self::printEvent("testCount", array(
                "count" => count($suite)
            ));
        }

        $suiteName = $suite->getName();
        if (empty($suiteName)) {
            return;
        }
        $params = array(
            "name" => $suiteName,
        );
        if (class_exists($suiteName, false)) {
            $fileName = self::getFileName($suiteName);
            $params['locationHint'] = "php_qn://$fileName::\\$suiteName";
        }
        self::printEvent("testSuiteStarted", $params);
    }

    public function endTestSuite(PHPUnit_Framework_TestSuite $suite)
    {
        $suiteName = $suite->getName();
        if (empty($suiteName)) {
            return;
        }
        self::printEvent("testSuiteFinished",
            array(
                "name" => $suite->getName()
            ));
    }

}

OC_PHPUnit_TextUI_Command::main();
