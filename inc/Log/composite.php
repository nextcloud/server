<?php
/**
 * $Header: /repository/pear/Log/Log/composite.php,v 1.23 2004/08/09 06:04:11 jon Exp $
 * $Horde: horde/lib/Log/composite.php,v 1.2 2000/06/28 21:36:13 jon Exp $
 *
 * @version $Revision: 1.23 $
 * @package Log
 */

/**
 * The Log_composite:: class implements a Composite pattern which
 * allows multiple Log implementations to receive the same events.
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @author  Jon Parise <jon@php.net>
 *
 * @since Horde 1.3
 * @since Log 1.0
 * @package Log
 *
 * @example composite.php   Using the composite handler.
 */
class Log_composite extends Log
{
    /**
     * Array holding all of the Log instances to which log events should be
     * sent.
     *
     * @var array
     * @access private
     */
    var $_children = array();


    /**
     * Constructs a new composite Log object.
     *
     * @param boolean   $name       This parameter is ignored.
     * @param boolean   $ident      This parameter is ignored.
     * @param boolean   $conf       This parameter is ignored.
     * @param boolean   $level      This parameter is ignored.
     *
     * @access public
     */
    function Log_composite($name = false, $ident = false, $conf = false,
                           $level = PEAR_LOG_DEBUG)
    {
    }

    /**
     * Opens the child connections.
     *
     * @access public
     */
    function open()
    {
        if (!$this->_opened) {
            foreach ($this->_children as $id => $child) {
                $this->_children[$id]->open();
            }
            $this->_opened = true;
        }
    }

    /**
     * Closes any child instances.
     *
     * @access public
     */
    function close()
    {
        if ($this->_opened) {
            foreach ($this->_children as $id => $child) {
                $this->_children[$id]->close();
            }
            $this->_opened = false;
        }
    }

    /**
     * Flushes all open child instances.
     *
     * @access public
     * @since Log 1.8.2
     */
    function flush()
    {
        if ($this->_opened) {
            foreach ($this->_children as $id => $child) {
                $this->_children[$id]->flush();
            }
        }
    }

    /**
     * Sends $message and $priority to each child of this composite.
     *
     * @param mixed     $message    String or object containing the message
     *                              to log.
     * @param string    $priority   (optional) The priority of the message.
     *                              Valid values are: PEAR_LOG_EMERG,
     *                              PEAR_LOG_ALERT, PEAR_LOG_CRIT,
     *                              PEAR_LOG_ERR, PEAR_LOG_WARNING,
     *                              PEAR_LOG_NOTICE, PEAR_LOG_INFO, and
     *                              PEAR_LOG_DEBUG.
     *
     * @return boolean  True if the entry is successfully logged.
     *
     * @access public
     */
    function log($message, $priority = null)
    {
        /* If a priority hasn't been specified, use the default value. */
        if ($priority === null) {
            $priority = $this->_priority;
        }

        foreach ($this->_children as $id => $child) {
            $this->_children[$id]->log($message, $priority);
        }

        $this->_announce(array('priority' => $priority, 'message' => $message));

        return true;
    }

    /**
     * Returns true if this is a composite.
     *
     * @return boolean  True if this is a composite class.
     *
     * @access public
     */
    function isComposite()
    {
        return true;
    }

    /**
     * Sets this identification string for all of this composite's children.
     *
     * @param string    $ident      The new identification string.
     *
     * @access public
     * @since  Log 1.6.7
     */
    function setIdent($ident)
    {
        foreach ($this->_children as $id => $child) {
            $this->_children[$id]->setIdent($ident);
        }
    }

    /**
     * Adds a Log instance to the list of children.
     *
     * @param object    $child      The Log instance to add.
     *
     * @return boolean  True if the Log instance was successfully added.
     *
     * @access public
     */
    function addChild(&$child)
    {
        /* Make sure this is a Log instance. */
        if (!is_a($child, 'Log')) {
            return false;
        }

        $this->_children[$child->_id] = &$child;

        return true;
    }

    /**
     * Removes a Log instance from the list of children.
     *
     * @param object    $child      The Log instance to remove.
     *
     * @return boolean  True if the Log instance was successfully removed.
     *
     * @access public
     */
    function removeChild($child)
    {
        if (!is_a($child, 'Log') || !isset($this->_children[$child->_id])) {
            return false;
        }

        unset($this->_children[$child->_id]);

        return true;
    }
}

?>
