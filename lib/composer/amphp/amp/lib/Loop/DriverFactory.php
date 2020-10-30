<?php

namespace Amp\Loop;

// @codeCoverageIgnoreStart
class DriverFactory
{
    /**
     * Creates a new loop instance and chooses the best available driver.
     *
     * @return Driver
     *
     * @throws \Error If an invalid class has been specified via AMP_LOOP_DRIVER
     */
    public function create(): Driver
    {
        $driver = (function () {
            if ($driver = $this->createDriverFromEnv()) {
                return $driver;
            }

            if (UvDriver::isSupported()) {
                return new UvDriver;
            }

            if (EvDriver::isSupported()) {
                return new EvDriver;
            }

            if (EventDriver::isSupported()) {
                return new EventDriver;
            }

            return new NativeDriver;
        })();

        if (\getenv("AMP_DEBUG_TRACE_WATCHERS")) {
            return new TracingDriver($driver);
        }

        return $driver;
    }

    /**
     * @return Driver|null
     */
    private function createDriverFromEnv()
    {
        $driver = \getenv("AMP_LOOP_DRIVER");

        if (!$driver) {
            return null;
        }

        if (!\class_exists($driver)) {
            throw new \Error(\sprintf(
                "Driver '%s' does not exist.",
                $driver
            ));
        }

        if (!\is_subclass_of($driver, Driver::class)) {
            throw new \Error(\sprintf(
                "Driver '%s' is not a subclass of '%s'.",
                $driver,
                Driver::class
            ));
        }

        return new $driver;
    }
}
// @codeCoverageIgnoreEnd
