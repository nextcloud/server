<?php

/**
 * Base node-class 
 *
 * The node class implements the method used by both the File and the Directory classes 
 * 
 * @package Sabre
 * @subpackage DAV
 * @copyright Copyright (C) 2007-2011 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/) 
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
abstract class Sabre_DAV_FSExt_Node extends Sabre_DAV_FS_Node implements Sabre_DAV_ILockable, Sabre_DAV_IProperties {

    /**
     * Returns all the locks on this node
     * 
     * @return array 
     */
    function getLocks() {

        $resourceData = $this->getResourceData();
        $locks = $resourceData['locks'];
        foreach($locks as $k=>$lock) {
            if (time() > $lock->timeout + $lock->created) unset($locks[$k]); 
        }
        return $locks;

    }

    /**
     * Locks this node 
     * 
     * @param Sabre_DAV_Locks_LockInfo $lockInfo 
     * @return void
     */
    function lock(Sabre_DAV_Locks_LockInfo $lockInfo) {

        // We're making the lock timeout 30 minutes
        $lockInfo->timeout = 1800;
        $lockInfo->created = time();

        $resourceData = $this->getResourceData();
        if (!isset($resourceData['locks'])) $resourceData['locks'] = array();
        $current = null;
        foreach($resourceData['locks'] as $k=>$lock) {
            if ($lock->token === $lockInfo->token) $current = $k;
        }
        if (!is_null($current)) $resourceData['locks'][$current] = $lockInfo;
        else $resourceData['locks'][] = $lockInfo;

        $this->putResourceData($resourceData);

    }

    /**
     * Removes a lock from this node
     * 
     * @param Sabre_DAV_Locks_LockInfo $lockInfo 
     * @return bool 
     */
    function unlock(Sabre_DAV_Locks_LockInfo $lockInfo) {

        //throw new Sabre_DAV_Exception('bla');
        $resourceData = $this->getResourceData();
        foreach($resourceData['locks'] as $k=>$lock) {

            if ($lock->token === $lockInfo->token) {

                unset($resourceData['locks'][$k]);
                $this->putResourceData($resourceData);
                return true;

            }
        }
        return false;

    }

    /**
     * Updates properties on this node,
     *
     * @param array $mutations
     * @see Sabre_DAV_IProperties::updateProperties
     * @return bool|array 
     */
    public function updateProperties($properties) {

        $resourceData = $this->getResourceData();
        
        $result = array();

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
     * @return void
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
        if (!file_exists($path)) return array('locks'=>array(), 'properties' => array());

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
            return array('locks'=>array(), 'properties' => array());
        }

        $data = $data[$this->getName()];
        if (!isset($data['locks'])) $data['locks'] = array();
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

    }

    public function delete() {

        return $this->deleteResourceData();

    }

}

