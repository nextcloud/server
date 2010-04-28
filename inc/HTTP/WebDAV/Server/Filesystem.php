<<<<<<< HEAD:inc/HTTP/WebDAV/Server/Filesystem.php
<?php // $Id$
/*
   +----------------------------------------------------------------------+
   | Copyright (c) 2002-2007 Christian Stocker, Hartmut Holzgraefe        |
   | All rights reserved                                                  |
   |                                                                      |
   | Redistribution and use in source and binary forms, with or without   |
   | modification, are permitted provided that the following conditions   |
   | are met:                                                             |
   |                                                                      |
   | 1. Redistributions of source code must retain the above copyright    |
   |    notice, this list of conditions and the following disclaimer.     |
   | 2. Redistributions in binary form must reproduce the above copyright |
   |    notice, this list of conditions and the following disclaimer in   |
   |    the documentation and/or other materials provided with the        |
   |    distribution.                                                     |
   | 3. The names of the authors may not be used to endorse or promote    |
   |    products derived from this software without specific prior        |
   |    written permission.                                               |
   |                                                                      |
   | THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS  |
   | "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT    |
   | LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS    |
   | FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE       |
   | COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,  |
   | INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, |
   | BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;     |
   | LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER     |
   | CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT   |
   | LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN    |
   | ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE      |
   | POSSIBILITY OF SUCH DAMAGE.                                          |
   +----------------------------------------------------------------------+
*/    
    require_once "../../../lib_base.php";
    oc_require_once "HTTP/WebDAV/Server.php";
    oc_require_once "System.php";
    
    /**
     * Filesystem access using WebDAV
     *
     * @access public
     * @author  Hartmut Holzgraefe <hartmut@php.net>
     * @version @package-version@
     */
    class HTTP_WebDAV_Server_Filesystem extends HTTP_WebDAV_Server 
    {
    /**
     * Root directory for WebDAV access
     *
     * Defaults to webserver document root (set by ServeRequest)
     *
     * @access private
     * @var    string
     */
    var $base = "";

    /**
     * Serve a webdav request
     *
     * @access public
     * @param  string  
     */
    function ServeRequest($base = false) 
    {
            // special treatment for litmus compliance test
            // reply on its identifier header
            // not needed for the test itself but eases debugging
            if (isset($this->_SERVER['HTTP_X_LITMUS'])) {
            error_log("Litmus test ".$this->_SERVER['HTTP_X_LITMUS']);
            header("X-Litmus-reply: ".$this->_SERVER['HTTP_X_LITMUS']);
        }

        // set root directory, defaults to webserver document root if not set
        if ($base) {
            $this->base = realpath($base); // TODO throw if not a directory
        } else if (!$this->base) {
            $this->base = $this->_SERVER['DOCUMENT_ROOT'];
        }
                
        // establish connection to property/locking db
//        mysql_connect($this->db_host, $this->db_user, $this->db_passwd) or die(mysql_error());
//        mysql_select_db($this->db_name) or die(mysql_error());
        // TODO throw on connection problems

        // let the base class do all the work
        parent::ServeRequest();
    }

    /**
     * No authentication is needed here
     *
     * @access private
     * @param  string  HTTP Authentication type (Basic, Digest, ...)
     * @param  string  Username
     * @param  string  Password
     * @return bool    true on successful authentication
     */
    function check_auth($type, $user, $pass) 
    {
        return true;
    }


    /**
     * PROPFIND method handler
     *
     * @param  array  general parameter passing array
     * @param  array  return array for file properties
     * @return bool   true on success
     */
    function PROPFIND(&$options, &$files) 
    {
        // get absolute fs path to requested resource
        $fspath = $this->base . $options["path"];
            
        // sanity check
        if (!file_exists($fspath)) {
            return false;
        }

        // prepare property array
        $files["files"] = array();

        // store information for the requested path itself
        $files["files"][] = $this->fileinfo($options["path"]);

        // information for contained resources requested?
        if (!empty($options["depth"]) && is_dir($fspath) && is_readable($fspath)) {                

            // make sure path ends with '/'
            $options["path"] = $this->_slashify($options["path"]);

            // try to open directory
            $handle = opendir($fspath);
                
            if ($handle) {
                // ok, now get all its contents
                while ($filename = readdir($handle)) {
                    if ($filename != "." && $filename != "..") {
                        $files["files"][] = $this->fileinfo($options["path"].$filename);
                     }
                }
                // TODO recursion needed if "Depth: infinite"
            }
        }

        // ok, all done
        return true;
    } 
        
    /**
     * Get properties for a single file/resource
     *
     * @param  string  resource path
     * @return array   resource properties
     */
    function fileinfo($path) 
    {
        // map URI path to filesystem path
        $fspath = $this->base . $path;

        // create result array
        $info = array();
        // TODO remove slash append code when base clase is able to do it itself
        $info["path"]  = is_dir($fspath) ? $this->_slashify($path) : $path; 
        $info["props"] = array();
            
        // no special beautified displayname here ...
        $info["props"][] = $this->mkprop("displayname", strtoupper($path));
            
        // creation and modification time
        $info["props"][] = $this->mkprop("creationdate",    filectime($fspath));
        $info["props"][] = $this->mkprop("getlastmodified", filemtime($fspath));

        // Microsoft extensions: last access time and 'hidden' status
        $info["props"][] = $this->mkprop("lastaccessed",    fileatime($fspath));
        $info["props"][] = $this->mkprop("ishidden", ('.' === substr(basename($fspath), 0, 1)));

        // type and size (caller already made sure that path exists)
        if (is_dir($fspath)) {
            // directory (WebDAV collection)
            $info["props"][] = $this->mkprop("resourcetype", "collection");
            $info["props"][] = $this->mkprop("getcontenttype", "httpd/unix-directory");             
        } else {
            // plain file (WebDAV resource)
            $info["props"][] = $this->mkprop("resourcetype", "");
            if (is_readable($fspath)) {
                $info["props"][] = $this->mkprop("getcontenttype", $this->_mimetype($fspath));
            } else {
                $info["props"][] = $this->mkprop("getcontenttype", "application/x-non-readable");
            }               
            $info["props"][] = $this->mkprop("getcontentlength", filesize($fspath));
        }

        // get additional properties from database
        $query = "SELECT ns, name, value 
                        FROM {$this->db_prefix}properties 
                       WHERE path = '$path'";
        $res = mysql_query($query);
        while ($row = mysql_fetch_assoc($res)) {
            $info["props"][] = $this->mkprop($row["ns"], $row["name"], $row["value"]);
        }
        mysql_free_result($res);

        return $info;
    }

    /**
     * detect if a given program is found in the search PATH
     *
     * helper function used by _mimetype() to detect if the 
     * external 'file' utility is available
     *
     * @param  string  program name
     * @param  string  optional search path, defaults to $PATH
     * @return bool    true if executable program found in path
     */
    function _can_execute($name, $path = false) 
    {
        // path defaults to PATH from environment if not set
        if ($path === false) {
            $path = getenv("PATH");
        }
            
        // check method depends on operating system
        if (!strncmp(PHP_OS, "WIN", 3)) {
            // on Windows an appropriate COM or EXE file needs to exist
            $exts     = array(".exe", ".com");
            $check_fn = "file_exists";
        } else {
            // anywhere else we look for an executable file of that name
            $exts     = array("");
            $check_fn = "is_executable";
        }
            
        // now check the directories in the path for the program
        foreach (explode(PATH_SEPARATOR, $path) as $dir) {
            // skip invalid path entries
            if (!file_exists($dir)) continue;
            if (!is_dir($dir)) continue;

            // and now look for the file
            foreach ($exts as $ext) {
                if ($check_fn("$dir/$name".$ext)) return true;
            }
        }

        return false;
    }

        
    /**
     * try to detect the mime type of a file
     *
     * @param  string  file path
     * @return string  guessed mime type
     */
    function _mimetype($fspath) 
    {
        if (is_dir($fspath)) {
            // directories are easy
            return "httpd/unix-directory"; 
        } else if (function_exists("mime_content_type")) {
            // use mime magic extension if available
            $mime_type = mime_content_type($fspath);
        } else if ($this->_can_execute("file")) {
            // it looks like we have a 'file' command, 
            // lets see it it does have mime support
            $fp    = popen("file -i '$fspath' 2>/dev/null", "r");
            $reply = fgets($fp);
            pclose($fp);
                
            // popen will not return an error if the binary was not found
            // and find may not have mime support using "-i"
            // so we test the format of the returned string 
                
            // the reply begins with the requested filename
            if (!strncmp($reply, "$fspath: ", strlen($fspath)+2)) {                     
                $reply = substr($reply, strlen($fspath)+2);
                // followed by the mime type (maybe including options)
                if (preg_match('|^[[:alnum:]_-]+/[[:alnum:]_-]+;?.*|', $reply, $matches)) {
                    $mime_type = $matches[0];
                }
            }
        } 
            
        if (empty($mime_type)) {
            // Fallback solution: try to guess the type by the file extension
            // TODO: add more ...
            // TODO: it has been suggested to delegate mimetype detection 
            //       to apache but this has at least three issues:
            //       - works only with apache
            //       - needs file to be within the document tree
            //       - requires apache mod_magic 
            // TODO: can we use the registry for this on Windows?
            //       OTOH if the server is Windos the clients are likely to 
            //       be Windows, too, and tend do ignore the Content-Type
            //       anyway (overriding it with information taken from
            //       the registry)
            // TODO: have a seperate PEAR class for mimetype detection?
            switch (strtolower(strrchr(basename($fspath), "."))) {
            case ".html":
                $mime_type = "text/html";
                break;
            case ".gif":
                $mime_type = "image/gif";
                break;
            case ".jpg":
                $mime_type = "image/jpeg";
                break;
            default: 
                $mime_type = "application/octet-stream";
                break;
            }
        }
            
        return $mime_type;
    }

    /**
     * HEAD method handler
     * 
     * @param  array  parameter passing array
     * @return bool   true on success
     */
    function HEAD(&$options) 
    {
        // get absolute fs path to requested resource
        $fspath = $this->base . $options["path"];

        // sanity check
        if (!file_exists($fspath)) return false;
            
        // detect resource type
        $options['mimetype'] = $this->_mimetype($fspath); 
                
        // detect modification time
        // see rfc2518, section 13.7
        // some clients seem to treat this as a reverse rule
        // requiering a Last-Modified header if the getlastmodified header was set
        $options['mtime'] = filemtime($fspath);
            
        // detect resource size
        $options['size'] = filesize($fspath);
            
        return true;
    }

    /**
     * GET method handler
     * 
     * @param  array  parameter passing array
     * @return bool   true on success
     */
    function GET(&$options) 
    {
        // get absolute fs path to requested resource
        $fspath = $this->base . $options["path"];

        // is this a collection?
        if (is_dir($fspath)) {
            return $this->GetDir($fspath, $options);
        }

        // the header output is the same as for HEAD
        if (!$this->HEAD($options)) {
            return false;
        }

        // no need to check result here, it is handled by the base class
        $options['stream'] = fopen($fspath, "r");
            
        return true;
    }

    /**
     * GET method handler for directories
     *
     * This is a very simple mod_index lookalike.
     * See RFC 2518, Section 8.4 on GET/HEAD for collections
     *
     * @param  string  directory path
     * @return void    function has to handle HTTP response itself
     */
    function GetDir($fspath, &$options) 
    {
        $path = $this->_slashify($options["path"]);
        if ($path != $options["path"]) {
            header("Location: ".$this->base_uri.$path);
            exit;
        }

        // fixed width directory column format
        $format = "%15s  %-19s  %-s\n";

        if (!is_readable($fspath)) {
            return false;
        }

        $handle = opendir($fspath);
        if (!$handle) {
            return false;
        }

        echo "<html><head><title>Index of ".htmlspecialchars($options['path'])."</title></head>\n";
            
        echo "<h1>Index of ".htmlspecialchars($options['path'])."</h1>\n";
            
        echo "<pre>";
        printf($format, "Size", "Last modified", "Filename");
        echo "<hr>";

        while ($filename = readdir($handle)) {
            if ($filename != "." && $filename != "..") {
                $fullpath = $fspath."/".$filename;
                $name     = htmlspecialchars($filename);
                printf($format, 
                       number_format(filesize($fullpath)),
                       strftime("%Y-%m-%d %H:%M:%S", filemtime($fullpath)), 
                       "<a href='$name'>$name</a>");
            }
        }

        echo "</pre>";

        closedir($handle);

        echo "</html>\n";

        exit;
    }

    /**
     * PUT method handler
     * 
     * @param  array  parameter passing array
     * @return bool   true on success
     */
    function PUT(&$options) 
    {
        $fspath = $this->base . $options["path"];

        $dir = dirname($fspath);
        if (!file_exists($dir) || !is_dir($dir)) {
            return "409 Conflict"; // TODO right status code for both?
        }

        $options["new"] = ! file_exists($fspath);

        if ($options["new"] && !is_writeable($dir)) {
            return "403 Forbidden";
        }
        if (!$options["new"] && !is_writeable($fspath)) {
            return "403 Forbidden";
        }
        if (!$options["new"] && is_dir($fspath)) {
            return "403 Forbidden";
        }

        $fp = fopen($fspath, "w");

        return $fp;
    }


    /**
     * MKCOL method handler
     *
     * @param  array  general parameter passing array
     * @return bool   true on success
     */
    function MKCOL($options) 
    {           
        $path   = $this->base .$options["path"];
        $parent = dirname($path);
        $name   = basename($path);

        if (!file_exists($parent)) {
            return "409 Conflict";
        }

        if (!is_dir($parent)) {
            return "403 Forbidden";
        }

        if ( file_exists($parent."/".$name) ) {
            return "405 Method not allowed";
        }

        if (!empty($this->_SERVER["CONTENT_LENGTH"])) { // no body parsing yet
            return "415 Unsupported media type";
        }
            
        $stat = mkdir($parent."/".$name, 0777);
        if (!$stat) {
            return "403 Forbidden";                 
        }

        return ("201 Created");
    }
        
        
    /**
     * DELETE method handler
     *
     * @param  array  general parameter passing array
     * @return bool   true on success
     */
    function DELETE($options) 
    {
        $path = $this->base . "/" .$options["path"];

        if (!file_exists($path)) {
            return "404 Not found";
        }

        if (is_dir($path)) {
            $query = "DELETE FROM {$this->db_prefix}properties 
                           WHERE path LIKE '".$this->_slashify($options["path"])."%'";
            mysql_query($query);
            System::rm(array("-rf", $path));
        } else {
            unlink($path);
        }
        $query = "DELETE FROM {$this->db_prefix}properties 
                       WHERE path = '$options[path]'";
        mysql_query($query);

        return "204 No Content";
    }


    /**
     * MOVE method handler
     *
     * @param  array  general parameter passing array
     * @return bool   true on success
     */
    function MOVE($options) 
    {
        return $this->COPY($options, true);
    }

    /**
     * COPY method handler
     *
     * @param  array  general parameter passing array
     * @return bool   true on success
     */
    function COPY($options, $del=false) 
    {
        // TODO Property updates still broken (Litmus should detect this?)

        if (!empty($this->_SERVER["CONTENT_LENGTH"])) { // no body parsing yet
            return "415 Unsupported media type";
        }

        // no copying to different WebDAV Servers yet
        if (isset($options["dest_url"])) {
            return "502 bad gateway";
        }

        $source = $this->base . $options["path"];
        if (!file_exists($source)) {
            return "404 Not found";
        }

        if (is_dir($source)) { // resource is a collection
            switch ($options["depth"]) {
            case "infinity": // valid 
                break;
            case "0": // valid for COPY only
                if ($del) { // MOVE?
                    return "400 Bad request";
                }
                break;
            case "1": // invalid for both COPY and MOVE
            default: 
                return "400 Bad request";
            }
        }

        $dest         = $this->base . $options["dest"];
        $destdir      = dirname($dest);
        
        if (!file_exists($destdir) || !is_dir($destdir)) {
            return "409 Conflict";
        }


        $new          = !file_exists($dest);
        $existing_col = false;

        if (!$new) {
            if ($del && is_dir($dest)) {
                if (!$options["overwrite"]) {
                    return "412 precondition failed";
                }
                $dest .= basename($source);
                if (file_exists($dest)) {
                    $options["dest"] .= basename($source);
                } else {
                    $new          = true;
                    $existing_col = true;
                }
            }
        }

        if (!$new) {
            if ($options["overwrite"]) {
                $stat = $this->DELETE(array("path" => $options["dest"]));
                if (($stat{0} != "2") && (substr($stat, 0, 3) != "404")) {
                    return $stat; 
                }
            } else {
                return "412 precondition failed";
            }
        }

        if ($del) {
            if (!rename($source, $dest)) {
                return "500 Internal server error";
            }
            $destpath = $this->_unslashify($options["dest"]);
            if (is_dir($source)) {
                $query = "UPDATE {$this->db_prefix}properties 
                                 SET path = REPLACE(path, '".$options["path"]."', '".$destpath."') 
                               WHERE path LIKE '".$this->_slashify($options["path"])."%'";
                mysql_query($query);
            }

            $query = "UPDATE {$this->db_prefix}properties 
                             SET path = '".$destpath."'
                           WHERE path = '".$options["path"]."'";
            mysql_query($query);
        } else {
            if (is_dir($source)) {
                $files = System::find($source);
                $files = array_reverse($files);
            } else {
                $files = array($source);
            }

            if (!is_array($files) || empty($files)) {
                return "500 Internal server error";
            }
                    
                
            foreach ($files as $file) {
                if (is_dir($file)) {
                    $file = $this->_slashify($file);
                }

                $destfile = str_replace($source, $dest, $file);
                    
                if (is_dir($file)) {
                    if (!file_exists($destfile)) {
                        if (!is_writeable(dirname($destfile))) {
                            return "403 Forbidden";
                        }
                        if (!mkdir($destfile)) {
                            return "409 Conflict";
                        }
                    } else if (!is_dir($destfile)) {
                        return "409 Conflict";
                    }
                } else {
                    
                    if (!copy($file, $destfile)) {
                        return "409 Conflict";
                    }
                }
            }

            $query = "INSERT INTO {$this->db_prefix}properties 
                               SELECT *
                                 FROM {$this->db_prefix}properties 
                                WHERE path = '".$options['path']."'";
        }

        return ($new && !$existing_col) ? "201 Created" : "204 No Content";         
    }

    /**
     * PROPPATCH method handler
     *
     * @param  array  general parameter passing array
     * @return bool   true on success
     */
    function PROPPATCH(&$options) 
    {
        global $prefs, $tab;

        $msg  = "";
        $path = $options["path"];
        $dir  = dirname($path)."/";
        $base = basename($path);
            
        foreach ($options["props"] as $key => $prop) {
            if ($prop["ns"] == "DAV:") {
                $options["props"][$key]['status'] = "403 Forbidden";
            } else {
                if (isset($prop["val"])) {
                    $query = "REPLACE INTO {$this->db_prefix}properties 
                                           SET path = '$options[path]'
                                             , name = '$prop[name]'
                                             , ns= '$prop[ns]'
                                             , value = '$prop[val]'";
                } else {
                    $query = "DELETE FROM {$this->db_prefix}properties 
                                        WHERE path = '$options[path]' 
                                          AND name = '$prop[name]' 
                                          AND ns = '$prop[ns]'";
                }       
                mysql_query($query);
            }
        }
                        
        return "";
    }


    /**
     * LOCK method handler
     *
     * @param  array  general parameter passing array
     * @return bool   true on success
     */
    function LOCK(&$options) 
    {
        // get absolute fs path to requested resource
        $fspath = $this->base . $options["path"];

        // TODO recursive locks on directories not supported yet
        // makes litmus test "32. lock_collection" fail
        if (is_dir($fspath) && !empty($options["depth"])) {
            return "409 Conflict";
        }

        $options["timeout"] = time()+300; // 5min. hardcoded

        if (isset($options["update"])) { // Lock Update
            $where = "WHERE path = '$options[path]' AND token = '$options[update]'";

            $query = "SELECT owner, exclusivelock FROM {$this->db_prefix}locks $where";
            $res   = mysql_query($query);
            $row   = mysql_fetch_assoc($res);
            mysql_free_result($res);

            if (is_array($row)) {
                $query = "UPDATE {$this->db_prefix}locks 
                                 SET expires = '$options[timeout]' 
                                   , modified = ".time()."
                              $where";
                mysql_query($query);

                $options['owner'] = $row['owner'];
                $options['scope'] = $row["exclusivelock"] ? "exclusive" : "shared";
                $options['type']  = $row["exclusivelock"] ? "write"     : "read";

                return true;
            } else {
                return false;
            }
        }
            
        $query = "INSERT INTO {$this->db_prefix}locks
                        SET token   = '$options[locktoken]'
                          , path    = '$options[path]'
                          , created = ".time()."
                          , modified = ".time()."
                          , owner   = '$options[owner]'
                          , expires = '$options[timeout]'
                          , exclusivelock  = " .($options['scope'] === "exclusive" ? "1" : "0")
            ;
        mysql_query($query);

        return mysql_affected_rows() ? "200 OK" : "409 Conflict";
    }

    /**
     * UNLOCK method handler
     *
     * @param  array  general parameter passing array
     * @return bool   true on success
     */
    function UNLOCK(&$options) 
    {
        $query = "DELETE FROM {$this->db_prefix}locks
                      WHERE path = '$options[path]'
                        AND token = '$options[token]'";
        mysql_query($query);

        return mysql_affected_rows() ? "204 No Content" : "409 Conflict";
    }

    /**
     * checkLock() helper
     *
     * @param  string resource path to check for locks
     * @return bool   true on success
     */
    function checkLock($path) 
    {
        $result = false;
            
        $query = "SELECT owner, token, created, modified, expires, exclusivelock
                  FROM {$this->db_prefix}locks
                 WHERE path = '$path'
               ";
        $res = mysql_query($query);

        if ($res) {
            $row = mysql_fetch_array($res);
            mysql_free_result($res);

            if ($row) {
                $result = array( "type"    => "write",
                                 "scope"   => $row["exclusivelock"] ? "exclusive" : "shared",
                                 "depth"   => 0,
                                 "owner"   => $row['owner'],
                                 "token"   => $row['token'],
                                 "created" => $row['created'],   
                                 "modified" => $row['modified'],   
                                 "expires" => $row['expires']
                                 );
            }
        }

        return $result;
    }


    /**
     * create database tables for property and lock storage
     *
     * @param  void
     * @return bool   true on success
     */
    function create_database() 
    {
        // TODO
        return false;
    }
}


