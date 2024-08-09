<?php

/**
 * Prime Finite Fields
 *
 * PHP version 5 and 7
 *
 * @author    Jim Wigginton <terrafrost@php.net>
 * @copyright 2017 Jim Wigginton
 * @license   http://www.opensource.org/licenses/mit-license.html  MIT License
 */

namespace phpseclib3\Math\PrimeField;

use phpseclib3\Common\Functions\Strings;
use phpseclib3\Math\BigInteger;
use phpseclib3\Math\Common\FiniteField\Integer as Base;

/**
 * Prime Finite Fields
 *
 * @author  Jim Wigginton <terrafrost@php.net>
 */
class Integer extends Base
{
    /**
     * Holds the PrimeField's value
     *
     * @var BigInteger
     */
    protected $value;

    /**
     * Keeps track of current instance
     *
     * @var int
     */
    protected $instanceID;

    /**
     * Holds the PrimeField's modulo
     *
     * @var array<int, BigInteger>
     */
    protected static $modulo;

    /**
     * Holds a pre-generated function to perform modulo reductions
     *
     * @var array<int, callable(BigInteger):BigInteger>
     */
    protected static $reduce;

    /**
     * Zero
     *
     * @var BigInteger
     */
    protected static $zero;

    /**
     * Default constructor
     *
     * @param int $instanceID
     */
    public function __construct($instanceID, BigInteger $num = null)
    {
        $this->instanceID = $instanceID;
        if (!isset($num)) {
            $this->value = clone static::$zero[static::class];
        } else {
            $reduce = static::$reduce[$instanceID];
            $this->value = $reduce($num);
        }
    }

    /**
     * Set the modulo for a given instance
     *
     * @param int $instanceID
     * @return void
     */
    public static function setModulo($instanceID, BigInteger $modulo)
    {
        static::$modulo[$instanceID] = $modulo;
    }

    /**
     * Set the modulo for a given instance
     *
     * @param int $instanceID
     * @return void
     */
    public static function setRecurringModuloFunction($instanceID, callable $function)
    {
        static::$reduce[$instanceID] = $function;
        if (!isset(static::$zero[static::class])) {
            static::$zero[static::class] = new BigInteger();
        }
    }

    /**
     * Delete the modulo for a given instance
     */
    public static function cleanupCache($instanceID)
    {
        unset(static::$modulo[$instanceID]);
        unset(static::$reduce[$instanceID]);
    }

    /**
     * Returns the modulo
     *
     * @param int $instanceID
     * @return BigInteger
     */
    public static function getModulo($instanceID)
    {
        return static::$modulo[$instanceID];
    }

    /**
     * Tests a parameter to see if it's of the right instance
     *
     * Throws an exception if the incorrect class is being utilized
     *
     * @return void
     */
    public static function checkInstance(self $x, self $y)
    {
        if ($x->instanceID != $y->instanceID) {
            throw new \UnexpectedValueException('The instances of the two PrimeField\Integer objects do not match');
        }
    }

    /**
     * Tests the equality of two numbers.
     *
     * @return bool
     */
    public function equals(self $x)
    {
        static::checkInstance($this, $x);

        return $this->value->equals($x->value);
    }

    /**
     * Compares two numbers.
     *
     * @return int
     */
    public function compare(self $x)
    {
        static::checkInstance($this, $x);

        return $this->value->compare($x->value);
    }

    /**
     * Adds two PrimeFieldIntegers.
     *
     * @return static
     */
    public function add(self $x)
    {
        static::checkInstance($this, $x);

        $temp = new static($this->instanceID);
        $temp->value = $this->value->add($x->value);
        if ($temp->value->compare(static::$modulo[$this->instanceID]) >= 0) {
            $temp->value = $temp->value->subtract(static::$modulo[$this->instanceID]);
        }

        return $temp;
    }

    /**
     * Subtracts two PrimeFieldIntegers.
     *
     * @return static
     */
    public function subtract(self $x)
    {
        static::checkInstance($this, $x);

        $temp = new static($this->instanceID);
        $temp->value = $this->value->subtract($x->value);
        if ($temp->value->isNegative()) {
            $temp->value = $temp->value->add(static::$modulo[$this->instanceID]);
        }

        return $temp;
    }

    /**
     * Multiplies two PrimeFieldIntegers.
     *
     * @return static
     */
    public function multiply(self $x)
    {
        static::checkInstance($this, $x);

        return new static($this->instanceID, $this->value->multiply($x->value));
    }

    /**
     * Divides two PrimeFieldIntegers.
     *
     * @return static
     */
    public function divide(self $x)
    {
        static::checkInstance($this, $x);

        $denominator = $x->value->modInverse(static::$modulo[$this->instanceID]);
        return new static($this->instanceID, $this->value->multiply($denominator));
    }

    /**
     * Performs power operation on a PrimeFieldInteger.
     *
     * @return static
     */
    public function pow(BigInteger $x)
    {
        $temp = new static($this->instanceID);
        $temp->value = $this->value->powMod($x, static::$modulo[$this->instanceID]);

        return $temp;
    }

