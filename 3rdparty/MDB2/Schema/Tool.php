<?php /* vim: se et ts=4 sw=4 sts=4 fdm=marker tw=80: */
/**
 * Copyright (c) 1998-2010 Manuel Lemos, Tomas V.V.Cox,
 * Stig. S. Bakken, Lukas Smith, Igor Feghali
 * All rights reserved.
 *
 * MDB2_Schema enables users to maintain RDBMS independant schema files
 * in XML that can be used to manipulate both data and database schemas
 * This LICENSE is in the BSD license style.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions
 * are met:
 *
 * Redistributions of source code must retain the above copyright
 * notice, this list of conditions and the following disclaimer.
 *
 * Redistributions in binary form must reproduce the above copyright
 * notice, this list of conditions and the following disclaimer in the
 * documentation and/or other materials provided with the distribution.
 *
 * Neither the name of Manuel Lemos, Tomas V.V.Cox, Stig. S. Bakken,
 * Lukas Smith, Igor Feghali nor the names of his contributors may be
 * used to endorse or promote products derived from this software
 * without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS
 * FOR A PARTICULAR PURPOSE ARE DISCLAIMED.  IN NO EVENT SHALL THE
 * REGENTS OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,
 * INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING,
 * BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS
 *  OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED
 * AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT
 * LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY
 * WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 *
 * PHP version 5
 *
 * @category Database
 * @package  MDB2_Schema
 * @author   Christian Weiske <cweiske@php.net>
 * @license  BSD http://www.opensource.org/licenses/bsd-license.php
 * @version  SVN: $Id$
 * @link     http://pear.php.net/packages/MDB2_Schema
 */

require_once 'MDB2/Schema.php';
require_once 'MDB2/Schema/Tool/ParameterException.php';

/**
* Command line tool to work with database schemas
*
* Functionality:
* - dump a database schema to stdout
* - import schema into database
* - create a diff between two schemas
* - apply diff to database
*
 * @category Database
 * @package  MDB2_Schema
 * @author   Christian Weiske <cweiske@php.net>
 * @license  BSD http://www.opensource.org/licenses/bsd-license.php
 * @link     http://pear.php.net/packages/MDB2_Schema
 */
class MDB2_Schema_Tool
{
    /**
    * Run the schema tool
    *
    * @param array $args Array of command line arguments
    */
    public function __construct($args)
    {
        $strAction = $this->getAction($args);
        try {
            $this->{'do' . ucfirst($strAction)}($args);
        } catch (MDB2_Schema_Tool_ParameterException $e) {
            $this->{'doHelp' . ucfirst($strAction)}($e->getMessage());
        }
    }//public function __construct($args)



    /**
    * Runs the tool with command line arguments
    *
    * @return void
    */
    public static function run()
    {
        $args = $GLOBALS['argv'];
        array_shift($args);

        try {
            $tool = new MDB2_Schema_Tool($args);
        } catch (Exception $e) {
            self::toStdErr($e->getMessage() . "\n");
        }
    }//public static function run()



    /**
    * Reads the first parameter from the argument array and
    * returns the action.
    *
    * @param array &$args Command line parameters
    *
    * @return string Action to execute
    */
    protected function getAction(&$args)
    {
        if (count($args) == 0) {
            return 'help';
        }
        $arg = array_shift($args);
        switch ($arg) {
        case 'h':
        case 'help':
        case '-h':
        case '--help':
            return 'help';
        case 'd':
        case 'dump':
        case '-d':
        case '--dump':
            return 'dump';
        case 'l':
        case 'load':
        case '-l':
        case '--load':
            return 'load';
        case 'i':
        case 'diff':
        case '-i':
        case '--diff':
            return 'diff';
        case 'a':
        case 'apply':
        case '-a':
        case '--apply':
            return 'apply';
        case 'n':
        case 'init':
        case '-i':
        case '--init':
            return 'init';
        default:
            throw new MDB2_Schema_Tool_ParameterException(
                "Unknown mode \"$arg\""
            );
        }
    }//protected function getAction(&$args)



    /**
    * Writes the message to stderr
    *
    * @param string $msg Message to print
    *
    * @return void
    */
    protected static function toStdErr($msg)
    {
        file_put_contents('php://stderr', $msg);
    }//protected static function toStdErr($msg)



    /**
    * Displays generic help to stdout
    *
    * @return void
    */
    protected function doHelp()
    {
        self::toStdErr(
<<<EOH
Usage: mdb2_schematool mode parameters

Works with database schemas

mode: (- and -- are optional)
 h,  help     Show this help screen
 d,  dump     Dump a schema to stdout
 l,  load     Load a schema into database
 i,  diff     Create a diff between two schemas and dump it to stdout
 a,  apply    Apply a diff to a database
 n,  init     Initialize a database with data

EOH
        );
    }//protected function doHelp()



