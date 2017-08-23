<?php

namespace Yokai\SecurityExtraBundle\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase as BaseKernelTestCase;
use Symfony\Component\Filesystem\Filesystem;

abstract class KernelTestCase extends BaseKernelTestCase
{
    public static function setUpBeforeClass()
    {
        static::deleteTmpDir();
    }

    public static function tearDownAfterClass()
    {
        static::deleteTmpDir();
    }

    protected static function deleteTmpDir()
    {
        if (!file_exists($dir = sys_get_temp_dir() . '/' . static::getVarDir())) {
            return;
        }

        $fs = new Filesystem();
        $fs->remove($dir);
    }

    protected static function createKernel(array $options = array())
    {
        if (null === static::$class) {
            static::$class = static::getKernelClass();
        }

        if (!isset($options['test_case'])) {
            throw new \InvalidArgumentException('The option "test_case" must be set.');
        }

        return new static::$class(
            static::getVarDir(),
            $options['test_case'],
            isset($options['environment']) ? $options['environment'] : strtolower(static::getVarDir() . $options['test_case']),
            isset($options['debug']) ? $options['debug'] : true
        );
    }

    protected static function getVarDir()
    {
        return 'SB' . substr(strrchr(get_called_class(), '\\'), 1);
    }
}
