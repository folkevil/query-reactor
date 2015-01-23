<?php

/**
 * The MIT License (MIT)
 *
 * Copyright (c) 2015 Repo2
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

namespace Repo2\QueryReactor\Tests;

use Repo2\QueryReactor\Driver\Mysqli\MysqliDriver;
use Psr\Log\NullLogger;

class TestUtil
{
    public static function getDriver()
    {
        $type = getenv('REPO2_DB_TYPE');

        if (!$type) {
            die('Environment variable "REPO2_DB_TYPE" not defined.' . PHP_EOL);
        }

        switch ($type) {
            case 'mysql':
                return new MysqliDriver(new NullLogger());
            default:
                die(sprintf('The driver "%s" not supported.', $type) . PHP_EOL);
        }
    }

    public static function getControllerParams()
    {
        return [
            'host' => self::getenv('REPO2_DB_HOST', 'localhost'),
            'dbname' => self::getenv('REPO2_DB_NAME', 'repo2_test'),
            'username' => self::getenv('REPO2_DB_USERNAME', 'root'),
            'passwd' => self::getenv('REPO2_DB_PASSWD', '')
        ];
    }

    private static function getenv($name, $default)
    {
        return getenv($name) ?: $default;
    }
}