    /**
    * Displays the help screen for "dump" command
    *
    * @return void
    */
    protected function doHelpDump()
    {
        self::toStdErr(
<<<EOH
Usage: mdb2_schematool dump [all|data|schema] [-p] DSN

Dumps a database schema to stdout

If dump type is not specified, defaults to "schema".

DSN: Data source name in the form of
 driver://user:password@host/database

User and password may be omitted.
Using -p reads password from stdin which is more secure than passing it in the 
parameter.

EOH
        );
    }//protected function doHelpDump()



    /**
    * Displays the help screen for "init" command
    *
    * @return void
    */
    protected function doHelpInit()
    {
        self::toStdErr(
<<<EOH
Usage: mdb2_schematool init source [-p] destination

Initializes a database with data
 (Inserts data on a previous created database at destination)

source should be a schema file containing data,
destination should be a DSN

DSN: Data source name in the form of
 driver://user:password@host/database

User and password may be omitted.
Using -p reads password from stdin which is more secure than passing it in the 
parameter.

EOH
        );
    }//protected function doHelpInit()



    /**
    * Displays the help screen for "load" command
    *
    * @return void
    */
    protected function doHelpLoad()
    {
        self::toStdErr(
<<<EOH
Usage: mdb2_schematool load [-p] source [-p] destination

Loads a database schema from source to destination
 (Creates the database schema at destination)

source can be a DSN or a schema file,
destination should be a DSN

DSN: Data source name in the form of
 driver://user:password@host/database

User and password may be omitted.
Using -p reads password from stdin which is more secure than passing it in the 
parameter.

EOH
        );
    }//protected function doHelpLoad()



    /**
    * Returns an array of options for MDB2_Schema constructor
    *
    * @return array Options for MDB2_Schema constructor
    */
    protected function getSchemaOptions()
    {
        $options = array(
            'log_line_break' => '<br>',
            'idxname_format' => '%s',
            'debug' => true,
            'quote_identifier' => true,
            'force_defaults' => false,
            'portability' => true,
            'use_transactions' => false,
        );
        return $options;
    }//protected function getSchemaOptions()



    /**
    * Checks if the passed parameter is a PEAR_Error object
    * and throws an exception in that case.
    *
    * @param mixed  $object   Some variable to check
    * @param string $location Where the error occured
    *
    * @return void
    */
    protected function throwExceptionOnError($object, $location = '')
    {
        if (PEAR::isError($object)) {
            //FIXME: exception class
            //debug_print_backtrace();
            throw new Exception('Error ' . $location
                . "\n" . $object->getMessage()
                . "\n" . $object->getUserInfo()
            );
        }
    }//protected function throwExceptionOnError($object, $location = '')



    /**
    * Loads a file or a dsn from the arguments
    *
    * @param array &$args Array of arguments to the program
    *
    * @return array Array of ('file'|'dsn', $value)
    */
    protected function getFileOrDsn(&$args)
    {
        if (count($args) == 0) {
            throw new MDB2_Schema_Tool_ParameterException(
                'File or DSN expected'
            );
        }

        $arg = array_shift($args);
        if ($arg == '-p') {
            $bAskPassword = true;
            $arg          = array_shift($args);
        } else {
            $bAskPassword = false;
        }

        if (strpos($arg, '://') === false) {
            if (file_exists($arg)) {
                //File
                return array('file', $arg);
            } else {
                throw new Exception('Schema file does not exist');
            }
        }

        //read password if necessary
        if ($bAskPassword) {
            $password = $this->readPasswordFromStdin($arg);
            $arg      = self::setPasswordIntoDsn($arg, $password);
            self::toStdErr($arg);
        }
        return array('dsn', $arg);
    }//protected function getFileOrDsn(&$args)



    /**
    * Takes a DSN data source name and integrates the given
    * password into it.
    *
    * @param string $dsn      Data source name
    * @param string $password Password
    *
    * @return string DSN with password
    */
    protected function setPasswordIntoDsn($dsn, $password)
    {
        //simple try to integrate password
        if (strpos($dsn, '@') === false) {
            //no @ -> no user and no password
            return str_replace('://', '://:' . $password . '@', $dsn);
        } else if (preg_match('|://[^:]+@|', $dsn)) {
            //user only, no password
            return str_replace('@', ':' . $password . '@', $dsn);
        } else if (strpos($dsn, ':@') !== false) {
            //abstract version
            return str_replace(':@', ':' . $password . '@', $dsn);
        }

        return $dsn;
    }//protected function setPasswordIntoDsn($dsn, $password)



