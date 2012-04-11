<?php

final class OC_VersionStreamWrapper {

    /**
     * Determines whether or not to log debug messages with `OC_Log::write()`
     */
    private $debug = true;

    /**
     * The name of the ".empty" files created in new directories
     */
    const EMPTYFILE = '.empty';

    /**
     * Stores the current position for `readdir()` etc. calls
     */
    private $dir_position = 0;

    /**
     * Stores the current position for `fread()`, `fseek()` etc. calls
     */
    private $file_position = 0;

    /**
     * Stores the current directory tree for `readdir()` etc. directory traversal
     */
    private $tree;

    /**
     * Stores the current file for `fread()`, `fseek()`, etc. calls
     */
    private $blob;

    /**
     * Stores the current commit for `fstat()`, `stat()`, etc. calls
     */
    private $commit;

    /**
     * Stores the current path for `fwrite()`, `file_put_contents()` etc. calls
     */
    private $path;

    /**
     * Close directory handle
     */
    public function dir_closedir() {
        unset($this->tree);
        return true;
    }

    /**
     * Open directory handle
     */
    public function dir_opendir($path, $options) {
        // Parse the URL into a repository directory, file path and commit ID
        list($this->repo, $repo_file, $this->commit) = $this->parse_url($path);

        if ($repo_file == '' || $repo_file == '/') {
            // Set the tree property for the future `readdir()` etc. calls
            $this->tree = array_values($this->commit->tree()->nodes());
            return true;
        } elseif ($this->tree_search($this->repo, $this->commit->tree(), $repo_file) !== false) {
            // Something exists at this path, is it a directory though?
            try {
                $tree = $this->repo->factory(
                    'tree',
                    $this->tree_search($this->repo, $this->commit->tree(), $repo_file)
                );
                $this->tree = array_values($tree->nodes());
                return true;
            } catch (InvalidArgumentException $e) {
                // Trying to call `opendir()` on a file, return false below
            }
        }

        // Unable to find the directory, return false
        return false;
    }

    /**
     * Read entry from directory handle
     */
    public function dir_readdir() {
        return isset($this->tree[$this->dir_position])
               ? $this->tree[$this->dir_position++]->name()
               : false;
    }

    /**
     * Rewind directory handle
     */
    public function dir_rewinddir() {
        $this->dir_position = 0;
    }

    /**
     * Create a directory
     * Git doesn't track empty directories, so a ".empty" file is added instead
     */
    public function mkdir($path, $mode, $options) {
        // Parse the URL into a repository directory, file path and commit ID
        list($this->repo, $repo_file, $this->commit) = $this->parse_url($path);

        // Create an empty file for Git
        $empty = new Granite\Git\Blob($this->repo->path());
        $empty->content('');
        $empty->write();

        if (dirname($repo_file) == '.') {
            // Adding a new directory to the root tree
            $tree = $this->repo->head()->tree();
        } else {
            $tree = $this->repo->factory('tree', $this->tree_search(
                    $this->repo, $this->repo->head()->tree(), dirname($repo_file)
                )
            );
        }

        // Create our new tree, with our empty file
        $dir = $this->repo->factory('tree');
        $nodes = array();
        $nodes[self::EMPTYFILE] = new Granite\Git\Tree\Node(self::EMPTYFILE, '100644', $empty->sha());
        $dir->nodes($nodes);
        $dir->write();

        // Add our new tree to its parent
        $nodes = $tree->nodes();
        $nodes[basename($repo_file)] = new Granite\Git\Tree\Node(basename($repo_file), '040000', $dir->sha());
        $tree->nodes($nodes);
        $tree->write();

        // We need to recursively update each parent tree, since they are all
        // hashed and the changes will cascade back up the chain

        // So, we're currently at the bottom-most directory
        $current_dir = dirname($repo_file);
        $previous_tree = $tree;

        if ($current_dir !== '.') {
            do {
                // Determine the parent directory
                $previous_dir = $current_dir;
                $current_dir = dirname($current_dir);

                $current_tree = $current_dir !== '.'
                                ? $this->repo->factory(
                                      'tree', $this->tree_search(
                                          $this->repo,
                                          $this->repo->head()->tree(),
                                          $current_dir
                                      )
                                  )
                                : $this->repo->head()->tree();

                $current_nodes = $current_tree->nodes();
                $current_nodes[basename($previous_dir)] = new Granite\Git\Tree\Node(
                    basename($previous_dir), '040000', $previous_tree->sha()
                );
                $current_tree->nodes($current_nodes);
                $current_tree->write();

                $previous_tree = $current_tree;
            } while ($current_dir !== '.');

            $tree = $previous_tree;
        }

        // Create a new commit to represent this write
        $commit = $this->repo->factory('commit');
        $username = OC_User::getUser();
        $user_string = $username . ' ' . time() . ' +0000';
        $commit->author($user_string);
        $commit->committer($user_string);
        $commit->message("$username created the `$repo_file` directory, " . date('d F Y H:i', time()) . '.');
        $commit->parents(array($this->repo->head()->sha()));
        $commit->tree($tree);

        // Write it to disk
        $commit->write();

        // Update the HEAD for the 'master' branch
        $this->repo->head('master', $commit->sha());

        return true;
    }

