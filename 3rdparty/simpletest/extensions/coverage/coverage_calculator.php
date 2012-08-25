<?php
/**
* @package        SimpleTest
* @subpackage     Extensions
*/
/**
* @package        SimpleTest
* @subpackage     Extensions
*/
class CoverageCalculator {

    function coverageByFileVariables($file, $coverage) {
        $hnd = fopen($file, 'r');
        if ($hnd == null) {
            throw new Exception("File $file is missing");
        }
        $lines = array();
        for ($i = 1; !feof($hnd); $i++) {
            $line = fgets($hnd);
            $lineCoverage = $this->lineCoverageCodeToStyleClass($coverage, $i);
            $lines[$i] = array('lineCoverage' => $lineCoverage, 'code' => $line);
        }

        fclose($hnd);

        $var = compact('file', 'lines', 'coverage');
        return $var;
    }

    function lineCoverageCodeToStyleClass($coverage, $line) {
        if (!array_key_exists($line, $coverage)) {
            return "comment";
        }
        $code = $coverage[$line];
        if (empty($code)) {
            return "comment";
        }
        switch ($code) {
            case -1:
                return "missed";
            case -2:
                return "dead";
        }

        return "covered";
    }

    function totalLoc($total, $coverage) {
        return $total + sizeof($coverage);
    }

    function lineCoverage($total, $line) {
        # NOTE: counting dead code as covered, as it's almost always an executable line
        # strange artifact of xdebug or underlying system
        return $total + ($line > 0 || $line == -2 ? 1 : 0);
    }

    function totalCoverage($total, $coverage) {
        return $total + array_reduce($coverage, array(&$this, "lineCoverage"));
    }

    static function reportFilename($filename) {
        return preg_replace('|[/\\\\]|', '_', $filename) . '.html';
    }

    function percentCoverageByFile($coverage, $file, &$results) {
        $byFileReport = self::reportFilename($file);

        $loc = sizeof($coverage);
        if ($loc == 0)
        return 0;
        $lineCoverage = array_reduce($coverage, array(&$this, "lineCoverage"));
        $percentage = 100 * ($lineCoverage / $loc);
        $results[0][$file] = array('byFileReport' => $byFileReport, 'percentage' => $percentage);
    }

    function variables($coverage, $untouched) {
        $coverageByFile = array();
        array_walk($coverage, array(&$this, "percentCoverageByFile"), array(&$coverageByFile));

        $totalLoc = array_reduce($coverage, array(&$this, "totalLoc"));

        if ($totalLoc > 0) {
            $totalLinesOfCoverage = array_reduce($coverage, array(&$this, "totalCoverage"));
            $totalPercentCoverage = 100 * ($totalLinesOfCoverage / $totalLoc);
        }

        $untouchedPercentageDenominator = sizeof($coverage) + sizeof($untouched);
        if ($untouchedPercentageDenominator > 0) {
            $filesTouchedPercentage = 100 * sizeof($coverage) / $untouchedPercentageDenominator;
        }

        $var = compact('coverageByFile', 'totalPercentCoverage', 'totalLoc', 'totalLinesOfCoverage', 'filesTouchedPercentage');
        $var['untouched'] = $untouched;
        return $var;
    }
}
?>