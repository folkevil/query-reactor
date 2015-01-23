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

namespace Repo2\QueryReactor\Controller;

use Repo2\QueryReactor\Controller;
use Repo2\QueryReactor\Driver;
use Repo2\QueryReactor\Query;

class PoolingController implements Controller
{
    /**
     * @var array
     */
    private $params;

    /**
     * @var \SplObjectStorage
     */
    private $pool;

    /**
     * @var \SplQueue
     */
    private $idle;

    /**
     * @var \SplQueue
     */
    private $waiting;

    /**
     * @param array $params
     */
    public function __construct(array $params)
    {
        $this->params = $params + [
            'max_connections' => 10,
            'username' => null,
            'passwd' => null
        ];
        $this->pool = new \SplObjectStorage();
        $this->idle = new \SplQueue();
        $this->waiting = new \SplQueue();
    }

    /**
     * @inheritDoc
     */
    public function query(Driver $driver, Query $query)
    {
        if (!$this->idle->isEmpty()) {
            $link = $this->idle->dequeue();
        } elseif ($this->pool->count() >= $this->params['max_connections']) {
            $this->waiting->enqueue($query);
            return false;
        } else {
            $link = $driver->connect($this->params, $this->params['username'], $this->params['passwd']);
            $this->pool->attach($link);
        }
        $driver->query($link, $query->getExpression());
        return $link;
    }

    /**
     * @inheritDoc
     */
    public function next(Driver $driver, $link)
    {
        if (!isset($this->pool[$link])) {
            throw new \OutOfBoundsException(sprintf('Undefined %s in the pooling controller.', $driver->info($link)));
        }
        if (!$this->waiting->isEmpty()) {
            $query = $this->waiting->dequeue();
            $driver->query($link, $query->getExpression());
            return $query;
        }
        $this->idle->enqueue($link);
    }
}
