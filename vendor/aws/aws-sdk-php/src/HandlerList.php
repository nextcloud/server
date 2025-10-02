<?php
namespace Aws;

/**
 * Builds a single handler function from zero or more middleware functions and
 * a handler. The handler function is then used to send command objects and
 * return a promise that is resolved with an AWS result object.
 *
 * The "front" of the list is invoked before the "end" of the list. You can add
 * middleware to the front of the list using one of the "prepend" method, and
 * the end of the list using one of the "append" method. The last function
 * invoked in a handler list is the handler (a function that does not accept a
 * next handler but rather is responsible for returning a promise that is
 * fulfilled with an Aws\ResultInterface object).
 *
 * Handlers are ordered using a "step" that describes the step at which the
 * SDK is when sending a command. The available steps are:
 *
 * - init: The command is being initialized, allowing you to do things like add
 *   default options.
 * - validate: The command is being validated before it is serialized
 * - build: The command is being serialized into an HTTP request. A middleware
 *   in this step MUST serialize an HTTP request and populate the "@request"
 *   parameter of a command with the request such that it is available to
 *   subsequent middleware.
 * - sign: The request is being signed and prepared to be sent over the wire.
 *
 * Middleware can be registered with a name to allow you to easily add a
 * middleware before or after another middleware by name. This also allows you
 * to remove a middleware by name (in addition to removing by instance).
 */
class HandlerList implements \Countable
{
    const INIT = 'init';
    const VALIDATE = 'validate';
    const BUILD = 'build';
    const SIGN = 'sign';
    const ATTEMPT = 'attempt';

    /** @var callable */
    private $handler;

    /** @var array */
    private $named = [];

    /** @var array */
    private $sorted;

    /** @var callable|null */
    private $interposeFn;

    /** @var array Steps (in reverse order) */
    private $steps = [
        self::ATTEMPT  => [],
        self::SIGN     => [],
        self::BUILD    => [],
        self::VALIDATE => [],
        self::INIT     => [],
    ];

    /**
     * @param callable $handler HTTP handler.
     */
    public function __construct(?callable $handler = null)
    {
        $this->handler = $handler;
    }

    /**
     * Dumps a string representation of the list.
     *
     * @return string
     */
    public function __toString()
    {
        $str = '';
        $i = 0;

        foreach (array_reverse($this->steps) as $k => $step) {
            foreach (array_reverse($step) as $j => $tuple) {
                $str .= "{$i}) Step: {$k}, ";
                if ($tuple[1]) {
                    $str .= "Name: {$tuple[1]}, ";
                }
                $str .= "Function: " . $this->debugCallable($tuple[0]) . "\n";
                $i++;
            }
        }

        if ($this->handler) {
            $str .= "{$i}) Handler: " . $this->debugCallable($this->handler) . "\n";
        }

        return $str;
    }

    /**
     * Set the HTTP handler that actually returns a response.
     *
     * @param callable $handler Function that accepts a request and array of
     *                          options and returns a Promise.
     */
    public function setHandler(callable $handler)
    {
        $this->handler = $handler;
    }

    /**
     * Returns true if the builder has a handler.
     *
     * @return bool
     */
    public function hasHandler()
    {
        return (bool) $this->handler;
    }

    /**
     * Checks if a middleware exists. The middleware
     * should have been added with a name in order to
     * use this method.
     *
     * @param string $name
     *
     * @return bool
     */
    public function hasMiddleware(string $name): bool
    {
        return isset($this->named[$name]);
    }

    /**
     * Append a middleware to the init step.
     *
     * @param callable $middleware Middleware function to add.
     * @param string   $name       Name of the middleware.
     */
    public function appendInit(callable $middleware, $name = null)
    {
        $this->add(self::INIT, $name, $middleware);
    }

    /**
     * Prepend a middleware to the init step.
     *
     * @param callable $middleware Middleware function to add.
     * @param string   $name       Name of the middleware.
     */
    public function prependInit(callable $middleware, $name = null)
    {
        $this->add(self::INIT, $name, $middleware, true);
    }

