<?php
/**
 * ownCloud file storage implementation for Git repositories
 * @author Craig Roberts
 * @copyright 2012 Craig Roberts craig0990@googlemail.com
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU AFFERO GENERAL PUBLIC LICENSE for more details.
 *
 * You should have received a copy of the GNU Affero General Public
 * License along with this library.  If not, see <http://www.gnu.org/licenses/>.
 */

// Include Granite
require_once('lib_granite.php');

// Create a top-level 'Backup' directory if it does not already exist
$user = OC_User::getUser();
if (OC_Filesystem::$loaded and !OC_Filesystem::is_dir('/Backup')) {
    OC_Filesystem::mkdir('/Backup');
    OC_Preferences::setValue(OC_User::getUser(), 'files_versioning', 'head', 'HEAD');
}

// Generate the repository path (currently using 'full' repositories, as opposed to bare ones)
$repo_path = DIRECTORY_SEPARATOR
    . OC_User::getUser()
    . DIRECTORY_SEPARATOR
    . 'files'
    . DIRECTORY_SEPARATOR
    . 'Backup';

// Mount the 'Backup' folder using the versioned storage provider below
OC_Filesystem::mount('OC_Filestorage_Versioned', array('repo'=>$repo_path), $repo_path . DIRECTORY_SEPARATOR);

class OC_Filestorage_Versioned extends OC_Filestorage {

    /**
     * Holds an instance of Granite\Git\Repository
     */
    protected $repo;

    /**
     * Constructs a new OC_Filestorage_Versioned instance, expects an associative
     * array with a `repo` key set to the path of the repository's `.git` folder
     *
     * @param array $parameters An array containing the key `repo` pointing to the
     *                             repository path.
     */
    public function __construct($parameters) {
        // Get the full path to the repository folder
        $path = OC_Config::getValue('datadirectory', OC::$SERVERROOT.'/data')
                . $parameters['repo']
                . DIRECTORY_SEPARATOR
                . '.git'
                . DIRECTORY_SEPARATOR;

        try {
            // Attempt to load the repository
            $this->repo = new Granite\Git\Repository($path);
        } catch (InvalidArgumentException $e) {
            // $path is not a valid Git repository, we must create one
            Granite\Git\Repository::init($path);

            // Load the newly-initialised repository
            $this->repo = new Granite\Git\Repository($path);

            /**
             * Create an initial commit with a README file
             * FIXME: This functionality should be transferred to the Granite library
             */
            $blob = new Granite\Git\Blob($this->repo->path());
            $blob->content('Your Backup directory is now ready for use.');

            // Create a new tree to hold the README file
            $tree = $this->repo->factory('tree');
            // Create a tree node to represent the README blob
            $tree_node = new Granite\Git\Tree\Node('README', '100644', $blob->sha());
            $tree->nodes(array($tree_node->name() => $tree_node));

            // Create an initial commit
            $commit = new Granite\Git\Commit($this->repo->path());
            $user_string = OC_User::getUser() . ' ' . time() . ' +0000';
            $commit->author($user_string);
            $commit->committer($user_string);
            $commit->message('Initial commit');
            $commit->tree($tree);

            // Write it all to disk
            $blob->write();
            $tree->write();
            $commit->write();

            // Update the HEAD for the 'master' branch
            $this->repo->head('master', $commit->sha());
        }

        // Update the class pointer to the HEAD
        $head = OC_Preferences::getValue(OC_User::getUser(), 'files_versioning', 'head', 'HEAD');

        // Load the most recent commit if the preference is not set
        if ($head == 'HEAD') {
            $this->head = $this->repo->head()->sha();
        } else {
            $this->head = $head;
        }
    }

    public function mkdir($path) {
        if (mkdir("versioned:/{$this->repo->path()}$path#{$this->head}")) {
            $this->head = $this->repo->head()->sha();
            OC_Preferences::setValue(OC_User::getUser(), 'files_versioning', 'head', $head);
            return true;
        }

        return false;
    }

    public function rmdir($path) {

    }

    /**
     * Returns a directory handle to the requested path, or FALSE on failure
     *
     * @param string $path The directory path to open
     *
     * @return boolean|resource A directory handle, or FALSE on failure
     */
    public function opendir($path) {
        return opendir("versioned:/{$this->repo->path()}$path#{$this->head}");
    }

    /**
     * Returns TRUE if $path is a directory, or FALSE if not
     *
     * @param string $path The path to check
     *
     * @return boolean
     */
    public function is_dir($path) {
        return $this->filetype($path) == 'dir';
    }

    /**
     * Returns TRUE if $path is a file, or FALSE if not
     *
     * @param string $path The path to check
     *
     * @return boolean
     */
    public function is_file($path) {
        return $this->filetype($path) == 'file';
    }

    public function stat($path)
	{
	    return stat("versioned:/{$this->repo->path()}$path#{$this->head}");
    }

    /**
     * Returns the strings 'dir' or 'file', depending on the type of $path
     *
     * @param string $path The path to check
     *
     * @return string Returns 'dir' if a directory, 'file' otherwise
     */
    public function filetype($path) {
	    if ($path == "" || $path == "/") {
	        return 'dir';
        } else {
            if (substr($path, -1) == '/') {
                $path = substr($path, 0, -1);
            }

            $node = $this->tree_search($this->repo, $this->repo->factory('commit', $this->head)->tree(), $path);

            // Does it exist, or is it new?
            if ($node == null) {
                // New file
                return 'file';
            } else {
                // Is it a tree?
                try {
                    $this->repo->factory('tree', $node);
                    return 'dir';
                } catch (InvalidArgumentException $e) {
                    // Nope, must be a blob
                    return 'file';
                }
            }
        }
    }

