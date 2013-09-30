<?php
require_once("autoload.inc.php");
class RODSGenQueSelFlds
{
    private $names;
    private $indexes;
    private $attrs;

    /**
     * default constructor.
     * @param string $name name of the field, which must be one defined in file 'RodsGenQueryNum.inc.php'.
     * @param string $attrs extra options used for operations such as order_by, the expected values are:
     * - 'order_by_asc' order the result by this field, in ASCENDING order
     * - 'order_by_desc' order the result by this field, in DESCENDING order
     * - min minimum of the group
     * - max maximum of the group
     * - sum sum of the group
     * - avg average of the group
     * - count count of the group
     */
    public function __construct(array $names = array(), array $attrs = array())
    {
        require_once("RodsGenQueryNum.inc.php"); //load magic numbers

        $this->names = $names;
        $this->attrs = array();
        $this->indexes = array();

        for ($i = 0; $i < count($names); $i++) {
            $name = $names[$i];
            if (!isset($GLOBALS['PRODS_GENQUE_NUMS']["$name"])) {
                throw new RODSException("General Query select field name '$name' is not valid",
                    'PERR_USER_INPUT_ERROR');
            }
            $this->indexes[] = $GLOBALS['PRODS_GENQUE_NUMS']["$name"];
            $this->attrs[] = RODSGenQueSelFlds::attr2GenQueNumber($attrs[$i]);
        }

    }

    /**
     * Add a single select field.
     *
     * @param string name name of the field, which must be one defined in file 'RodsGenQueryNum.inc.php'.
     */
    public function add($name, $attr = NULL)
    {
        require_once("RodsGenQueryNum.inc.php"); //load magic numbers
        if (!isset($GLOBALS['PRODS_GENQUE_NUMS']["$name"])) {
            throw new RODSException("General Query select field name '$name' is not valid",
                'PERR_USER_INPUT_ERROR');
        }
        $this->indexes[] = $GLOBALS['PRODS_GENQUE_NUMS']["$name"];
        $this->names[] = $name;
        $this->attrs[] = RODSGenQueSelFlds::attr2GenQueNumber($attr);
    }

    /**
     * update a single select field's attr/value. Note that if the value already exists,
     * it will OR the bits. This is used when you want more than one type of operation
     * for a select field, such as select_max and sort.
     */
    public function update($name, $attr)
    {
        require_once("RodsGenQueryNum.inc.php"); //load magic numbers
        if (!isset($GLOBALS['PRODS_GENQUE_NUMS']["$name"])) {
            throw new RODSException("General Query select field name '$name' is not valid",
                'PERR_USER_INPUT_ERROR');
        }

        $newattr = RODSGenQueSelFlds::attr2GenQueNumber($attr);
        for ($i = 0; $i < count($this->names); $i++) {
            if ($this->names[$i] == $name) {
                if ($this->attrs[$i] == 1)
                    $this->attrs[$i] = $newattr;
                else
                    $this->attrs[$i] = $newattr | $this->attrs[$i];
                return;
            }
        }
        $this->add($name, $attr);
    }

    /**
     * Convert supported attribute to magic number, that iRODS protocol uses
     * Following attributes are supported:
     * - 'order_by_asc' order the result by this field, in ASCENDING order
     * - 'order_by_desc' order the result by this field, in DESCENDING order
     * - min minimum of the group
     * - max maximum of the group
     * - sum sum of the group
     * - avg average of the group
     * - count count of the group
     */
    public static function attr2GenQueNumber($attr)
    {
        if (empty($attr)) return 1;
        $retval = 1;
        switch ($attr) {
            case 'order_by_asc':
                $retval = $GLOBALS['PRODS_GENQUE_NUMS']['ORDER_BY'];
                break;
            case 'order_by_desc':
                $retval = $GLOBALS['PRODS_GENQUE_NUMS']['ORDER_BY_DESC'];
                break;
            case 'min':
                $retval = $GLOBALS['PRODS_GENQUE_NUMS']['SELECT_MIN'];
                break;
            case 'max':
                $retval = $GLOBALS['PRODS_GENQUE_NUMS']['SELECT_MAX'];
                break;
            case 'sum':
                $retval = $GLOBALS['PRODS_GENQUE_NUMS']['SELECT_SUM'];
                break;
            case 'avg':
                $retval = $GLOBALS['PRODS_GENQUE_NUMS']['SELECT_AVG'];
                break;
            case 'count':
                $retval = $GLOBALS['PRODS_GENQUE_NUMS']['SELECT_COUNT'];
                break;
            default:
                throw new RODSException("Unexpected attribute: '$attr'",
                    'PERR_USER_INPUT_ERROR');
        }
        return intval($retval);
    }

    /**
     * make a RP_InxIvalPair, a low level iRODS packet
     */
    public function packetize()
    {
        return (new RP_InxIvalPair(count($this->names), $this->indexes,
            $this->attrs));

    }

    public function getIndexes()
    {
        return $this->indexes;
    }

    public function getAttrs()
    {
        return $this->attrs;
    }

    public function getCount()
    {
        return count($this->names);
    }

    public function getNames()
    {
        return $this->names;
    }

}
