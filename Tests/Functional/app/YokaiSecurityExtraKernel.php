<?php

use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\HttpKernel\Kernel;

class YokaiSecurityExtraKernel extends Kernel
{
    private $varDir;
    private $testCase;

    public function __construct($varDir, $testCase, $environment, $debug)
    {
        if (!is_dir(__DIR__ . '/' . $testCase)) {
            throw new \InvalidArgumentException(sprintf('The test case "%s" does not exist.', $testCase));
        }

        $this->varDir = $varDir;
        $this->testCase = $testCase;

        parent::__construct($environment, $debug);
    }

    public function registerBundles()
    {
        if (!file_exists($filename = $this->getRootDir() . '/' . $this->testCase . '/bundles.php')) {
            throw new \RuntimeException(sprintf('The bundles file "%s" does not exist.', $filename));
        }

        return include $filename;
    }

    public function registerContainerConfiguration(LoaderInterface $loader)
    {
        $loader->load(__DIR__ . '/' . $this->testCase . '/config.yml');
    }

    public function getRootDir()
    {
        return __DIR__;
    }

    public function getCacheDir()
    {
        return sys_get_temp_dir() . '/' . $this->varDir . '/' . $this->testCase . '/cache/' . $this->environment;
    }

    public function getLogDir()
    {
        return sys_get_temp_dir() . '/' . $this->varDir . '/' . $this->testCase . '/logs';
    }

    protected function getKernelParameters()
    {
        $parameters = parent::getKernelParameters();
        $parameters['kernel.test_case'] = $this->testCase;

        return $parameters;
    }
}
