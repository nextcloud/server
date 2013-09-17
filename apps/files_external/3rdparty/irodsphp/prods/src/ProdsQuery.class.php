<?php
/**
 * ProdsQuery class.
 *
 * This class provides high level PRODS queries, which aren't specific
 * to a path, account or resource.
 * @author Sifang Lu <sifang@sdsc.edu>
 * @copyright Copyright &copy; 2007, TBD
 * @package Prods
 */
require_once("autoload.inc.php");

class ProdsQuery
{
    public $account;

    public function __construct(RODSAccount $account)
    {
        $this->account = $account;
    }

    /**
     * Get all user defined metadata names for all files on the server.
     * @return array of strings (metadata names).
     */
    public function getMetadataNamesForAllFiles()
    {
        $flds = array("COL_META_DATA_ATTR_NAME" => NULL);
        $select = new RODSGenQueSelFlds(array_keys($flds), array_values($flds));
        $condition = new RODSGenQueConds();
        $condition->add('COL_D_DATA_ID', '>=', '0');
        $conn = RODSConnManager::getConn($this->account);
        $results = $conn->query($select, $condition);
        RODSConnManager::releaseConn($conn);

        if ($results->getNumRow() < 1)
            return array();
        else {
            $values = $results->getValues();
            return $values['COL_META_DATA_ATTR_NAME'];
        }
    }

    /**
     * Get all user defined metadata names for all directories(collections) on the server.
     * @return array of strings (metadata names).
     */
    public function getMetadataNamesForAllDirs()
    {
        $flds = array("COL_META_COLL_ATTR_NAME" => NULL);
        $select = new RODSGenQueSelFlds(array_keys($flds), array_values($flds));
        $condition = new RODSGenQueConds();
        $condition->add('COL_COLL_ID', '>=', '0');
        $conn = RODSConnManager::getConn($this->account);
        $results = $conn->query($select, $condition);
        RODSConnManager::releaseConn($conn);

        if ($results->getNumRow() < 1)
            return array();
        else {
            $values = $results->getValues();
            return $values['COL_META_COLL_ATTR_NAME'];
        }
    }

    /**
     * Get all resources registered on the server
     * @return array with fields: id, name, type, zone, class, loc, info, comment, ctime, mtime, vault_path, free_space. If user not found return empty array.
     */
    public function getResources()
    {
        // set selected value
        $flds = array("COL_R_RESC_ID" => NULL, "COL_R_RESC_NAME" => NULL,
            "COL_R_ZONE_NAME" => NULL, "COL_R_TYPE_NAME" => NULL,
            "COL_R_CLASS_NAME" => NULL, "COL_R_LOC" => NULL,
            "COL_R_VAULT_PATH" => NULL, "COL_R_FREE_SPACE" => NULL,
            "COL_R_RESC_INFO" => NULL, "COL_R_RESC_COMMENT" => NULL,
            "COL_R_CREATE_TIME" => NULL, "COL_R_MODIFY_TIME" => NULL);
        $select = new RODSGenQueSelFlds(array_keys($flds), array_values($flds));
        $condition = new RODSGenQueConds();
        $conn = RODSConnManager::getConn($this->account);
        $results = $conn->query($select, $condition);
        RODSConnManager::releaseConn($conn);
        $result_vals = $results->getValues();
        $retval = array();
        for ($i = 0; $i < $results->getNumRow(); $i++) {
            $retval_row = array();
            $retval_row['id'] = $result_vals["COL_R_RESC_ID"][$i];
            $retval_row['name'] = $result_vals["COL_R_RESC_NAME"][$i];
            $retval_row['type'] = $result_vals["COL_R_TYPE_NAME"][$i];
            $retval_row['zone'] = $result_vals["COL_R_ZONE_NAME"][$i];
            $retval_row['class'] = $result_vals["COL_R_CLASS_NAME"][$i];
            $retval_row['loc'] = $result_vals["COL_R_LOC"][$i];
            $retval_row['info'] = $result_vals["COL_R_RESC_INFO"][$i];
            $retval_row['comment'] = $result_vals["COL_R_RESC_COMMENT"][$i];
            $retval_row['ctime'] = $result_vals["COL_R_CREATE_TIME"][$i];
            $retval_row['mtime'] = $result_vals["COL_R_MODIFY_TIME"][$i];
            $retval_row['vault_path'] = $result_vals["COL_R_VAULT_PATH"][$i];
            $retval_row['free_space'] = $result_vals["COL_R_FREE_SPACE"][$i];
            $retval[] = $retval_row;
        }
        return $retval;

    }
}