    /**
    * Reads a password from stdin
    *
    * @param string $dsn DSN name to put into the message
    *
    * @return string Password
    */
    protected function readPasswordFromStdin($dsn)
    {
        $stdin = fopen('php://stdin', 'r');
        self::toStdErr('Please insert password for ' . $dsn . "\n");
        $password = '';
        $breakme  = false;
        while (false !== ($char = fgetc($stdin))) {
            if (ord($char) == 10 || $char == "\n" || $char == "\r") {
                break;
            }
            $password .= $char;
        }
        fclose($stdin);

        return trim($password);
    }//protected function readPasswordFromStdin()



    /**
    * Creates a database schema dump and sends it to stdout
    *
    * @param array $args Command line arguments
    *
    * @return void
    */
    protected function doDump($args)
    {
        $dump_what = MDB2_SCHEMA_DUMP_STRUCTURE;
        $arg = '';
        if (count($args)) {
            $arg = $args[0];
        }

        switch (strtolower($arg)) {
        case 'all':
            $dump_what = MDB2_SCHEMA_DUMP_ALL;
            array_shift($args);
            break;
        case 'data':
            $dump_what = MDB2_SCHEMA_DUMP_CONTENT;
            array_shift($args);
            break;
        case 'schema':
            array_shift($args);
        }

        list($type, $dsn) = $this->getFileOrDsn($args);
        if ($type == 'file') {
            throw new MDB2_Schema_Tool_ParameterException(
                'Dumping a schema file as a schema file does not make much ' .
                'sense'
            );
        }

        $schema = MDB2_Schema::factory($dsn, $this->getSchemaOptions());
        $this->throwExceptionOnError($schema);

        $definition = $schema->getDefinitionFromDatabase();
        $this->throwExceptionOnError($definition);


        $dump_options = array(
            'output_mode' => 'file',
            'output' => 'php://stdout',
            'end_of_line' => "\r\n"
        );
        $op = $schema->dumpDatabase(
            $definition, $dump_options, $dump_what
        );
        $this->throwExceptionOnError($op);

        $schema->disconnect();
    }//protected function doDump($args)



    /**
    * Loads a database schema
    *
    * @param array $args Command line arguments
    *
    * @return void
    */
    protected function doLoad($args)
    {
        list($typeSource, $dsnSource) = $this->getFileOrDsn($args);
        list($typeDest,   $dsnDest)   = $this->getFileOrDsn($args);

        if ($typeDest == 'file') {
            throw new MDB2_Schema_Tool_ParameterException(
                'A schema can only be loaded into a database, not a file'
            );
        }


        $schemaDest = MDB2_Schema::factory($dsnDest, $this->getSchemaOptions());
        $this->throwExceptionOnError($schemaDest);

        //load definition
        if ($typeSource == 'file') {
            $definition = $schemaDest->parseDatabaseDefinitionFile($dsnSource);
            $where      = 'loading schema file';
        } else {
            $schemaSource = MDB2_Schema::factory(
                $dsnSource,
                $this->getSchemaOptions()
            );
            $this->throwExceptionOnError(
                $schemaSource,
                'connecting to source database'
            );

            $definition = $schemaSource->getDefinitionFromDatabase();
            $where      = 'loading definition from database';
        }
        $this->throwExceptionOnError($definition, $where);


        //create destination database from definition
        $simulate = false;
        $op       = $schemaDest->createDatabase(
            $definition,
            array(),
            $simulate
        );
        $this->throwExceptionOnError($op, 'creating the database');
    }//protected function doLoad($args)



    /**
    * Initializes a database with data
    *
    * @param array $args Command line arguments
    *
    * @return void
    */
    protected function doInit($args)
    {
        list($typeSource, $dsnSource) = $this->getFileOrDsn($args);
        list($typeDest,   $dsnDest)   = $this->getFileOrDsn($args);

        if ($typeSource != 'file') {
            throw new MDB2_Schema_Tool_ParameterException(
                'Data must come from a source file'
            );
        }

        if ($typeDest != 'dsn') {
            throw new MDB2_Schema_Tool_ParameterException(
                'A schema can only be loaded into a database, not a file'
            );
        }

        $schemaDest = MDB2_Schema::factory($dsnDest, $this->getSchemaOptions());
        $this->throwExceptionOnError(
            $schemaDest,
            'connecting to destination database'
        );

        $definition = $schemaDest->getDefinitionFromDatabase();
        $this->throwExceptionOnError(
            $definition,
            'loading definition from database'
        );

        $op = $schemaDest->writeInitialization($dsnSource, $definition);
        $this->throwExceptionOnError($op, 'initializing database');
    }//protected function doInit($args)


}//class MDB2_Schema_Tool