/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * indent-tabs-mode:nil
 * End:
 */
=======
<?php
    oc_require_once("HTTP/WebDAV/Server.php");
    oc_require_once("System.php");
    
    /**
     * Filesystem access using WebDAV
     *
     * @access public
     */
    class HTTP_WebDAV_Server_Filesystem extends HTTP_WebDAV_Server 
    {
        /**
         * Root directory for WebDAV access
         *
         * Defaults to webserver document root (set by ServeRequest)
         *
         * @access private
         * @var    string
         */
        var $base = "";

        /**
         * Serve a webdav request
         *
         * @access public
         * @param  string  
         */
        function ServeRequest($base = false) 
        {
            // special treatment for litmus compliance test
            // reply on its identifier header
            // not needed for the test itself but eases debugging
            if (function_exists("apache_request_headers")) {
				foreach(apache_request_headers() as $key => $value) {
					if (stristr($key,"litmus")) {
						error_log("Litmus test $value");
						header("X-Litmus-reply: ".$value);
					}
				}
			}

            // set root directory, defaults to webserver document root if not set
            if ($base) { 
                $this->base = realpath($base); // TODO throw if not a directory
            } else if (!$this->base) {
                $this->base = $_SERVER['DOCUMENT_ROOT'];
            }
                
            // let the base class do all the work
            parent::ServeRequest();
        }

        /**
         * No authentication is needed here
         *
         * @access private
         * @param  string  HTTP Authentication type (Basic, Digest, ...)
         * @param  string  Username
         * @param  string  Password
         * @return bool    true on successful authentication
         */
        function check_auth($type, $user, $pass) 
        {
            return true;
        }


        /**
         * PROPFIND method handler
         *
         * @param  array  general parameter passing array
         * @param  array  return array for file properties
         * @return bool   true on success
         */
        function PROPFIND(&$options, &$files) 
        {
            // get absolute fs path to requested resource
            $fspath = $this->base . $options["path"];
            
            // sanity check
            if (!file_exists($fspath)) {
                return false;
            }

            // prepare property array
            $files["files"] = array();

            // store information for the requested path itself
            $files["files"][] = $this->fileinfo($options["path"]);

            // information for contained resources requested?
            if (!empty($options["depth"]))  { // TODO check for is_dir() first?
                
                // make sure path ends with '/'
                $options["path"] = $this->_slashify($options["path"]);

                // try to open directory
                $handle = @opendir($fspath);
                
                if ($handle) {
                    // ok, now get all its contents
                    while ($filename = readdir($handle)) {
                        if ($filename != "." && $filename != "..") {
                            $files["files"][] = $this->fileinfo($options["path"].$filename);
                        }
                    }
                    // TODO recursion needed if "Depth: infinite"
                }
            }

            // ok, all done
            return true;
        } 
        
        /**
         * Get properties for a single file/resource
         *
         * @param  string  resource path
         * @return array   resource properties
         */
        function fileinfo($path) 
        {
            // map URI path to filesystem path
            $fspath = $this->base . $path;

            // create result array
            $info = array();
            // TODO remove slash append code when base clase is able to do it itself
            $info["path"]  = is_dir($fspath) ? $this->_slashify($path) : $path; 
            $info["props"] = array();
            
            // no special beautified displayname here ...
            $info["props"][] = $this->mkprop("displayname", strtoupper($path));
            
            // creation and modification time
            $info["props"][] = $this->mkprop("creationdate",    filectime($fspath));
            $info["props"][] = $this->mkprop("getlastmodified", filemtime($fspath));

            // type and size (caller already made sure that path exists)
            if (is_dir($fspath)) {
                // directory (WebDAV collection)
                $info["props"][] = $this->mkprop("resourcetype", "collection");
                $info["props"][] = $this->mkprop("getcontenttype", "httpd/unix-directory");             
            } else {
                // plain file (WebDAV resource)
                $info["props"][] = $this->mkprop("resourcetype", "");
                if (is_readable($fspath)) {
                    $info["props"][] = $this->mkprop("getcontenttype", $this->_mimetype($fspath));
                } else {
                    $info["props"][] = $this->mkprop("getcontenttype", "application/x-non-readable");
                }               
                $info["props"][] = $this->mkprop("getcontentlength", filesize($fspath));
            }

            // get additional properties from database
            $query = "SELECT ns, name, value FROM properties WHERE path = '$path'";
            $res = OC_DB::query($query);
            while ($row = OC_DB::fetch_assoc($res)) {
                $info["props"][] = $this->mkprop($row["ns"], $row["name"], $row["value"]);
            }
            OC_DB::free_result($res);

            return $info;
        }

        /**
         * detect if a given program is found in the search PATH
         *
         * helper function used by _mimetype() to detect if the 
         * external 'file' utility is available
         *
         * @param  string  program name
         * @param  string  optional search path, defaults to $PATH
         * @return bool    true if executable program found in path
         */
        function _can_execute($name, $path = false) 
        {
            // path defaults to PATH from environment if not set
            if ($path === false) {
                $path = getenv("PATH");
            }
            
            // check method depends on operating system
            if (!strncmp(PHP_OS, "WIN", 3)) {
                // on Windows an appropriate COM or EXE file needs to exist
                $exts = array(".exe", ".com");
                $check_fn = "file_exists";
            } else { 
                // anywhere else we look for an executable file of that name
                $exts = array("");
                $check_fn = "is_executable";
            }
            
            // now check the directories in the path for the program
            foreach (explode(PATH_SEPARATOR, $path) as $dir) {
                // skip invalid path entries
                if (!file_exists($dir)) continue;
                if (!is_dir($dir)) continue;

                // and now look for the file
                foreach ($exts as $ext) {
                    if ($check_fn("$dir/$name".$ext)) return true;
                }
            }

            return false;
        }

        
        /**
         * try to detect the mime type of a file
         *
         * @param  string  file path
         * @return string  guessed mime type
         */
        function _mimetype($fspath) 
        {
            if (@is_dir($fspath)) {
                // directories are easy
                return "httpd/unix-directory"; 
            } else if (function_exists("mime_content_type")) {
                // use mime magic extension if available
                $mime_type = mime_content_type($fspath);
            } else if ($this->_can_execute("file")) {
                // it looks like we have a 'file' command, 
                // lets see it it does have mime support
                $fp = popen("file -i '$fspath' 2>/dev/null", "r");
                $reply = fgets($fp);
                pclose($fp);
                
                // popen will not return an error if the binary was not found
                // and find may not have mime support using "-i"
                // so we test the format of the returned string 
                
                // the reply begins with the requested filename
                if (!strncmp($reply, "$fspath: ", strlen($fspath)+2)) {                     
                    $reply = substr($reply, strlen($fspath)+2);
                    // followed by the mime type (maybe including options)
                    if (preg_match('/^[[:alnum:]_-]+/[[:alnum:]_-]+;?.*/', $reply, $matches)) {
                        $mime_type = $matches[0];
                    }
                }
            } 
            
            if (empty($mime_type)) {
                // Fallback solution: try to guess the type by the file extension
                // TODO: add more ...
                // TODO: it has been suggested to delegate mimetype detection 
                //       to apache but this has at least three issues:
                //       - works only with apache
                //       - needs file to be within the document tree
                //       - requires apache mod_magic 
                // TODO: can we use the registry for this on Windows?
                //       OTOH if the server is Windos the clients are likely to 
                //       be Windows, too, and tend do ignore the Content-Type
                //       anyway (overriding it with information taken from
                //       the registry)
                // TODO: have a seperate PEAR class for mimetype detection?
                switch (strtolower(strrchr(basename($fspath), "."))) {
                case ".html":
                    $mime_type = "text/html";
                    break;
                case ".gif":
                    $mime_type = "image/gif";
                    break;
                case ".jpg":
                    $mime_type = "image/jpeg";
                    break;
                default: 
                    $mime_type = "application/octet-stream";
                    break;
                }
            }
            
            return $mime_type;
        }

        /**
         * GET method handler
         * 
         * @param  array  parameter passing array
         * @return bool   true on success
         */
        function GET(&$options) 
        {
            // get absolute fs path to requested resource
            $fspath = $this->base . $options["path"];

            // sanity check
            if (!file_exists($fspath)) return false;
            
            // is this a collection?
            if (is_dir($fspath)) {
                return $this->GetDir($fspath, $options);
            }
            
            // detect resource type
            $options['mimetype'] = $this->_mimetype($fspath); 
                
            // detect modification time
            // see rfc2518, section 13.7
            // some clients seem to treat this as a reverse rule
            // requiering a Last-Modified header if the getlastmodified header was set
            $options['mtime'] = filemtime($fspath);
            
            // detect resource size
            $options['size'] = filesize($fspath);
            
            // no need to check result here, it is handled by the base class
            $options['stream'] = fopen($fspath, "r");
            
            return true;
        }

        /**
         * GET method handler for directories
         *
         * This is a very simple mod_index lookalike.
         * See RFC 2518, Section 8.4 on GET/HEAD for collections
         *
         * @param  string  directory path
         * @return void    function has to handle HTTP response itself
         */
        function GetDir($fspath, &$options) 
        {
            $path = $this->_slashify($options["path"]);
            if ($path != $options["path"]) {
                header("Location: ".$this->base_uri.$path);
                exit;
            }

            // fixed width directory column format
            $format = "%15s  %-19s  %-s\n";

            $handle = @opendir($fspath);
            if (!$handle) {
                return false;
            }

            echo "<html><head><title>Index of ".htmlspecialchars($options['path'])."</title></head>\n";
            
            echo "<h1>Index of ".htmlspecialchars($options['path'])."</h1>\n";
            
            echo "<pre>";
            printf($format, "Size", "Last modified", "Filename");
            echo "<hr>";

            while ($filename = readdir($handle)) {
                if ($filename != "." && $filename != "..") {
                    $fullpath = $fspath."/".$filename;
                    $name = htmlspecialchars($filename);
                    printf($format, 
                           number_format(filesize($fullpath)),
                           strftime("%Y-%m-%d %H:%M:%S", filemtime($fullpath)), 
                           "<a href='$this->base_uri$path$name'>$name</a>");
                }
            }

            echo "</pre>";

            closedir($handle);

            echo "</html>\n";

            exit;
        }

        /**
         * PUT method handler
         * 
         * @param  array  parameter passing array
         * @return bool   true on success
         */
        function PUT(&$options) 
        {
            $fspath = $this->base . $options["path"];

            if (!@is_dir(dirname($fspath))) {
                return "409 Conflict";
            }

            $options["new"] = ! file_exists($fspath);

            $fp = fopen($fspath, "w");

            return $fp;
        }


        /**
         * MKCOL method handler
         *
         * @param  array  general parameter passing array
         * @return bool   true on success
         */
        function MKCOL($options) 
        {           
            $path = $this->base .$options["path"];
            $parent = dirname($path);
            $name = basename($path);

            if (!file_exists($parent)) {
                return "409 Conflict";
            }

            if (!is_dir($parent)) {
                return "403 Forbidden";
            }

            if ( file_exists($parent."/".$name) ) {
                return "405 Method not allowed";
            }

            if (!empty($_SERVER["CONTENT_LENGTH"])) { // no body parsing yet
                return "415 Unsupported media type";
            }
            
            $stat = mkdir ($parent."/".$name,0777);
            if (!$stat) {
                return "403 Forbidden";                 
            }

            return ("201 Created");
        }
        
        
        /**
         * DELETE method handler
         *
         * @param  array  general parameter passing array
         * @return bool   true on success
         */
        function DELETE($options) 
        {
            $path = $this->base . "/" .$options["path"];

            if (!file_exists($path)) {
                return "404 Not found";
            }

            if (is_dir($path)) {
                $query = "DELETE FROM properties WHERE path LIKE '".$this->_slashify($options["path"])."%'";
                OC_DB::query($query);
                System::rm("-rf $path");
            } else {
                unlink ($path);
            }
            $query = "DELETE FROM properties WHERE path = '$options[path]'";
            OC_DB::query($query);

            return "204 No Content";
        }


        /**
         * MOVE method handler
         *
         * @param  array  general parameter passing array
         * @return bool   true on success
         */
        function MOVE($options) 
        {
            return $this->COPY($options, true);
        }

        /**
         * COPY method handler
         *
         * @param  array  general parameter passing array
         * @return bool   true on success
         */
        function COPY($options, $del=false) 
        {
            // TODO Property updates still broken (Litmus should detect this?)

            if (!empty($_SERVER["CONTENT_LENGTH"])) { // no body parsing yet
                return "415 Unsupported media type";
            }

            // no copying to different WebDAV Servers yet
            if (isset($options["dest_url"])) {
                return "502 bad gateway";
            }

            $source = $this->base .$options["path"];
            if (!file_exists($source)) return "404 Not found";

            $dest = $this->base . $options["dest"];

            $new = !file_exists($dest);
            $existing_col = false;

            if (!$new) {
                if ($del && is_dir($dest)) {
                    if (!$options["overwrite"]) {
                        return "412 precondition failed";
                    }
                    $dest .= basename($source);
                    if (file_exists($dest)) {
                        $options["dest"] .= basename($source);
                    } else {
                        $new = true;
                        $existing_col = true;
                    }
                }
            }

            if (!$new) {
                if ($options["overwrite"]) {
                    $stat = $this->DELETE(array("path" => $options["dest"]));
                    if (($stat{0} != "2") && (substr($stat, 0, 3) != "404")) {
                        return $stat; 
                    }
                } else {                
                    return "412 precondition failed";
                }
            }

            if (is_dir($source) && ($options["depth"] != "infinity")) {
                // RFC 2518 Section 9.2, last paragraph
                return "400 Bad request";
            }

            if ($del) {
                if (!rename($source, $dest)) {
                    return "500 Internal server error";
                }
                $destpath = $this->_unslashify($options["dest"]);
                if (is_dir($source)) {
                    $query = "UPDATE properties 
                                 SET path = REPLACE(path, '".$options["path"]."', '".$destpath."') 
                               WHERE path LIKE '".$this->_slashify($options["path"])."%'";
                    OC_DB::query($query);
                }

                $query = "UPDATE properties 
                             SET path = '".$destpath."'
                           WHERE path = '".$options["path"]."'";
                OC_DB::query($query);
            } else {
                if (is_dir($source)) {
                    $files = System::find($source);
                    $files = array_reverse($files);
                } else {
                    $files = array($source);
                }

                if (!is_array($files) || empty($files)) {
                    return "500 Internal server error";
                }
                    
                
                foreach ($files as $file) {
                    if (is_dir($file)) {
                      $file = $this->_slashify($file);
                    }

                    $destfile = str_replace($source, $dest, $file);
                    
                    if (is_dir($file)) {
                        if (!is_dir($destfile)) {
                            // TODO "mkdir -p" here? (only natively supported by PHP 5) 
                            if (!mkdir($destfile)) {
                                return "409 Conflict";
                            }
                        } else {
                          error_log("existing dir '$destfile'");
                        }
                    } else {
                        if (!copy($file, $destfile)) {
                            return "409 Conflict";
                        }
                    }
                }

                $query = "INSERT INTO properties SELECT ... FROM properties WHERE path = '".$options['path']."'";
            }

            return ($new && !$existing_col) ? "201 Created" : "204 No Content";         
        }

        /**
         * PROPPATCH method handler
         *
         * @param  array  general parameter passing array
         * @return bool   true on success
         */
        function PROPPATCH(&$options) 
        {
            global $prefs, $tab;

            $msg = "";
            
            $path = $options["path"];
            
            $dir = dirname($path)."/";
            $base = basename($path);
            
            foreach($options["props"] as $key => $prop) {
                if ($prop["ns"] == "DAV:") {
                    $options["props"][$key]['status'] = "403 Forbidden";
                } else {
                    if (isset($prop["val"])) {
                        $query = "REPLACE INTO properties SET path = '$options[path]', name = '$prop[name]', ns= '$prop[ns]', value = '$prop[val]'";
                        error_log($query);
                    } else {
                        $query = "DELETE FROM properties WHERE path = '$options[path]' AND name = '$prop[name]' AND ns = '$prop[ns]'";
                    }       
                    OC_DB::query($query);
                }
            }
                        
            return "";
        }


        /**
         * LOCK method handler
         *
         * @param  array  general parameter passing array
         * @return bool   true on success
         */
        function LOCK(&$options) 
        {
            if (isset($options["update"])) { // Lock Update
                $query = "UPDATE locks SET expires = ".(time()+300);
                OC_DB::query($query);
                
                if (OC_DB::affected_rows()) {
                    $options["timeout"] = 300; // 5min hardcoded
                    return true;
                } else {
                    return false;
                }
            }
            
            $options["timeout"] = time()+300; // 5min. hardcoded

            $query = "INSERT INTO locks
                        SET token   = '$options[locktoken]'
                          , path    = '$options[path]'
                          , owner   = '$options[owner]'
                          , expires = '$options[timeout]'
                          , exclusivelock  = " .($options['scope'] === "exclusive" ? "1" : "0")
                ;
            OC_DB::query($query);

            return OC_DB::affected_rows() ? "200 OK" : "409 Conflict";
        }

        /**
         * UNLOCK method handler
         *
         * @param  array  general parameter passing array
         * @return bool   true on success
         */
        function UNLOCK(&$options) 
        {
            $query = "DELETE FROM locks
                      WHERE path = '$options[path]'
                        AND token = '$options[token]'";
            OC_DB::query($query);

            return OC_DB::affected_rows() ? "204 No Content" : "409 Conflict";
        }

        /**
         * checkLock() helper
         *
         * @param  string resource path to check for locks
         * @return bool   true on success
         */
        function checkLock($path) 
        {
            $result = false;
            
            $query = "SELECT owner, token, expires, exclusivelock
                  FROM locks
                 WHERE path = '$path'
               ";
            $res = OC_DB::query($query);

            if ($res) {
                $row = OC_DB::fetch_assoc($res);
                OC_DB::free_result($res);

                if ($row) {
                    $result = array( "type"    => "write",
                                                     "scope"   => $row["exclusivelock"] ? "exclusive" : "shared",
                                                     "depth"   => 0,
                                                     "owner"   => $row['owner'],
                                                     "token"   => $row['token'],
                                                     "expires" => $row['expires']
                                                     );
                }
            }

            return $result;
        }


        /**
         * create database tables for property and lock storage
         *
         * @param  void
         * @return bool   true on success
         */
        function create_database() 
        {
            // TODO
            return false;
        }
        
    }


?>
>>>>>>> 854e0c5a9c9060e827fbbfddffeeeadfc2d27278:inc/HTTP/WebDAV/Server/Filesystem.php
