<?php

namespace OC\Session;

/**
 * Non-locking session handler
 *
 * lib/private/Session/Internal.php:__construct()
 *   session_set_save_handler(new FileSessionHandler(), true)
 **/



class FileSessionHandler extends \SessionHandler
{
    private $savePath;
	private $firstCall=false;

    public function open($savePath, $sessionName)
    {
        $this->savePath = $savePath;
		return parent::open($savePath, $sessionName);
    }

    public function close()
    {
		if ($this->firstCall)
			return parent::close();

        return true;
    }

    public function read($id)
    {
		if (!file_exists("$this->savePath/sess_$id"))
			$this->firstCall=true;

		if ($this->firstCall)
			return parent::read($id);

        return (string)@file_get_contents("$this->savePath/sess_$id");
    }

    public function write($id, $data)
    {
		if ($this->firstCall)
			return parent::write($id, $data);

        return file_put_contents("$this->savePath/sess_$id", $data) === false ? false : true;
    }

    public function destroy($id)
    {
		if ($this->firstCall)
			return parent::destroy($id);

        $file = "$this->savePath/sess_$id";
        if (file_exists($file)) {
            unlink($file);
        }

        return true;
    }

    public function gc($maxlifetime)
    {
		if ($this->firstCall)
			return parent::gc($maxlifetime);

        foreach (glob("$this->savePath/sess_*") as $file) {
            if (filemtime($file) + $maxlifetime < time() && file_exists($file)) {
                unlink($file);
            }
        }

        return true;
    }
}
