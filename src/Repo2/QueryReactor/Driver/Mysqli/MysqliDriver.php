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

namespace Repo2\QueryReactor\Driver\Mysqli;

use Repo2\QueryBuilder;
use Repo2\QueryReactor\Driver;
use Psr\Log\LoggerInterface;

class MysqliDriver implements Driver
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var int
     */
    private $timeout;

    /**
     * @param LoggerInterface $logger
     * @param int $timeout
     */
    public function __construct(LoggerInterface $logger, $timeout = 1)
    {
        $this->logger = $logger;
        $this->timeout = $timeout;
    }

    /**
     * @inheritDoc
     */
    public function connect(array $params, $username, $passwd)
    {
        $link = mysqli_init();
        set_error_handler(function () {
        });
        if (!$link->real_connect($params['host'], $username, $passwd, $params['dbname'])) {
            restore_error_handler();
            throw new MysqliException($link->connect_error, $link->connect_errno);
        }
        restore_error_handler();
        $this->logger->debug(sprintf('Connected to %s.', $this->info($link)));
        return $link;
    }

    /**
     * @inheritDoc
     */
    public function query($link, QueryBuilder\ExpressionInterface $expr)
    {
        /* @var $link \mysqli */
        $sql = $expr->compile(new QueryBuilder\Driver\Mysqli($link));
        if (false === $link->query($sql, MYSQLI_ASYNC)) {
            throw $this->error($link);
        }
        $this->logger->debug(sprintf('Running %s on %s.', $sql, $this->info($link)));
    }

    /**
     * @inheritDoc
     */
    public function error($link)
    {
        /* @var $link \mysqli */
        return new MysqliException($link->error, $link->errno);
    }

    /**
     * @inheritDoc
     */
    public function poll(array $links)
    {
        $read = $error = $reject = $links;

        if (false === mysqli_poll($read, $error, $reject, $this->timeout)) {
            throw new MysqliException(sprintf('Maximum polling time of %d seconds exceeded.', $this->timeout));
        }

        foreach ($reject as $link) {
            /* @var $link \mysqli */
            throw new MysqliException(sprintf('The connection to the host %s is rejected.', $link->host_info));
        }

        return [$read, $error];
    }

    /**
     * @inheritDoc
     */
    public function getResult($link)
    {
        /* @var $link \mysqli */
        return $link->reap_async_query();
    }

    /**
     * @inheritDoc
     */
    public function freeResult($result)
    {
        /* @var $result \mysqli_result */
        $result->free_result();
    }

    /**
     * @inheritDoc
     */
    public function info($link)
    {
        /* @var $link \mysqli */
        return sprintf('%s (thread %d)', $link->host_info, $link->thread_id);
    }
}