    /**
     * Renames a file or directory
     */
    public function rename($path_from, $path_to) {

    }

    /**
     * Removes a directory
     */
    public function rmdir($path, $options) {

    }

    /**
     * Retrieve the underlaying resource (NOT IMPLEMENTED)
     */
    public function stream_cast($cast_as) {
        return false;
    }

    /**
     * Close a resource
     */
    public function stream_close() {
        unset($this->blob);
        return true;
    }

    /**
     * Tests for end-of-file on a file pointer
     */
    public function stream_eof() {
        return !($this->file_position < strlen($this->blob));
    }

    /**
     * Flushes the output (NOT IMPLEMENTED)
     */
    public function stream_flush() {
        return false;
    }

    /**
     * Advisory file locking (NOT IMPLEMENTED)
     */
    public function stream_lock($operation) {
        return false;
    }

    /**
     * Change stream options (NOT IMPLEMENTED)
     * Called in response to `chgrp()`, `chown()`, `chmod()` and `touch()`
     */
    public function stream_metadata($path, $option, $var) {
        return false;
    }

    /**
     * Opens file or URL
     */
    public function stream_open($path, $mode, $options, &$opened_path) {
        // Store the path, so we can use it later in `stream_write()` if necessary
        $this->path = $path;
        // Parse the URL into a repository directory, file path and commit ID
        list($this->repo, $repo_file, $this->commit) = $this->parse_url($path);

        $file = $this->tree_search($this->repo, $this->commit->tree(), $repo_file);
        if ($file !== false) {
            try {
                $this->blob = $this->repo->factory('blob', $file)->content();
                return true;
            } catch (InvalidArgumentException $e) {
                // Trying to open a directory, return false below
            }
        } elseif ($mode !== 'r') {
            // All other modes allow opening for reading and writing, clearly
            // some 'write' files may not exist yet...
            return true;
        }

        // File could not be found or is not actually a file
        return false;
    }

    /**
     * Read from stream
     */
    public function stream_read($count) {
        // Fetch the remaining set of bytes
        $bytes = substr($this->blob, $this->file_position, $count);

        // If EOF or empty string, return false
        if ($bytes == '' || $bytes == false) {
            return false;
        }

        // If $count does not extend past EOF, add $count to stream offset
        if ($this->file_position + $count < strlen($this->blob)) {
            $this->file_position += $count;
        } else {
            // Otherwise return all remaining bytes
            $this->file_position = strlen($this->blob);
        }

        return $bytes;
    }

    /**
     * Seeks to specific location in a stream
     */
    public function stream_seek($offset, $whence = SEEK_SET) {
        $new_offset = false;

        switch ($whence)
        {
            case SEEK_SET:
                $new_offset = $offset;
            break;
            case SEEK_CUR:
                $new_offset = $this->file_position += $offset;
            break;
            case SEEK_END:
                $new_offset = strlen($this->blob) + $offset;
            break;
        }

        $this->file_position = $offset;

        return ($new_offset !== false);
    }

    /**
     * Change stream options (NOT IMPLEMENTED)
     */
    public function stream_set_option($option, $arg1, $arg2) {
        return false;
    }

    /**
     * Retrieve information about a file resource (NOT IMPLEMENTED)
     */
    public function stream_stat() {

    }

    /**
     * Retrieve the current position of a stream
     */
    public function stream_tell() {
        return $this->file_position;
    }

    /**
     * Truncate stream
     */
    public function stream_truncate($new_size) {

    }

