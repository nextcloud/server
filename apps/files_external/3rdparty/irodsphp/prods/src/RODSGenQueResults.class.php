<?php
require_once("autoload.inc.php");
class RODSGenQueResults
{
    private $total_count;
    private $values;
    private $numcol;
    private $numrow;

    /**
     * default constructor.
     * @param integer $total_count total count of all potential results.
     * @param array $result_array an associative array of the values. Each key is the return field name, and each array element is an array of values of the query.
     */
    public function __construct($total_count = 0, array $result_array = array())
    {
        $this->total_count = $total_count;
        $this->values = $result_array;
        $this->numcol = count($result_array);
        if ($this->numcol > 0)
            $this->numrow = count(current($result_array));
        else
            $this->numrow = 0;
    }

    /**
     * Add general query result packet RP_GenQueryOut, directly from the protocol level query,  into the result structure.
     * @param RP_GenQueryOut $genque_result_pk result packet directly from the protocol level query.
     * @return number of rows just added
     */
    public function addResults(RP_GenQueryOut $genque_result_pk)
    {
        if ($genque_result_pk->totalRowCount > $this->total_count)
            $this->total_count = $genque_result_pk->totalRowCount;

        require_once("RodsGenQueryNum.inc.php"); //load magic numbers

        $num_row_added = 0;
        for ($i = 0; $i < $genque_result_pk->attriCnt; $i++) {
            $sql_res_pk = $genque_result_pk->SqlResult_PI[$i];
            $attri_name = $GLOBALS['PRODS_GENQUE_NUMS_REV'][$sql_res_pk->attriInx];
            if (empty($this->values["$attri_name"]))
                $this->values["$attri_name"] = $sql_res_pk->value;
            else
                array_splice($this->values["$attri_name"],
                    count($this->values["$attri_name"]), 0, $sql_res_pk->value);
            if ($i == 0) {
                $num_row_added = count($sql_res_pk->value);
                if ($num_row_added != (int)$genque_result_pk->rowCnt) {
                    throw new RODSException("Gen Query result packet num row mismatch. Expect: $genque_result_pk->rowCnt, got: $num_row_added",
                        'PERR_UNEXPECTED_PACKET_FORMAT');
                }
            }
        }

        $this->numcol = count($this->values);
        if ($this->numcol > 0)
            $this->numrow = count(current($this->values));
        else
            $this->numrow = 0;

        return $num_row_added;
    }

    /**
     * get result values in (2-d) array, each array key is the name
     * used RODSGenQueSelFlds, such as COL_COLL_NAME
     */
    public function getValues()
    {
        return $this->values;
    }

    /**
     * get total result count, including all the potential results not returned.
     */
    public function getTotalCount()
    {
        return $this->total_count;
    }

    /**
     * get number of columns/fields of the results.
     */
    public function getNumCol()
    {
        return $this->numcol;
    }

    /**
     * get number of rows of the results.
     */
    public function getNumRow()
    {
        return $this->numrow;
    }
}
