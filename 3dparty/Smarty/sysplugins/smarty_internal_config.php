<?php
/**
 * Smarty Internal Plugin Config
 *
 * Main class for config variables
 *
 * @ignore
 * @package Smarty
 * @subpackage Config
 * @author Uwe Tews
 */
class Smarty_Internal_Config {
    static $config_objects = array();

    public function __construct($config_resource, $smarty, $data = null)
    {
        $this->data = $data;
        $this->smarty = $smarty;
        $this->config_resource = $config_resource;
        $this->config_resource_type = null;
        $this->config_resource_name = null;
        $this->config_filepath = null;
        $this->config_timestamp = null;
        $this->config_source = null;
        $this->compiled_config = null;
        $this->compiled_filepath = null;
        $this->compiled_timestamp = null;
        $this->mustCompile = null;
        $this->compiler_object = null;
        // parse config resource name
        if (!$this->parseConfigResourceName ($config_resource)) {
            throw new SmartyException ("Unable to parse config resource '{$config_resource}'");
        }
    }

    public function getConfigFilepath ()
    {
        return $this->config_filepath === null ?
        $this->config_filepath = $this->buildConfigFilepath() :
        $this->config_filepath;
    }

    public function getTimestamp ()
    {
        return $this->config_timestamp === null ?
        $this->config_timestamp = filemtime($this->getConfigFilepath()) :
        $this->config_timestamp;
    }

    private function parseConfigResourceName($config_resource)
    {
        if (empty($config_resource))
            return false;
        if (strpos($config_resource, ':') === false) {
            // no resource given, use default
            $this->config_resource_type = $this->smarty->default_config_type;
            $this->config_resource_name = $config_resource;
        } else {
            // get type and name from path
            list($this->config_resource_type, $this->config_resource_name) = explode(':', $config_resource, 2);
            if (strlen($this->config_resource_type) == 1) {
                // 1 char is not resource type, but part of filepath
                $this->config_resource_type = $this->smarty->default_config_type;
                $this->config_resource_name = $config_resource;
            } else {
                $this->config_resource_type = strtolower($this->config_resource_type);
            }
        }
        return true;
    }

    /*
     * get system filepath to config
     */
    public function buildConfigFilepath ()
    {
        foreach((array)$this->smarty->config_dir as $_config_dir) {
            if (strpos('/\\', substr($_config_dir, -1)) === false) {
                $_config_dir .= DS;
            }

            $_filepath = $_config_dir . $this->config_resource_name;
            if (file_exists($_filepath))
                return $_filepath;
        }
        // check for absolute path
        if (file_exists($this->config_resource_name))
            return $this->config_resource_name;
        // no tpl file found
        throw new SmartyException("Unable to load config file \"{$this->config_resource_name}\"");
        return false;
    }
    /**
     * Read config file source
     *
     * @return string content of source file
     */
    /**
     * Returns the template source code
     *
     * The template source is being read by the actual resource handler
     *
     * @return string the template source
     */
    public function getConfigSource ()
    {
        if ($this->config_source === null) {
            if ($this->readConfigSource($this) === false) {
                throw new SmartyException("Unable to load config file \"{$this->config_resource_name}\"");
            }
        }
        return $this->config_source;
    }
    public function readConfigSource()
    {
        // read source file
        if (file_exists($this->getConfigFilepath())) {
            $this->config_source = file_get_contents($this->getConfigFilepath());
            return true;
        } else {
            return false;
        }
    }