    /**
     * Append a middleware to the validate step.
     *
     * @param callable $middleware Middleware function to add.
     * @param string   $name       Name of the middleware.
     */
    public function appendValidate(callable $middleware, $name = null)
    {
        $this->add(self::VALIDATE, $name, $middleware);
    }

    /**
     * Prepend a middleware to the validate step.
     *
     * @param callable $middleware Middleware function to add.
     * @param string   $name       Name of the middleware.
     */
    public function prependValidate(callable $middleware, $name = null)
    {
        $this->add(self::VALIDATE, $name, $middleware, true);
    }

    /**
     * Append a middleware to the build step.
     *
     * @param callable $middleware Middleware function to add.
     * @param string   $name       Name of the middleware.
     */
    public function appendBuild(callable $middleware, $name = null)
    {
        $this->add(self::BUILD, $name, $middleware);
    }

    /**
     * Prepend a middleware to the build step.
     *
     * @param callable $middleware Middleware function to add.
     * @param string   $name       Name of the middleware.
     */
    public function prependBuild(callable $middleware, $name = null)
    {
        $this->add(self::BUILD, $name, $middleware, true);
    }

    /**
     * Append a middleware to the sign step.
     *
     * @param callable $middleware Middleware function to add.
     * @param string   $name       Name of the middleware.
     */
    public function appendSign(callable $middleware, $name = null)
    {
        $this->add(self::SIGN, $name, $middleware);
    }

    /**
     * Prepend a middleware to the sign step.
     *
     * @param callable $middleware Middleware function to add.
     * @param string   $name       Name of the middleware.
     */
    public function prependSign(callable $middleware, $name = null)
    {
        $this->add(self::SIGN, $name, $middleware, true);
    }

    /**
     * Append a middleware to the attempt step.
     *
     * @param callable $middleware Middleware function to add.
     * @param string   $name       Name of the middleware.
     */
    public function appendAttempt(callable $middleware, $name = null)
    {
        $this->add(self::ATTEMPT, $name, $middleware);
    }

    /**
     * Prepend a middleware to the attempt step.
     *
     * @param callable $middleware Middleware function to add.
     * @param string   $name       Name of the middleware.
     */
    public function prependAttempt(callable $middleware, $name = null)
    {
        $this->add(self::ATTEMPT, $name, $middleware, true);
    }

    /**
     * Add a middleware before the given middleware by name.
     *
     * @param string|callable $findName   Add before this
     * @param string          $withName   Optional name to give the middleware
     * @param callable        $middleware Middleware to add.
     */
    public function before($findName, $withName, callable $middleware)
    {
        $this->splice($findName, $withName, $middleware, true);
    }

    /**
     * Add a middleware after the given middleware by name.
     *
     * @param string|callable $findName   Add after this
     * @param string          $withName   Optional name to give the middleware
     * @param callable        $middleware Middleware to add.
     */
    public function after($findName, $withName, callable $middleware)
    {
        $this->splice($findName, $withName, $middleware, false);
    }

    /**
     * Remove a middleware by name or by instance from the list.
     *
     * @param string|callable $nameOrInstance Middleware to remove.
     */
    public function remove($nameOrInstance)
    {
        if (is_callable($nameOrInstance)) {
            $this->removeByInstance($nameOrInstance);
        } elseif (is_string($nameOrInstance)) {
            $this->removeByName($nameOrInstance);
        }
    }

    /**
     * Interpose a function between each middleware (e.g., allowing for a trace
     * through the middleware layers).
     *
     * The interpose function is a function that accepts a "step" argument as a
     * string and a "name" argument string. This function must then return a
     * function that accepts the next handler in the list. This function must
     * then return a function that accepts a CommandInterface and optional
     * RequestInterface and returns a promise that is fulfilled with an
     * Aws\ResultInterface or rejected with an Aws\Exception\AwsException
     * object.
     *
     * @param callable|null $fn Pass null to remove any previously set function
     */
    public function interpose(?callable $fn = null)
    {
        $this->sorted = null;
        $this->interposeFn = $fn;
    }

