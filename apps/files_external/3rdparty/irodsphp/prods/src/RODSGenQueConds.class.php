<?php
require_once("autoload.inc.php");
class RODSGenQueConds
{
    private $cond;
    private $cond_kw;

    /**
     * default constructor. It take names, ops, and vals.
     * suppose name='foo' op='>=' and val='0', then the triplex means
     * "foo >= 0" as one iRODS general query condition.
     * @param array (of string) $names names of the field, which must be one defined in file 'RodsGenQueryNum.inc.php'.
     * @param array (of string) $ops logical operator, such as '=' 'like' '>'
     * @param array (of string) $vals value of the filed
     */
    public function __construct(array $names = array(), array $ops = array(),
                                array $vals = array())
    {
        require_once("RodsGenQueryNum.inc.php"); //load magic numbers
        require_once("RodsGenQueryKeyWd.inc.php"); //load magic keywords

        $this->cond = array('names' => array(), 'sysnames' => array(), 'values' => array());
        $this->cond_kw = array('names' => array(), 'sysnames' => array(), 'values' => array());

        for ($i = 0; $i < count($names); $i++) {
            $name = $names[$i];
            $op = $ops[$i];
            $val = $vals[$i];
            if (isset($GLOBALS['PRODS_GENQUE_NUMS']["$name"])) {
                $this->cond['names'][] = $name;
                $this->cond['sysnames'][] = $GLOBALS['PRODS_GENQUE_NUMS']["$name"];
                $this->cond['values'][] = "$op '$val'";
            } else
                if (isset($GLOBALS['PRODS_GENQUE_KEYWD']["$name"])) {
                    $this->cond_kw['names'][] = $name;
                    $this->cond_kw['sysnames'][] = $GLOBALS['PRODS_GENQUE_KEYWD']["$name"];
                    $this->cond_kw['values'][] = "$op '$val'";
                } else {
                    throw new RODSException("General Query condition field name '$name' is not valid",
                        'PERR_USER_INPUT_ERROR');
                }
        }
    }

    /**
     * Add a single select field.
     * @param string $name names of the field, which must be one defined in file 'RodsGenQueryNum.inc.php'.
     * @param string $op logical operator, such as '=' 'like' '>'
     * @param string $val value of the filed
     * @param array  an array of tuples of extra op's and val's, each tuple is an assosive array that has key 'op' and 'val'. These conditions will be 'OR' with the other conditions.
     * for example add ('COL_D_DATA_ID','like', '/tempZone/home/rods/%', array(array('op'=>'=','val'=>'/tempZone/home/rods'")))
     * would select all file ids both in subdirectories under '/tempZone/home/rods' and directly under '/tempZone/home/rods'
     */
    public function add($name, $op, $val, array $OR_ops_vals = array())
    {
        require_once("RodsGenQueryNum.inc.php"); //load magic numbers
        require_once("RodsGenQueryKeyWd.inc.php"); //load magic keywords

        if (isset($GLOBALS['PRODS_GENQUE_NUMS']["$name"])) {
            $this->cond['names'][] = $name;
            $this->cond['sysnames'][] = $GLOBALS['PRODS_GENQUE_NUMS']["$name"];
            $value = "$op '$val'";
            foreach ($OR_ops_vals as $op_val) {
                $or_op = $op_val['op'];
                $or_val = $op_val['val'];
                if (empty($or_op) || empty($or_val))
                    continue;
                $value = $value . " || $or_op '$or_val'";
            }
            $this->cond['values'][] = $value;
        } else
            if (isset($GLOBALS['PRODS_GENQUE_KEYWD']["$name"])) {
                $this->cond_kw['names'][] = $name;
                $this->cond_kw['sysnames'][] = $GLOBALS['PRODS_GENQUE_KEYWD']["$name"];
                $value = "$op '$val'";
                foreach ($OR_ops_vals as $op_val) {
                    $or_op = $op_val['op'];
                    $or_val = $op_val['val'];
                    if (empty($or_op) || empty($or_val))
                        continue;
                    $value = $value . " || $or_op '$or_val'";
                }
                $this->cond_kw['values'][] = $value;
            } else {
                throw new RODSException("General Query condition field name '$name' is not valid",
                    'PERR_USER_INPUT_ERROR');
            }
    }

    /**
     * make a RP_InxValPair.
     */
    public function packetize()
    {
        return (new RP_InxValPair(count($this->cond['names']),
            $this->cond['sysnames'], $this->cond['values']));
    }

    /**
     * make a RP_KeyValPair.
     */
    public function packetizeKW()
    {
        return (new RP_KeyValPair(count($this->cond_kw['names']),
            $this->cond_kw['sysnames'], $this->cond_kw['values']));
    }

    public function getCond()
    {
        return $this->cond;
    }
}
