<?php
/**
 * @package        SimpleTest
 * @subpackage     Extensions
 */
/**
 * @todo	which db abstraction layer is this?
 */
require_once 'DB/sqlite.php';

/**
 * Persists code coverage data into SQLite database and aggregate data for convienent
 * interpretation in report generator.  Be sure to not to keep an instance longer
 * than you have, otherwise you risk overwriting database edits from another process
 * also trying to make updates.
 * @package        SimpleTest
 * @subpackage     Extensions
 */
class CoverageDataHandler {

    var $db;

    function __construct($filename) {
        $this->filename = $filename;
        $this->db = new SQLiteDatabase($filename);
        if (empty($this->db)) {
            throw new Exception("Could not create sqlite db ". $filename);
        }
    }

    function createSchema() {
        $this->db->queryExec("create table untouched (filename text)");
        $this->db->queryExec("create table coverage (name text, coverage text)");
    }

    function &getFilenames() {
        $filenames = array();
        $cursor = $this->db->unbufferedQuery("select distinct name from coverage");
        while ($row = $cursor->fetch()) {
            $filenames[] = $row[0];
        }

        return $filenames;
    }

    function write($coverage) {
        foreach ($coverage as $file => $lines) {
            $coverageStr = serialize($lines);
            $relativeFilename = self::ltrim(getcwd() . '/', $file);
            $sql = "insert into coverage (name, coverage) values ('$relativeFilename', '$coverageStr')";
            # if this fails, check you have write permission
            $this->db->queryExec($sql);
        }
    }

    function read() {
        $coverage = array_flip($this->getFilenames());
        foreach($coverage as $file => $garbage) {
            $coverage[$file] = $this->readFile($file);
        }
        return $coverage;
    }

    function &readFile($file) {
        $sql = "select coverage from coverage where name = '$file'";
        $aggregate = array();
        $result = $this->db->query($sql);
        while ($result->valid()) {
            $row = $result->current();
            $this->aggregateCoverage($aggregate, unserialize($row[0]));
            $result->next();
        }

        return $aggregate;
    }

    function aggregateCoverage(&$total, $next) {
        foreach ($next as $lineno => $code) {
            if (!isset($total[$lineno])) {
                $total[$lineno] = $code;
            } else {
                $total[$lineno] = $this->aggregateCoverageCode($total[$lineno], $code);
            }
        }
    }

    function aggregateCoverageCode($code1, $code2) {
        switch($code1) {
            case -2: return -2;
            case -1: return $code2;
            default:
                switch ($code2) {
                    case -2: return -2;
                    case -1: return $code1;
                }
        }
        return $code1 + $code2;
    }

    static function ltrim($cruft, $pristine) {
        if(stripos($pristine, $cruft) === 0) {
            return substr($pristine, strlen($cruft));
        }
        return $pristine;
    }

    function writeUntouchedFile($file) {
        $relativeFile = CoverageDataHandler::ltrim('./', $file);
        $sql = "insert into untouched values ('$relativeFile')";
        $this->db->queryExec($sql);
    }

    function &readUntouchedFiles() {
        $untouched = array();
        $result = $this->db->query("select filename from untouched order by filename");
        while ($result->valid()) {
            $row = $result->current();
            $untouched[] = $row[0];
            $result->next();
        }

        return $untouched;
    }
}
?>