    /**
     * Write to stream
     * FIXME: Could use heavy refactoring
     */
    public function stream_write($data) {
        /**
         * FIXME: This also needs to be added to Granite, in the form of `add()`,
         * `rm()` and `commit()` calls
         */

        // Parse the URL into a repository directory, file path and commit ID
        list($this->repo, $repo_file, $this->commit) = $this->parse_url($this->path);

        $node = $this->tree_search($this->repo, $this->commit->tree(), $repo_file);

        if ($node !== false) {
            // File already exists, attempting modification of existing tree
            try {
                $this->repo->factory('blob', $node);

                // Create our new blob with the provided $data
                $blob = $this->repo->factory('blob');
                $blob->content($data);
                $blob->write();

                // We know the tree exists, so strip the filename from the path and
                // find it...

                if (dirname($repo_file) == '.' || dirname($repo_file) == '') {
                    // Root directory
                    $tree = $this->repo->head()->tree();
                } else {
                    // Sub-directory
                    $tree = $this->repo->factory('tree', $this->tree_search(
                            $this->repo,
                            $this->repo->head()->tree(),
                            dirname($repo_file)
                        )
                    );
                }

                // Replace the old blob with our newly modified one
                $tree_nodes = $tree->nodes();
                $tree_nodes[basename($repo_file)] = new Granite\Git\Tree\Node(
                    basename($repo_file), '100644', $blob->sha()
                );
                $tree->nodes($tree_nodes);
                $tree->write();

                // We need to recursively update each parent tree, since they are all
                // hashed and the changes will cascade back up the chain

                // So, we're currently at the bottom-most directory
                $current_dir = dirname($repo_file);
                $previous_tree = $tree;

                if ($current_dir !== '.') {
                    do {
                        // Determine the parent directory
                        $previous_dir = $current_dir;
                        $current_dir = dirname($current_dir);

                        $current_tree = $current_dir !== '.'
                                        ? $this->repo->factory(
                                              'tree', $this->tree_search(
                                                  $this->repo,
                                                  $this->repo->head()->tree(),
                                                  $current_dir
                                              )
                                          )
                                        : $this->repo->head()->tree();

                        $current_nodes = $current_tree->nodes();
                        $current_nodes[basename($previous_dir)] = new Granite\Git\Tree\Node(
                            basename($previous_dir), '040000', $previous_tree->sha()
                        );
                        $current_tree->nodes($current_nodes);
                        $current_tree->write();

                        $previous_tree = $current_tree;
                    } while ($current_dir !== '.');
                }

                // Create a new commit to represent this write
                $commit = $this->repo->factory('commit');
                $username = OC_User::getUser();
                $user_string = $username . ' ' . time() . ' +0000';
                $commit->author($user_string);
                $commit->committer($user_string);
                $commit->message("$username modified the `$repo_file` file, " . date('d F Y H:i', time()) . '.');
                $commit->parents(array($this->repo->head()->sha()));
                $commit->tree($previous_tree);

                // Write it to disk
                $commit->write();

                // Update the HEAD for the 'master' branch
                $this->repo->head('master', $commit->sha());

                // If we made it this far, write was successful - update the stream
                // position and return the number of bytes written
                $this->file_position += strlen($data);
                return strlen($data);

            } catch (InvalidArgumentException $e) {
                // Attempting to write to a directory or other error, fail
                return 0;
            }
        } else {
            // File does not exist, needs to be created

            // Create our new blob with the provided $data
            $blob = $this->repo->factory('blob');
            $blob->content($data);
            $blob->write();

            if (dirname($repo_file) == '.') {
                // Trying to add a new file to the root tree, nice and easy
                $tree = $this->repo->head()->tree();
                $tree_nodes = $tree->nodes();
                $tree_nodes[basename($repo_file)] = new Granite\Git\Tree\Node(
                    basename($repo_file), '100644', $blob->sha()
                );
                $tree->nodes($tree_nodes);
                $tree->write();
            } else {
                // Trying to add a new file to a subdirectory, try and find it
                $tree = $this->repo->factory('tree', $this->tree_search(
                        $this->repo, $this->repo->head()->tree(), dirname($repo_file)
                    )
                );

                // Add the blob to the tree
                $nodes = $tree->nodes();
                $nodes[basename($repo_file)] =  new Granite\Git\Tree\Node(
                    basename($repo_file), '100644', $blob->sha()
                );
                $tree->nodes($nodes);
                $tree->write();

                // We need to recursively update each parent tree, since they are all
                // hashed and the changes will cascade back up the chain

                // So, we're currently at the bottom-most directory
                $current_dir = dirname($repo_file);
                $previous_tree = $tree;

                if ($current_dir !== '.') {
                    do {
                        // Determine the parent directory
                        $previous_dir = $current_dir;
                        $current_dir = dirname($current_dir);

                        $current_tree = $current_dir !== '.'
                                        ? $this->repo->factory(
                                              'tree', $this->tree_search(
                                                  $this->repo,
                                                  $this->repo->head()->tree(),
                                                  $current_dir
                                              )
                                          )
                                        : $this->repo->head()->tree();

                        $current_nodes = $current_tree->nodes();
                        $current_nodes[basename($previous_dir)] = new Granite\Git\Tree\Node(
                            basename($previous_dir), '040000', $previous_tree->sha()
                        );
                        $current_tree->nodes($current_nodes);
                        $current_tree->write();

                        $previous_tree = $current_tree;
                    } while ($current_dir !== '.');

                    $tree = $previous_tree;
                }
            }

            // Create a new commit to represent this write
            $commit = $this->repo->factory('commit');
            $username = OC_User::getUser();
            $user_string = $username . ' ' . time() . ' +0000';
            $commit->author($user_string);
            $commit->committer($user_string);
            $commit->message("$username created the `$repo_file` file, " . date('d F Y H:i', time()) . '.');
            $commit->parents(array($this->repo->head()->sha()));
            $commit->tree($tree); // Top-level tree (NOT the newly modified tree)

            // Write it to disk
            $commit->write();

            // Update the HEAD for the 'master' branch
            $this->repo->head('master', $commit->sha());

            // If we made it this far, write was successful - update the stream
            // position and return the number of bytes written
            $this->file_position += strlen($data);
            return strlen($data);
        }

        // Write failed
        return 0;
    }

