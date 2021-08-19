<?php

namespace OpenStack\Integration;

class Runner
{
    private $testsDir;
    private $samplesDir;
    private $logger;
    private $tests;
    private $namespace;

    public function __construct($samplesDir, $testsDir, $testNamespace)
    {
        $this->samplesDir = $samplesDir;
        $this->testsDir = $testsDir;
        $this->namespace = $testNamespace;

        $this->logger = new DefaultLogger();
        $this->assembleTestFiles();
    }

    private function traverse(string $path): \DirectoryIterator
    {
        return new \DirectoryIterator($path);
    }

    private function assembleTestFiles()
    {
        foreach ($this->traverse($this->testsDir) as $servicePath) {
            if ($servicePath->isDir()) {
                $serviceBn = $servicePath->getBasename();
                foreach ($this->traverse($servicePath->getPathname()) as $versionPath) {
                    $versionBn = $versionPath->getBasename();
                    if ($servicePath->isDir() && $versionBn[0] == 'v') {
                        foreach ($this->traverse($versionPath->getPathname()) as $testPath) {
                            if (strpos($testPath->getFilename(), 'Test.php')) {
                                $testBn = strtolower(substr($testPath->getBasename(), 0, -8));
                                $this->tests[strtolower($serviceBn)][strtolower($versionBn)][] = $testBn;
                            }
                        }
                    }
                }
            }
        }
    }

    private function getOpts()
    {
        $opts = getopt('s:v:m:t:', ['service:', 'version:', 'module::', 'test::', 'debug::', 'help::']);

        $getOpt = function (array $keys, $default) use ($opts) {
            $value = $default;
            foreach ($keys as $key) {
                if (isset($opts[$key])) {
                    $value = $opts[$key];
                    break;
                }
            }
            return strtolower($value);
        };

        return [
            $getOpt(['s', 'service'], 'all'),
            $getOpt(['v', 'version'], 'all'),
            $getOpt(['m', 'module'], 'core'),
            $getOpt(['t', 'test'], ''),
            isset($opts['debug']) ? (int)$opts['debug'] : 0,
        ];
    }

    private function getRunnableServices($service, $version, $module)
    {
        $tests = $this->tests;

        if ($service != 'all') {
            if (!isset($tests[$service])) {
                $this->logger->critical(sprintf("%s is not a valid service", $service));
                exit(1);
            }

            $serviceArray = $tests[$service];
            $tests = [$service => $serviceArray];

            if ($version != 'all') {
                if (!isset($serviceArray[$version])) {
                    $this->logger->critical(sprintf("%s is not a valid version for the %s service", $version, $service));
                    exit(1);
                }

                $versionArray = $serviceArray[$version];
                if ($module != 'core') {
                    if (!in_array($module, $serviceArray[$version])) {
                        $this->logger->critical(sprintf("%s is not a valid test class for the %s %s service", $module, $version, $service));
                        exit(1);
                    }
                    $versionArray = [$module];
                }

                $tests = [$service => [$version => $versionArray]];
            }
        }

        return $tests;
    }

    /**
     * @return TestInterface
     */
    private function getTest($service, $version, $test, $verbosity)
    {
        $className = sprintf("%s\\%s\\%s\\%sTest", $this->namespace, Utils::toCamelCase($service), $version, ucfirst($test));

        if (!class_exists($className)) {
            throw new \RuntimeException(sprintf("%s does not exist", $className));
        }

        $basePath = $this->samplesDir . DIRECTORY_SEPARATOR . $service . DIRECTORY_SEPARATOR . $version;
        $smClass  = sprintf("%s\\SampleManager", $this->namespace);
        $class = new $className($this->logger, new $smClass($basePath, $verbosity), $verbosity);

        if (!($class instanceof TestInterface)) {
            throw new \RuntimeException(sprintf("%s does not implement TestInterface", $className));
        }

        return $class;
    }

    public function runServices()
    {
        list($serviceOpt, $versionOpt, $moduleOpt, $testMethodOpt, $verbosityOpt) = $this->getOpts();

        foreach ($this->getRunnableServices($serviceOpt, $versionOpt, $moduleOpt) as $serviceName => $serviceArray) {
            foreach ($serviceArray as $versionName => $versionArray) {
                foreach ($versionArray as $testName) {
                    $this->logger->info(str_repeat('=', 49));
                    $this->logger->info("Starting %s %v %m integration test(s)", [
                        '%s' => $serviceName,
                        '%v' => $versionName,
                        '%m' => $moduleOpt,
                    ]);
                    $this->logger->info(str_repeat('=', 49));

                    $testRunner = $this->getTest($serviceName, $versionName, $testName, $verbosityOpt);

                    try {
                        if ($testMethodOpt) {
                            $testRunner->runOneTest($testMethodOpt);
                        } else {
                            $testRunner->runTests();
                        }
                    } finally {
                        $this->logger->info(str_repeat('=', 11));
                        $this->logger->info('Cleaning up');
                        $this->logger->info(str_repeat('=', 11));
                        $testRunner->teardown();
                    }
                }
            }
        }
    }
}