    /**
     * Returns the compiled  filepath
     *
     * @return string the compiled filepath
     */
    public function getCompiledFilepath ()
    {
        return $this->compiled_filepath === null ?
        ($this->compiled_filepath = $this->buildCompiledFilepath()) :
        $this->compiled_filepath;
    }
    public function buildCompiledFilepath()
    {
        $_compile_id = isset($this->smarty->compile_id) ? preg_replace('![^\w\|]+!', '_', $this->smarty->compile_id) : null;
        $_flag = (int)$this->smarty->config_read_hidden + (int)$this->smarty->config_booleanize * 2 +
        (int)$this->smarty->config_overwrite * 4;
        $_filepath = sha1($this->config_resource_name . $_flag);
        // if use_sub_dirs, break file into directories
        if ($this->smarty->use_sub_dirs) {
            $_filepath = substr($_filepath, 0, 2) . DS
             . substr($_filepath, 2, 2) . DS
             . substr($_filepath, 4, 2) . DS
             . $_filepath;
        }
        $_compile_dir_sep = $this->smarty->use_sub_dirs ? DS : '^';
        if (isset($_compile_id)) {
            $_filepath = $_compile_id . $_compile_dir_sep . $_filepath;
        }
        $_compile_dir = $this->smarty->compile_dir;
        if (substr($_compile_dir, -1) != DS) {
            $_compile_dir .= DS;
        }
        return $_compile_dir . $_filepath . '.' . basename($this->config_resource_name) . '.config' . '.php';
    }
    /**
     * Returns the timpestamp of the compiled file
     *
     * @return integer the file timestamp
     */
    public function getCompiledTimestamp ()
    {
        return $this->compiled_timestamp === null ?
        ($this->compiled_timestamp = (file_exists($this->getCompiledFilepath())) ? filemtime($this->getCompiledFilepath()) : false) :
        $this->compiled_timestamp;
    }
    /**
     * Returns if the current config file must be compiled
     *
     * It does compare the timestamps of config source and the compiled config and checks the force compile configuration
     *
     * @return boolean true if the file must be compiled
     */
    public function mustCompile ()
    {
        return $this->mustCompile === null ?
        $this->mustCompile = ($this->smarty->force_compile || $this->getCompiledTimestamp () === false || $this->smarty->compile_check && $this->getCompiledTimestamp () < $this->getTimestamp ()):
        $this->mustCompile;
    }
    /**
     * Returns the compiled config file
     *
     * It checks if the config file must be compiled or just read the compiled version
     *
     * @return string the compiled config file
     */
    public function getCompiledConfig ()
    {
        if ($this->compiled_config === null) {
            // see if template needs compiling.
            if ($this->mustCompile()) {
                $this->compileConfigSource();
            } else {
                $this->compiled_config = file_get_contents($this->getCompiledFilepath());
            }
        }
        return $this->compiled_config;
    }

    /**
     * Compiles the config files
     */
    public function compileConfigSource ()
    {
        // compile template
        if (!is_object($this->compiler_object)) {
            // load compiler
            $this->compiler_object = new Smarty_Internal_Config_File_Compiler($this->smarty);
        }
        // compile locking
        if ($this->smarty->compile_locking) {
            if ($saved_timestamp = $this->getCompiledTimestamp()) {
                touch($this->getCompiledFilepath());
            }
        }
        // call compiler
        try {
            $this->compiler_object->compileSource($this);
        }
        catch (Exception $e) {
            // restore old timestamp in case of error
            if ($this->smarty->compile_locking && $saved_timestamp) {
                touch($this->getCompiledFilepath(), $saved_timestamp);
            }
            throw $e;
        }
        // compiling succeded
        // write compiled template
        Smarty_Internal_Write_File::writeFile($this->getCompiledFilepath(), $this->getCompiledConfig(), $this->smarty);
    }

    /*
     * load config variables
    *
    * @param mixed $sections array of section names, single section or null
    * @param object $scope global,parent or local
    */
    public function loadConfigVars ($sections = null, $scope = 'local')
    {
        if ($this->data instanceof Smarty_Internal_Template) {
            $this->data->properties['file_dependency'][sha1($this->getConfigFilepath())] = array($this->getConfigFilepath(), $this->getTimestamp(),'file');
        }
        if ($this->mustCompile()) {
            $this->compileConfigSource();
        }
        // pointer to scope
        if ($scope == 'local') {
        	$scope_ptr = $this->data;
        } elseif ($scope == 'parent') {
        	if (isset($this->data->parent)) {
        		$scope_ptr = $this->data->parent;
        	} else {
        		$scope_ptr = $this->data;
        	}
        } elseif ($scope == 'root' || $scope == 'global') {
        	$scope_ptr = $this->data;
        	while (isset($scope_ptr->parent)) {
        		$scope_ptr = $scope_ptr->parent;
        	}
        }
        $_config_vars = array();
        include($this->getCompiledFilepath ());
        // copy global config vars
        foreach ($_config_vars['vars'] as $variable => $value) {
            if ($this->smarty->config_overwrite || !isset($scope_ptr->config_vars[$variable])) {
                $scope_ptr->config_vars[$variable] = $value;
            } else {
                $scope_ptr->config_vars[$variable] = array_merge((array)$scope_ptr->config_vars[$variable], (array)$value);
            }
        }
        // scan sections
        foreach ($_config_vars['sections'] as $this_section => $dummy) {
            if ($sections == null || in_array($this_section, (array)$sections)) {
                foreach ($_config_vars['sections'][$this_section]['vars'] as $variable => $value) {
                    if ($this->smarty->config_overwrite || !isset($scope_ptr->config_vars[$variable])) {
                        $scope_ptr->config_vars[$variable] = $value;
                    } else {
                        $scope_ptr->config_vars[$variable] = array_merge((array)$scope_ptr->config_vars[$variable], (array)$value);
                    }
                }
            }
        }
    }
}
?>