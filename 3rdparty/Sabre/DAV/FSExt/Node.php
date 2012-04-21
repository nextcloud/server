<?php

/**
 * Base node-class
 *
 * The node class implements the method used by both the File and the Directory classes
 *
 * @package Sabre
 * @subpackage DAV
 * @copyright Copyright (C) 2007-2012 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/)
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
abstract class Sabre_DAV_FSExt_Node extends Sabre_DAV_FS_Node implements Sabre_DAV_IProperties {

    /**
     * Updates properties on this node,
     *
     * @param array $properties
     * @see Sabre_DAV_IProperties::updateProperties
     * @return bool|array
     */
    public function updateProperties($properties) {

        $resourceData = $this->getResourceData();

        foreach($properties as $propertyName=>$propertyValue) {

            // If it was null, we need to delete the property
            if (is_null($propertyValue)) {
                if (isset($resourceData['properties'][$propertyName])) {
                    unset($resourceData['properties'][$propertyName]);
                }
            } else {
                $resourceData['properties'][$propertyName] = $propertyValue;
            }

        }

        $this->putResourceData($resourceData);
        return true;
    }

    /**
     * Returns a list of properties for this nodes.;
     *
     * The properties list is a list of propertynames the client requested, encoded as xmlnamespace#tagName, for example: http://www.example.org/namespace#author
     * If the array is empty, all properties should be returned
     *
     * @param array $properties
     * @return array
     */
    function getProperties($properties) {

        $resourceData = $this->getResourceData();

        // if the array was empty, we need to return everything
        if (!$properties) return $resourceData['properties'];

        $props = array();
        foreach($properties as $property) {
            if (isset($resourceData['properties'][$property])) $props[$property] = $resourceData['properties'][$property];
        }

        return $props;

    }

    /**
     * Returns the path to the resource file
     *
     * @return string
     */
    protected function getResourceInfoPath() {

        list($parentDir) = Sabre_DAV_URLUtil::splitPath($this->path);
        return $parentDir . '/.sabredav';

    }

    /**
     * Returns all the stored resource information
     *
     * @return array
     */
    protected function getResourceData() {

        $path = $this->getResourceInfoPath();
        if (!file_exists($path)) return array('properties' => array());

        // opening up the file, and creating a shared lock
        $handle = fopen($path,'r');
        flock($handle,LOCK_SH);
        $data = '';

        // Reading data until the eof
        while(!feof($handle)) {
            $data.=fread($handle,8192);
        }

        // We're all good
        fclose($handle);

        // Unserializing and checking if the resource file contains data for this file
        $data = unserialize($data);
        if (!isset($data[$this->getName()])) {
            return array('properties' => array());
        }

        $data = $data[$this->getName()];
        if (!isset($data['properties'])) $data['properties'] = array();
        return $data;

    }

    /**
     * Updates the resource information
     *
     * @param array $newData
     * @return void
     */
    protected function putResourceData(array $newData) {

        $path = $this->getResourceInfoPath();

        // opening up the file, and creating a shared lock
        $handle = fopen($path,'a+');
        flock($handle,LOCK_EX);
        $data = '';

        rewind($handle);

        // Reading data until the eof
        while(!feof($handle)) {
            $data.=fread($handle,8192);
        }

        // Unserializing and checking if the resource file contains data for this file
        $data = unserialize($data);
        $data[$this->getName()] = $newData;
        ftruncate($handle,0);
        rewind($handle);

        fwrite($handle,serialize($data));
        fclose($handle);

    }

    /**
     * Renames the node
     *
     * @param string $name The new name
     * @return void
     */
    public function setName($name) {

        list($parentPath, ) = Sabre_DAV_URLUtil::splitPath($this->path);
        list(, $newName) = Sabre_DAV_URLUtil::splitPath($name);
        $newPath = $parentPath . '/' . $newName;

        // We're deleting the existing resourcedata, and recreating it
        // for the new path.
        $resourceData = $this->getResourceData();
        $this->deleteResourceData();

        rename($this->path,$newPath);
        $this->path = $newPath;
        $this->putResourceData($resourceData);


    }

    /**
     * @return bool
     */
    public function deleteResourceData() {

        // When we're deleting this node, we also need to delete any resource information
        $path = $this->getResourceInfoPath();
        if (!file_exists($path)) return true;

        // opening up the file, and creating a shared lock
        $handle = fopen($path,'a+');
        flock($handle,LOCK_EX);
        $data = '';

        rewind($handle);

        // Reading data until the eof
        while(!feof($handle)) {
            $data.=fread($handle,8192);
        }

        // Unserializing and checking if the resource file contains data for this file
        $data = unserialize($data);
        if (isset($data[$this->getName()])) unset($data[$this->getName()]);
        ftruncate($handle,0);
        rewind($handle);
        fwrite($handle,serialize($data));
        fclose($handle);

        return true;
    }

    public function delete() {

        return $this->deleteResourceData();

    }

}

