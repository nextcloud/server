<?php

require_once "Google/Model.php";

/**
 * Extension to the regular Google_Model that automatically
 * exposes the items array for iteration, so you can just
 * iterate over the object rather than a reference inside.
 */
class Google_Collection extends Google_Model implements Iterator, Countable
{
  protected $collection_key = 'items';

  public function rewind()
  {
    if (is_array($this->data[$this->collection_key])) {
      reset($this->data[$this->collection_key]);
    }
  }

  public function current()
  {
    $this->coerceType($this->key());
    if (is_array($this->data[$this->collection_key])) {
      return current($this->data[$this->collection_key]);
    }
  }

  public function key()
  {
    if (is_array($this->data[$this->collection_key])) {
      return key($this->data[$this->collection_key]);
    }
  }

  public function next()
  {
    return next($this->data[$this->collection_key]);
  }

  public function valid()
  {
    $key = $this->key();
    return $key !== null && $key !== false;
  }

  public function count()
  {
    return count($this->data[$this->collection_key]);
  }

  public function offsetExists ($offset)
  {
    if (!is_numeric($offset)) {
      return parent::offsetExists($offset);
    }
    return isset($this->data[$this->collection_key][$offset]);
  }

  public function offsetGet($offset)
  {
    if (!is_numeric($offset)) {
      return parent::offsetGet($offset);
    }
    $this->coerceType($offset);
    return $this->data[$this->collection_key][$offset];
  }

  public function offsetSet($offset, $value)
  {
    if (!is_numeric($offset)) {
      return parent::offsetSet($offset, $value);
    }
    $this->data[$this->collection_key][$offset] = $value;
  }

  public function offsetUnset($offset)
  {
    if (!is_numeric($offset)) {
      return parent::offsetUnset($offset);
    }
    unset($this->data[$this->collection_key][$offset]);
  }

  private function coerceType($offset)
  {
    $typeKey = $this->keyType($this->collection_key);
    if (isset($this->$typeKey) && !is_object($this->data[$this->collection_key][$offset])) {
      $type = $this->$typeKey;
      $this->data[$this->collection_key][$offset] =
          new $type($this->data[$this->collection_key][$offset]);
    }
  }
}
