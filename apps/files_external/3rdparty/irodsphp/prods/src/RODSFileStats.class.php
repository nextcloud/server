<?php

class RODSFileStats
{
    public $name;
    public $size;
    public $owner;
    public $ownerzone;
    public $mtime;
    public $ctime;
    public $id;
    public $typename;
    public $rescname;
    public $comments;
    public $num_replica;

    public function __construct($name, $size, $owner, $ownerzone, $mtime, $ctime, $id, $typename,
                                $rescname, $comments, $num_replica = null)
    {
        $this->name = $name;
        $this->size = $size;
        $this->owner = $owner;
        $this->ownerzone = $ownerzone;
        $this->mtime = $mtime;
        $this->ctime = $ctime;
        $this->id = $id;
        $this->typename = $typename;
        $this->rescname = $rescname;
        $this->comments = $comments;
        $this->num_replica = $num_replica;
    }

}  
     