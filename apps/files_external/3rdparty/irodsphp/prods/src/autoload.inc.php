<?php
// change this, if this code isn't "higher" than ALL classfiles
define("CLASS_DIR", dirname(__FILE__));

/**
 * autoload classes (no need to include them one by one)
 *
 * @uses classFolder()
 * @param $className string
 */
function __autoload($className)
{
    $folder = classFolder($className);

    if ($folder)
        require_once($folder . $className . ".class.php");
}

/**
 * search for folders and subfolders with classes
 *
 * @param $className string
 * @param $sub string[optional]
 * @return string
 */
function classFolder($className, $sub = "/")
{
    $dir = dir(CLASS_DIR . $sub);

    if (file_exists(CLASS_DIR . $sub . $className . ".class.php"))
        return CLASS_DIR . $sub;

    while (false !== ($folder = $dir->read())) {
        if ($folder != "." && $folder != "..") {
            if (is_dir(CLASS_DIR . $sub . $folder)) {
                $subFolder = classFolder($className, $sub . $folder . "/");

                if ($subFolder)
                    return $subFolder;
            }
        }
    }
    $dir->close();
    return false;
}

spl_autoload_register('__autoload');