    /**
     * Delete a file
     */
    public function unlink($path) {

    }

    /**
     * Retrieve information about a file
     */
    public function url_stat($path, $flags) {
        // Parse the URL into a repository directory, file path and commit ID
        list($this->repo, $repo_file, $this->commit) = $this->parse_url($path);

        $node = $this->tree_search($this->repo, $this->commit->tree(), $repo_file);

        if ($node == false && $this->commit->sha() == $this->repo->head()->sha()) {
            // A new file - no information available
            $size = 0;
            $mtime = -1;
        } else {

            // Is it a directory?
            try {
                $this->repo->factory('tree', $node);
                $size = 4096; // FIXME
            } catch (InvalidArgumentException $e) {
                // Must be a file
                $size = strlen(file_get_contents($path));
            }

            // Parse the timestamp from the commit message
	        preg_match('/[0-9]{10}+/', $this->commit->committer(), $matches);
	        $mtime = $matches[0];
        }

	    $stat["dev"] = "";
	    $stat["ino"] = "";
	    $stat["mode"] = "";
	    $stat["nlink"] = "";
	    $stat["uid"] = "";
	    $stat["gid"] = "";
	    $stat["rdev"] = "";
	    $stat["size"] = $size;
	    $stat["atime"] = $mtime;
	    $stat["mtime"] = $mtime;
	    $stat["ctime"] = $mtime;
	    $stat["blksize"] = "";
	    $stat["blocks"] = "";

	    return $stat;
    }

    /**
     * Debug function for development purposes
     */
    private function debug($message, $level = OC_Log::DEBUG)
    {
        if ($this->debug) {
            OC_Log::write('files_versioning', $message, $level);
        }
    }

    /**
     * Parses a URL of the form:
     * `versioned://path/to/git/repository/.git/path/to/file#SHA-1-commit-id`
     * FIXME: Will throw an InvalidArgumentException if $path is invaid
     *
     * @param string $path The path to parse
     *
     * @return array An array containing an instance of Granite\Git\Repository,
     *               the file path, and an instance of Granite\Git\Commit
     * @throws InvalidArgumentException If the repository cannot be loaded
     */
    private function parse_url($path)
    {
        preg_match('/\/([A-Za-z0-9\/]+\.git\/)([A-Za-z0-9\/\.\/]*)(#([A-Fa-f0-9]+))*/', $path, $matches);

        // Load up the repo
        $repo = new \Granite\Git\Repository($matches[1]);
        // Parse the filename (stripping any trailing slashes)
        $repo_file = $matches[2];
        if (substr($repo_file, -1) == '/') {
            $repo_file = substr($repo_file, 0, -1);
        }

        // Default to HEAD if no commit is provided
        $repo_commit = isset($matches[4])
                       ? $matches[4]
                       : $repo->head()->sha();

        // Load the relevant commit
        $commit = $repo->factory('commit', $repo_commit);

        return array($repo, $repo_file, $commit);
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