    /**
     * Compose the middleware and handler into a single callable function.
     *
     * @return callable
     */
    public function resolve()
    {
        if (!($prev = $this->handler)) {
            throw new \LogicException('No handler has been specified');
        }

        if ($this->sorted === null) {
            $this->sortMiddleware();
        }

        foreach ($this->sorted as $fn) {
            $prev = $fn($prev);
        }

        return $prev;
    }

    /**
     * @return int
     */
    #[\ReturnTypeWillChange]
    public function count()
    {
        return count($this->steps[self::INIT])
            + count($this->steps[self::VALIDATE])
            + count($this->steps[self::BUILD])
            + count($this->steps[self::SIGN])
            + count($this->steps[self::ATTEMPT]);
    }

    /**
     * Splices a function into the middleware list at a specific position.
     *
     * @param          $findName
     * @param          $withName
     * @param callable $middleware
     * @param          $before
     */
    private function splice($findName, $withName, callable $middleware, $before)
    {
        if (!isset($this->named[$findName])) {
            throw new \InvalidArgumentException("$findName not found");
        }

        $idx = $this->sorted = null;
        $step = $this->named[$findName];

        if ($withName) {
            $this->named[$withName] = $step;
        }

        foreach ($this->steps[$step] as $i => $tuple) {
            if ($tuple[1] === $findName) {
                $idx = $i;
                break;
            }
        }

        $replacement = $before
            ? [$this->steps[$step][$idx], [$middleware, $withName]]
            : [[$middleware, $withName], $this->steps[$step][$idx]];
        array_splice($this->steps[$step], $idx, 1, $replacement);
    }

    /**
     * Provides a debug string for a given callable.
     *
     * @param array|callable $fn Function to write as a string.
     *
     * @return string
     */
    private function debugCallable($fn)
    {
        if (is_string($fn)) {
            return "callable({$fn})";
        }

        if (is_array($fn)) {
            $ele = is_string($fn[0]) ? $fn[0] : get_class($fn[0]);
            return "callable(['{$ele}', '{$fn[1]}'])";
        }

        return 'callable(' . spl_object_hash($fn) . ')';
    }

    /**
     * Sort the middleware, and interpose if needed in the sorted list.
     */
    private function sortMiddleware()
    {
        $this->sorted = [];

        if (!$this->interposeFn) {
            foreach ($this->steps as $step) {
                foreach ($step as $fn) {
                    $this->sorted[] = $fn[0];
                }
            }
            return;
        }

        $ifn = $this->interposeFn;
        // Interpose the interposeFn into the handler stack.
        foreach ($this->steps as $stepName => $step) {
            foreach ($step as $fn) {
                $this->sorted[] = $ifn($stepName, $fn[1]);
                $this->sorted[] = $fn[0];
            }
        }
    }

    private function removeByName($name)
    {
        if (!isset($this->named[$name])) {
            return;
        }

        $this->sorted = null;
        $step = $this->named[$name];
        $this->steps[$step] = array_values(
            array_filter(
                $this->steps[$step],
                function ($tuple) use ($name) {
                    return $tuple[1] !== $name;
                }
            )
        );
    }

    private function removeByInstance(callable $fn)
    {
        foreach ($this->steps as $k => $step) {
            foreach ($step as $j => $tuple) {
                if ($tuple[0] === $fn) {
                    $this->sorted = null;
                    unset($this->named[$this->steps[$k][$j][1]]);
                    unset($this->steps[$k][$j]);
                }
            }
        }
    }

    /**
     * Add a middleware to a step.
     *
     * @param string   $step       Middleware step.
     * @param string   $name       Middleware name.
     * @param callable $middleware Middleware function to add.
     * @param bool     $prepend    Prepend instead of append.
     */
    private function add($step, $name, callable $middleware, $prepend = false)
    {
        $this->sorted = null;

        if ($prepend) {
            $this->steps[$step][] = [$middleware, $name];
        } else {
            array_unshift($this->steps[$step], [$middleware, $name]);
        }

        if ($name) {
            $this->named[$name] = $step;
        }
    }
}