    public function filesize($path) {
        return filesize("versioned:/{$this->repo->path()}$path#{$this->head}");
    }

    /**
     * Returns a boolean value representing whether $path is readable
     *
     * @param string $path The path to check
     *(
     * @return boolean Whether or not the path is readable
     */
    public function is_readable($path) {
        return true;
    }

    /**
     * Returns a boolean value representing whether $path is writable
     *
     * @param string $path The path to check
     *(
     * @return boolean Whether or not the path is writable
     */
    public function is_writable($path) {

        $head = OC_Preferences::getValue(OC_User::getUser(), 'files_versioning', 'head', 'HEAD');
        if ($head !== 'HEAD' && $head !== $this->repo->head()->sha()) {
            // Cannot modify previous commits
            return false;
        }
        return true;
    }

    /**
     * Returns a boolean value representing whether $path exists
     *
     * @param string $path The path to check
     *(
     * @return boolean Whether or not the path exists
     */
    public function file_exists($path) {
        return file_exists("versioned:/{$this->repo->path()}$path#{$this->head}");
    }

    /**
     * Returns an integer value representing the inode change time
     * (NOT IMPLEMENTED)
     *
     * @param string $path The path to check
     *(
     * @return int Timestamp of the last inode change
     */
    public function filectime($path) {
        return -1;
    }

    /**
     * Returns an integer value representing the file modification time
     *
     * @param string $path The path to check
     *(
     * @return int Timestamp of the last file modification
     */
    public function filemtime($path) {
        return filemtime("versioned:/{$this->repo->path()}$path#{$this->head}");
    }

    public function file_get_contents($path) {
        return file_get_contents("versioned:/{$this->repo->path()}$path#{$this->head}");
    }

    public function file_put_contents($path, $data) {
        $success = file_put_contents("versioned:/{$this->repo->path()}$path#{$this->head}", $data);
        if ($success !== false) {
            // Update the HEAD in the preferences
            OC_Preferences::setValue(OC_User::getUser(), 'files_versioning', 'head', $this->repo->head()->sha());
            return $success;
        }

        return false;
    }

    public function unlink($path) {

    }

    public function rename($path1, $path2) {

    }

    public function copy($path1, $path2) {

    }

    public function fopen($path, $mode) {
	    return fopen("versioned:/{$this->repo->path()}$path#{$this->head}", $mode);
    }

    public function getMimeType($path) {
        if ($this->filetype($path) == 'dir') {
	        return 'httpd/unix-directory';
	    } elseif ($this->filesize($path) == 0) {
	        // File's empty, returning text/plain allows opening in the web editor
	        return 'text/plain';
	    } else {
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            /**
             * We need to represent the repository path, the file path, and the
             * revision, which can be simply achieved with a convention of using
             * `.git` in the repository directory (bare or not) and the '#part'
             * segment of a URL to specify the revision. For example
             *
             * versioned://var/www/myrepo.git/docs/README.md#HEAD ('bare' repo)
             * versioned://var/www/myrepo/.git/docs/README.md#HEAD ('full' repo)
             * versioned://var/www/myrepo/.git/docs/README.md#6a8f...8a54 ('full' repo and SHA-1 commit ID)
             */
            $mime = $finfo->buffer(file_get_contents("versioned:/{$this->repo->path()}$path#{$this->head}"));
            return $mime;
	    }
    }

    /**
     * Generates a hash based on the file contents
     *
     * @param string $type The hashing algorithm to use (e.g. 'md5', 'sha256', etc.)
     * @param string $path The file to be hashed
     * @param boolean $raw Outputs binary data if true, lowercase hex digits otherwise
     *
     * @return string Hashed string representing the file contents
     */
    public function hash($type, $path, $raw) {
        return hash($type, file_get_contents($path), $raw);
    }

    public function free_space($path) {
    }

    public function search($query) {

    }

    public function touch($path, $mtime=null) {

    }


    public function getLocalFile($path) {
    }

    /**
     * Recursively searches a tree for a path, returning FALSE if is not found
     * or an SHA-1 id if it is found.
     *
     * @param string  $repo  The repository containing the tree object
     * @param string  $tree  The tree object to search
     * @param string  $path  The path to search for (relative to the tree)
     * @param int     $depth The depth of the current search (for recursion)
     *
     * @return string|boolean The SHA-1 id of the sub-tree
     */
    private function tree_search($repo, $tree, $path, $depth = 0)
    {
        $paths = array_values(explode(DIRECTORY_SEPARATOR, $path));

        $current_path = $paths[$depth];

        $nodes = $tree->nodes();
        foreach ($nodes as $node) {
            if ($node->name() == $current_path) {

                if (count($paths)-1 == $depth) {
                    // Stop, found it
                    return $node->sha();
                }

                // Recurse if necessary
                if ($node->isDirectory()) {
                    $tree = $this->repo->factory('tree', $node->sha());
                    return $this->tree_search($repo, $tree, $path, $depth + 1);
                }
            }
        }

        return false;
    }

}
