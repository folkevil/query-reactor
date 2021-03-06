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

namespace Repo2\QueryReactor;

use Repo2\QueryBuilder\ExpressionInterface;

interface Driver
{
    /**
     * @param array $params
     * @param string $username
     * @param string $passwd
     * @return mixed
     */
    public function connect(array $params, $username, $passwd);

    /**
     * @param mixed $link
     * @param ExpressionInterface $expr
     * @throws Driver\DriverException
     */
    public function query($link, ExpressionInterface $expr);

    /**
     * @param mixed $link
     * @return Driver\DriverException
     */
    public function error($link);

    /**
     * @param array $links
     * @return array
     * @throws Driver\DriverException
     */
    public function poll(array $links);

    /**
     * @param mixed $link
     * @return string
     */
    public function info($link);

    /**
     * @param mixed $link
     * @return mixed
     */
    public function getResult($link);

    /**
     * @param mixed $result
     */
    public function freeResult($result);
}
