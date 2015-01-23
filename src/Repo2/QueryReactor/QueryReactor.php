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

class QueryReactor
{
    /**
     * @var Driver
     */
    private $driver;

    /**
     * @var Controller
     */
    private $controller;

    /**
     * @var \SplObjectStorage
     */
    private $active;

    /**
     * @param Driver $driver
     * @param Controller $controller
     */
    public function __construct(Driver $driver, Controller $controller)
    {
        $this->driver = $driver;
        $this->controller = $controller;
        $this->active = new \SplObjectStorage();
    }

    private function exec()
    {
        foreach (func_get_args() as $query) {
            if ($query instanceof Query) {
                $this->execQuery($query);
            } elseif ($query instanceof \Iterator) {
                $this->execIterator($query);
            }
        }
    }

    /**
     * @param Query $query
     * @return QueryReactor
     */
    public function execQuery(Query $query)
    {
        $link = $this->controller->query($this->driver, $query);
        if ($link) {
            $this->active->attach($link, $query);
        }
        return $this;
    }

    /**
     * @param \Iterator $iterator
     * @return QueryReactor
     */
    public function execIterator(\Iterator $iterator)
    {
        foreach ($iterator as $query) {
            $this->execQuery($query);
        }
        return $this;
    }

    /**
     * @param mixed $link
     * @return Query
     */
    private function free($link)
    {
        $query = $this->active[$link];
        // schedule a next query
        $next = $this->controller->next($this->driver, $link);
        if ($next) {
            $this->active->attach($link, $next);
        } else {
            $this->active->detach($link);
        }
        return $query;
    }

    /**
     * @param mixed $link
     */
    private function reject($link)
    {
        $error = $this->driver->error($link);
        $query = $this->free($link);
        $this->exec($query->reject($error));
    }

    /**
     * @param mixed $link
     * @param mixed $result
     */
    private function resolve($link, $result)
    {
        $query = $this->free($link);
        if ($result instanceof \Traversable) {
            $this->exec($query->resolve($result));
            $this->driver->freeResult($result);
        }
    }

    /**
     * @return int
     * @throws Driver\DriverException
     */
    public function poll()
    {
        $links = [];
        foreach ($this->active as $link) {
            $links[] = $link;
        }

        list($read, $error) = $this->driver->poll($links);

        foreach ($error as $link) {
            $this->reject($link);
        }

        foreach ($read as $link) {
            $result = $this->driver->getResult($link);
            if (false === $result) {
                $this->reject($link);
            } else {
                $this->resolve($link, $result);
            }
        }

        return $this->active->count();
    }

    public function await()
    {
        while ($this->poll()) {
        }
    }
}
