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

namespace Repo2\QueryReactor\Sharding;

use Repo2\QueryReactor\Controller;
use Repo2\QueryReactor\Driver;
use Repo2\QueryReactor\Query;

class ShardingController implements Controller
{
    /**
     * @var ShardingService
     */
    private $sharding;

    /**
     * @var string
     */
    private $controllerClass;

    /**
     * @var Controller[]
     */
    private $controllers;

    /**
     * @param ShardingService $sharding
     * @param string $controllerClass
     */
    public function __construct(ShardingService $sharding, $controllerClass)
    {
        $this->sharding = $sharding;
        $this->controllerClass = $controllerClass;
    }

    /**
     * @inheritDoc
     */
    public function next(Driver $driver, $link)
    {
        foreach ($this->controllers as $controller) {
            try {
                return $controller->next($driver, $link);
            } catch (\OutOfBoundsException $err) {
                continue;
            }
        }
        throw new \OutOfBoundsException(sprintf('Undefined %s in sharding connection.', $driver->info($link)));
    }

    /**
     * @inheritDoc
     */
    public function query(Driver $driver, Query $query)
    {
        if ($query instanceof ShardedQuery) {
            $controller = $this->pickShard($query);
        } else {
            $controller = $this->pickGlobal();
        }
        return $controller->query($driver, $query);
    }

    /**
     * @return Controller
     */
    private function pickGlobal()
    {
        if (empty($this->controllers[0])) {
            $this->controllers[0] = new $this->controllerClass($this->sharding->selectGlobal());
        }
        return $this->controllers[0];
    }

    /**
     * @param ShardedQuery $query
     * @return Controller
     */
    private function pickShard(ShardedQuery $query)
    {
        $params = $this->sharding->selectShard($query->getDistributionName(), $query->getDistributionValue());

        $shardId = $params['id'];

        if (empty($this->controllers[$shardId])) {
            $this->controllers[$shardId] = new $this->controllerClass($params);
        }

        return $this->controllers[$shardId];
    }
}
