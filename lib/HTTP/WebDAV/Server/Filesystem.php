<?php
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
   --- modified for ownCloud ---
*/
    require_once("lib/base.php");
    require_once("HTTP/WebDAV/Server.php");
    require_once("System.php");

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
        $fspath = $options["path"];

        // sanity check
        if (!OC_FILESYSTEM::file_exists($fspath)) {
            return false;
        }

        // prepare property array
        $files["files"] = array();
        // store information for the requested path itself
        $files["files"][] = $this->fileinfo($options["path"]);
        // information for contained resources requested?
        if (!empty($options["depth"]) && OC_FILESYSTEM::is_dir($fspath) && OC_FILESYSTEM::is_readable($fspath)) {
            // make sure path ends with '/'
            $options["path"] = $this->_slashify($options["path"]);

            // try to open directory
                $handle = @OC_FILESYSTEM::opendir($fspath);

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
        $fspath =$path;

        // create result array
        $info = array();
        // TODO remove slash append code when base clase is able to do it itself
        $info["path"]  = OC_FILESYSTEM::is_dir($fspath) ? $this->_slashify($path) : $path;
        $info["props"] = array();
        // no special beautified displayname here ...
        $info["props"][] = $this->mkprop("displayname", strtoupper($path));

        // creation and modification time
        $info["props"][] = $this->mkprop("creationdate",    OC_FILESYSTEM::filectime($fspath));
        $info["props"][] = $this->mkprop("getlastmodified", OC_FILESYSTEM::filemtime($fspath));
        // Microsoft extensions: last access time and 'hidden' status
        $info["props"][] = $this->mkprop("lastaccessed",     OC_FILESYSTEM::fileatime($fspath));
        $info["props"][] = $this->mkprop("ishidden", ('.' === substr(basename($fspath), 0, 1)));
        // type and size (caller already made sure that path exists)
        if ( OC_FILESYSTEM::is_dir($fspath)) {
            // directory (WebDAV collection)
            $info["props"][] = $this->mkprop("resourcetype", "collection");
            $info["props"][] = $this->mkprop("getcontenttype", "httpd/unix-directory");
        } else {
            // plain file (WebDAV resource)
            $info["props"][] = $this->mkprop("resourcetype", "");
            if ( OC_FILESYSTEM::is_readable($fspath)) {
                $info["props"][] = $this->mkprop("getcontenttype", $this->_mimetype($fspath));
            } else {
                $info["props"][] = $this->mkprop("getcontenttype", "application/x-non-readable");
            }
            $info["props"][] = $this->mkprop("getcontentlength",  OC_FILESYSTEM::filesize($fspath));
        }
        // get additional properties from database
		$query = OC_DB::prepare("SELECT ns, name, value FROM *PREFIX*properties WHERE path = ?");
		$res = $query->execute(array($path))->fetchAll();
		foreach($res as $row){
            $info["props"][] = $this->mkprop($row["ns"], $row["name"], $row["value"]);
        }
        return $info;
    }

    /**
     * try to detect the mime type of a file
     *
     * @param  string  file path
     * @return string  guessed mime type
     */
    function _mimetype($fspath)
    {
        return  OC_FILESYSTEM::getMimeType($fspath);
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
        $fspath = $options["path"];

        // sanity check
        if (! OC_FILESYSTEM::file_exists($fspath)) return false;

        // detect resource type
        $options['mimetype'] = $this->_mimetype($fspath);

        // detect modification time
        // see rfc2518, section 13.7
        // some clients seem to treat this as a reverse rule
        // requiering a Last-Modified header if the getlastmodified header was set
        $options['mtime'] = OC_FILESYSTEM::filemtime($fspath);

        // detect resource size
        $options['size'] = OC_FILESYSTEM::filesize($fspath);

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
        // get absolute fs path to requested resource)
        $fspath = $options["path"];
        // is this a collection?
        if (OC_FILESYSTEM::is_dir($fspath)) {
            return $this->GetDir($fspath, $options);
        }

        // the header output is the same as for HEAD
        if (!$this->HEAD($options)) {
            return false;
        }

        // no need to check result here, it is handled by the base class
        $options['stream'] = OC_FILESYSTEM::fopen($fspath, "r");

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

        if (!OC_FILESYSTEM::is_readable($fspath)) {
            return false;
        }

        $handle = OC_FILESYSTEM::opendir($fspath);
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
                if( substr($fspath, -1) != '/' ){
			$fspath .= '/';
                }
                $fullpath = $fspath.$filename;
                $name     = htmlspecialchars($filename);
                $uri = $_SERVER['SCRIPT_NAME'] . $fullpath;
                printf($format,
                       number_format(OC_FILESYSTEM::filesize($fullpath)),
                       strftime("%Y-%m-%d %H:%M:%S", OC_FILESYSTEM::filemtime($fullpath)),
                       "<a href='$uri'>$name</a>");
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
        $fspath = $options["path"];
        $dir = dirname($fspath);
        if (!OC_FILESYSTEM::file_exists($dir) || !OC_FILESYSTEM::is_dir($dir)) {
            return "409 Conflict"; // TODO right status code for both?
        }

        $options["new"] = ! OC_FILESYSTEM::file_exists($fspath);

        if ($options["new"] && !OC_FILESYSTEM::is_writeable($dir)) {
            return "403 Forbidden";
        }
        if (!$options["new"] && !OC_FILESYSTEM::is_writeable($fspath)) {
            return "403 Forbidden";
        }
        if (!$options["new"] && OC_FILESYSTEM::is_dir($fspath)) {
            return "403 Forbidden";
        }
        $fp = OC_FILESYSTEM::fopen($fspath, "w");

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
        $path   = $options["path"];
        $parent = dirname($path);
        $name   = basename($path);
        if (!OC_FILESYSTEM::file_exists($parent)) {
            return "409 Conflict";
        }

        if (!OC_FILESYSTEM::is_dir($parent)) {
            return "403 Forbidden";
        }

        if ( OC_FILESYSTEM::file_exists($parent."/".$name) ) {
            return "405 Method not allowed";
        }

        if (!empty($this->_SERVER["CONTENT_LENGTH"])) { // no body parsing yet
            return "415 Unsupported media type";
        }

        $stat = OC_FILESYSTEM::mkdir($parent."/".$name, 0777);
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
        $path =$options["path"];
        if (!OC_FILESYSTEM::file_exists($path)) {
            return "404 Not found";
        }
		$lock=self::checkLock($path);
		if(is_array($lock)){
			$owner=$options['owner'];
			$lockOwner=$lock['owner'];
			if($owner==$lockOwner){
				return "423 Locked";
			}
		}
        if (OC_FILESYSTEM::is_dir($path)) {
                $query = OC_DB::prepare("DELETE FROM *PREFIX*properties WHERE path LIKE '?%'");
                $query->execute(array($this->_slashify($options["path"])));
				OC_FILESYSTEM::delTree($path);
        } else {
            OC_FILESYSTEM::unlink($path);
        }
            $query = OC_DB::prepare("DELETE FROM *PREFIX*properties WHERE path = ?");
            $query->execute(array($options['path']));

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

        $source = $options["path"];
        if (!OC_FILESYSTEM::file_exists($source)) {
            return "404 Not found";
        }

        if (OC_FILESYSTEM::is_dir($source)) { // resource is a collection
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

        $dest         = $options["dest"];
        $destdir      = dirname($dest);

        if (!OC_FILESYSTEM::file_exists($destdir) || !OC_FILESYSTEM::is_dir($destdir)) {
            return "409 Conflict";
        }


        $new          = !OC_FILESYSTEM::file_exists($dest);
        $existing_col = false;

        if (!$new) {
            if ($del && OC_FILESYSTEM::is_dir($dest)) {
                if (!$options["overwrite"]) {
                    return "412 precondition failed";
                }
                $dest .= basename($source);
                if (OC_FILESYSTEM::file_exists($dest)) {
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
            if (!OC_FILESYSTEM::rename($source, $dest)) {
                return "500 Internal server error";
            }
            $destpath = $this->_unslashify($options["dest"]);
            if (is_dir($source)) {
					$dpath=$destpath;
					$path=$options["path"];
                    $query = OC_DB::prepare("UPDATE *PREFIX*properties
                                 SET path = REPLACE(path, ?, ?)
                               WHERE path LIKE '?%'");
                    $query->execute(array($path,$dpath,$path));
            }

                $query = OC_DB::prepare("UPDATE *PREFIX*properties
                             SET path = ?
                           WHERE path = ?");
                $query->execute(array($dpath,$path));
        } else {
            if (OC_FILESYSTEM::is_dir($source)) {
                $files = OC_FILESYSTEM::getTree($source);
            } else {
                $files = array($source);
            }

            if (!is_array($files) || empty($files)) {
                return "500 Internal server error";
            }


            foreach ($files as $file) {
                if (OC_FILESYSTEM::is_dir($file)) {
                    $file = $this->_slashify($file);
                }
                $destfile = str_replace($source, $dest, $file);

                if (OC_FILESYSTEM::is_dir($file)) {
                    if (!OC_FILESYSTEM::file_exists($destfile)) {
                        if (!OC_FILESYSTEM::is_writeable(dirname($destfile))) {
                            return "403 Forbidden";
                        }
                        if (!OC_FILESYSTEM::mkdir($destfile)) {
                            return "409 Conflict";
                        }
                    } else if (!OC_FILESYSTEM::is_dir($destfile)) {
                        return "409 Conflict";
                    }
                } else {
                    if (!OC_FILESYSTEM::copy($file, $destfile)) {
                        return "409 Conflict($source) $file --> $destfile   ".implode('::',$files);
                    }
                }
            }
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
				$path=$options['path'];
				$name=$prop['name'];
				$ns=$prop['ns'];
				$val=$prop['val'];
                if (isset($prop["val"])) {
                        $query = OC_DB::prepare("REPLACE INTO *PREFIX*properties (path,name,ns,value) VALUES(?,?,?,?)");
                        $query->execute(array($path,$name,$ns,$val));
                } else {
                        $query = $query = OC_DB::prepare("DELETE FROM *PREFIX*properties WHERE path = ? AND name = ? AND ns = ?");
                        $query->execute(array($path,$name,$ns));
                }
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
        $fspath = $options["path"];
        // TODO recursive locks on directories not supported yet
        // makes litmus test "32. lock_collection" fail
        if (OC_FILESYSTEM::is_dir($fspath) && !empty($options["depth"])) {
            switch($options["depth"]){
				case 'infinity':
					$recursion=1;
					break;
				case '0':
					$recursion=0;
					break;
            }
        }else{
			$recursion=0;
        }

        $options["timeout"] = time()+300; // 5min. hardcoded

        if (isset($options["update"])) { // Lock Update
            $where = "WHERE path = ? AND token = ?";

            $query = OC_DB::prepare("SELECT owner, exclusivelock FROM *PREFIX*locks $where");
            $res   = $query->execute(array($options[path],$options[update]))->fetchAll();

            if (is_array($res) and isset($res[0])) {
				$row=$res[0];
                $query = OC_DB::prepare("UPDATE `*PREFIX*locks` SET `expires` = , `modified` =  $where");
                $query->execute(array($options[timeout],time(),$options[path],$options[update]));

                $options['owner'] = $row['owner'];
                $options['scope'] = $row["exclusivelock"] ? "exclusive" : "shared";
                $options['type']  = $row["exclusivelock"] ? "write"     : "read";

                return true;
            } else {//check for indirect refresh
			$query = OC_DB::prepare("SELECT * FROM *PREFIX*locks WHERE recursive = 1");
            $res = $query->execute();
            foreach($res as $row){
				if(strpos($options['path'],$row['path'])==0){//are we a child of a folder with an recursive lock
					$where = "WHERE path = ? AND token = ?";
					$query = OC_DB::prepare("UPDATE `*PREFIX*locks` SET `expires` = ?, `modified` = ? $where");
					$query->execute(array($options[timeout],time(),$row[path],$options[update]));
					$options['owner'] = $row['owner'];
					$options['scope'] = $row["exclusivelock"] ? "exclusive" : "shared";
					$options['type']  = $row["exclusivelock"] ? "write"     : "read";
					return true;
				}
            }
            }
        }

		$locktoken=$options['locktoken'];
		$path=$options['path'];
		$time=time();
		$owner=$options['owner'];
		$timeout=$options['timeout'];
		$exclusive=($options['scope'] === "exclusive" ? "1" : "0");
        $query = OC_DB::prepare("INSERT INTO `*PREFIX*locks`
(`token`,`path`,`created`,`modified`,`owner`,`expires`,`exclusivelock`,`recursive`)
VALUES (?,?,?,?,?,'timeout',?,?)");
            $result=$query->execute(array($locktoken,$path,$time,$time,$owner,$exclusive,$recursion));
			if(!OC_FILESYSTEM::file_exists($fspath) and $rows>0) {
				return "201 Created";
			}
            return PEAR::isError($result) ? "409 Conflict" : "200 OK";
    }

    /**
     * UNLOCK method handler
     *
     * @param  array  general parameter passing array
     * @return bool   true on success
     */
    function UNLOCK(&$options)
    {
            $query = OC_DB::prepare("DELETE FROM *PREFIX*locks
                      WHERE path = ?
                        AND token = ?");
            $query->execute(array($options[path]),$options[token]);
			return PEAR::isError($result) ? "409 Conflict" : "204 No Content";
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
        $query = OC_DB::prepare("SELECT *
                  FROM *PREFIX*locks
                 WHERE path = ?
               ");
            $res = $query->execute(array($path))->fetchAll();
        if (is_array($res) and isset($res[0])) {
				$row=$res[0];

            if ($row) {
                $result = array( "type"    => "write",
                                 "scope"   => $row["exclusivelock"] ? "exclusive" : "shared",
                                 "depth"   => 0,
                                 "owner"   => $row['owner'],
                                 "token"   => $row['token'],
                                 "created" => $row['created'],
                                 "modified" => $row['modified'],
                                 "expires" => $row['expires'],
                                 "recursive" => $row['recursive']
                                 );
            }
        }else{
			//check for recursive locks;
			$query = OC_DB::prepare("SELECT *
                  FROM *PREFIX*locks
                 WHERE recursive = 1
               ");
            $res = $query->execute()->fetchAll();
            foreach($res as $row){
				if(strpos($path,$row['path'])==0){//are we a child of a folder with an recursive lock
					$result = array( "type"    => "write",
                                 "scope"   => $row["exclusivelock"] ? "exclusive" : "shared",
                                 "depth"   => 0,
                                 "owner"   => $row['owner'],
                                 "token"   => $row['token'],
                                 "created" => $row['created'],
                                 "modified" => $row['modified'],
                                 "expires" => $row['expires'],
                                 "recursive" => $row['recursive']
                                 );
				}
            }
        }

        return $result;
    }
}

?>