    /**
     * Calculates the square root
     *
     * @link https://en.wikipedia.org/wiki/Tonelli%E2%80%93Shanks_algorithm
     * @return static|false
     */
    public function squareRoot()
    {
        static $one, $two;
        if (!isset($one)) {
            $one = new BigInteger(1);
            $two = new BigInteger(2);
        }
        $reduce = static::$reduce[$this->instanceID];
        $p_1 = static::$modulo[$this->instanceID]->subtract($one);
        $q = clone $p_1;
        $s = BigInteger::scan1divide($q);
        list($pow) = $p_1->divide($two);
        for ($z = $one; !$z->equals(static::$modulo[$this->instanceID]); $z = $z->add($one)) {
            $temp = $z->powMod($pow, static::$modulo[$this->instanceID]);
            if ($temp->equals($p_1)) {
                break;
            }
        }

        $m = new BigInteger($s);
        $c = $z->powMod($q, static::$modulo[$this->instanceID]);
        $t = $this->value->powMod($q, static::$modulo[$this->instanceID]);
        list($temp) = $q->add($one)->divide($two);
        $r = $this->value->powMod($temp, static::$modulo[$this->instanceID]);

        while (!$t->equals($one)) {
            for ($i = clone $one; $i->compare($m) < 0; $i = $i->add($one)) {
                if ($t->powMod($two->pow($i), static::$modulo[$this->instanceID])->equals($one)) {
                    break;
                }
            }

            if ($i->compare($m) == 0) {
                return false;
            }
            $b = $c->powMod($two->pow($m->subtract($i)->subtract($one)), static::$modulo[$this->instanceID]);
            $m = $i;
            $c = $reduce($b->multiply($b));
            $t = $reduce($t->multiply($c));
            $r = $reduce($r->multiply($b));
        }

        return new static($this->instanceID, $r);
    }

    /**
     * Is Odd?
     *
     * @return bool
     */
    public function isOdd()
    {
        return $this->value->isOdd();
    }

    /**
     * Negate
     *
     * A negative number can be written as 0-12. With modulos, 0 is the same thing as the modulo
     * so 0-12 is the same thing as modulo-12
     *
     * @return static
     */
    public function negate()
    {
        return new static($this->instanceID, static::$modulo[$this->instanceID]->subtract($this->value));
    }

    /**
     * Converts an Integer to a byte string (eg. base-256).
     *
     * @return string
     */
    public function toBytes()
    {
        if (isset(static::$modulo[$this->instanceID])) {
            $length = static::$modulo[$this->instanceID]->getLengthInBytes();
            return str_pad($this->value->toBytes(), $length, "\0", STR_PAD_LEFT);
        }
        return $this->value->toBytes();
    }

    /**
     * Converts an Integer to a hex string (eg. base-16).
     *
     * @return string
     */
    public function toHex()
    {
        return Strings::bin2hex($this->toBytes());
    }

    /**
     * Converts an Integer to a bit string (eg. base-2).
     *
     * @return string
     */
    public function toBits()
    {
        // return $this->value->toBits();
        static $length;
        if (!isset($length)) {
            $length = static::$modulo[$this->instanceID]->getLength();
        }

        return str_pad($this->value->toBits(), $length, '0', STR_PAD_LEFT);
    }

    /**
     * Returns the w-ary non-adjacent form (wNAF)
     *
     * @param int $w optional
     * @return array<int, int>
     */
    public function getNAF($w = 1)
    {
        $w++;

        $mask = new BigInteger((1 << $w) - 1);
        $sub = new BigInteger(1 << $w);
        //$sub = new BigInteger(1 << ($w - 1));
        $d = $this->toBigInteger();
        $d_i = [];

        $i = 0;
        while ($d->compare(static::$zero[static::class]) > 0) {
            if ($d->isOdd()) {
                // start mods

                $bigInteger = $d->testBit($w - 1) ?
                    $d->bitwise_and($mask)->subtract($sub) :
                    //$sub->subtract($d->bitwise_and($mask)) :
                    $d->bitwise_and($mask);
                // end mods
                $d = $d->subtract($bigInteger);
                $d_i[$i] = (int) $bigInteger->toString();
            } else {
                $d_i[$i] = 0;
            }
            $shift = !$d->equals(static::$zero[static::class]) && $d->bitwise_and($mask)->equals(static::$zero[static::class]) ? $w : 1; // $w or $w + 1?
            $d = $d->bitwise_rightShift($shift);
            while (--$shift > 0) {
                $d_i[++$i] = 0;
            }
            $i++;
        }

        return $d_i;
    }

    /**
     * Converts an Integer to a BigInteger
     *
     * @return BigInteger
     */
    public function toBigInteger()
    {
        return clone $this->value;
    }

    /**
     *  __toString() magic method
     *
     * @return string
     */
    public function __toString()
    {
        return (string) $this->value;
    }

    /**
     *  __debugInfo() magic method
     *
     * @return array
     */
    public function __debugInfo()
    {
        return ['value' => $this->toHex()];
    }
}
