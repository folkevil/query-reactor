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

namespace Repo2\QueryReactor\Tests\Query;

use Repo2\QueryReactor\Query\GenericQuery;
use Repo2\QueryReactor\Tests\Fixtures;

class GenericQueryTest extends \PHPUnit_Framework_TestCase
{
    public function testGetExpression()
    {
        $expr = Fixtures::getSelect();
        $query = new GenericQuery($expr);
        $this->assertSame($expr, $query->getExpression());
    }

    public function testResolve()
    {
        $onFulfill = $this->getMock('\Repo2\QueryReactor\Tests\Stub');

        $onFulfill->expects($this->once())
            ->method('__invoke');

        $query = new GenericQuery(Fixtures::getSelect(), $onFulfill);

        $this->assertNull($query->resolve(new \ArrayObject()));
    }

    public function testResolveWithReturn()
    {
        $onFulfill = $this->getMock('\Repo2\QueryReactor\Tests\Stub');

        $onFulfill->expects($this->once())
            ->method('__invoke')
            ->will($this->returnValue('ILovePHP'));

        $query = new GenericQuery(Fixtures::getSelect(), $onFulfill);

        $this->assertSame('ILovePHP', $query->resolve(new \ArrayObject()));
    }

    public function testReject()
    {
        $onReject = $this->getMock('\Repo2\QueryReactor\Tests\Stub');

        $onReject->expects($this->once())
            ->method('__invoke');

        $query = new GenericQuery(Fixtures::getSelect(), null, $onReject);

        $this->assertNull($query->reject(new \Exception()));
    }

    public function testRejectWithReturn()
    {
        $onReject = $this->getMock('\Repo2\QueryReactor\Tests\Stub');

        $onReject->expects($this->once())
            ->method('__invoke')
            ->will($this->returnValue('ILoveException'));

        $query = new GenericQuery(Fixtures::getSelect(), null, $onReject);

        $this->assertSame('ILoveException', $query->reject(new \Exception()));
    }
}
