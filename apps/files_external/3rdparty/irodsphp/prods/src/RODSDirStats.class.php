<?php

class RODSDirStats
{
    public $name;
    public $owner;
    public $ownerzone;
    public $mtime;
    public $ctime;
    public $id;
    public $comments;

    public function __construct($name, $owner, $ownerzone, $mtime, $ctime, $id, $comments)
    {
        $this->name = $name;
        $this->owner = $owner;
        $this->ownerzone = $ownerzone;
        $this->mtime = $mtime;
        $this->ctime = $ctime;
        $this->id = $id;
        $this->comments = $comments;
    }

}  
     