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

use Repo2\QueryBuilder\ExpressionInterface;
use Repo2\QueryReactor\Query;
use Repo2\QueryReactor\QueryReactor;
use Repo2\QueryReactor\Controller;

abstract class ControllerTestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Repo2\QueryReactor\QueryReactor
     */
    protected $reactor;

    /**
     * @param Controller $controller
     * @return QueryReactor
     */
    protected function createReactor(Controller $controller)
    {
        return new QueryReactor(TestUtil::getDriver(), $controller);
    }

    /**
     * @param array $params
     * @return \Repo2\QueryReactor\Controller
     */
    abstract protected function createController(array $params);

    public function setUp()
    {
        $this->reactor = $this->createReactor($this->createController(TestUtil::getControllerParams()));
        $this->queryAwait(new Query\GenericQuery(Fixtures::getCreateTable()));
    }

    public function tearDown()
    {
        $this->queryAwait(new Query\GenericQuery(Fixtures::getDropTable()));
    }

    /**
     * @param Query $query
     */
    protected function queryAwait(Query $query)
    {
        $this->reactor->execQuery($query)->await();
    }

    protected function getQueryMock(ExpressionInterface $expression, $queryClass = '\Repo2\QueryReactor\Query')
    {
        $mock = $this->getMock($queryClass);

        $mock->expects($this->once())
            ->method('getExpression')
            ->will($this->returnValue($expression));

        return $mock;
    }

    public function testSingleInsert()
    {
        $query = $this->getQueryMock(Fixtures::getInsert());

        $query->expects($this->never())->method('resolve');
        $query->expects($this->never())->method('reject');

        $this->queryAwait($query);
    }

    public function testEmptySelect()
    {
        $query = $this->getQueryMock(Fixtures::getSelect());

        $query->expects($this->once())->method('resolve');
        $query->expects($this->never())->method('reject');

        $this->queryAwait($query);
    }

    public function testRejectOnResultError()
    {
        $query = $this->getQueryMock(Fixtures::getBadSyntax());

        $query->expects($this->never())->method('resolve');
        $query->expects($this->once())->method('reject');

        $this->queryAwait($query);
    }

    public function testParallelSelect()
    {
        $expression = Fixtures::getSelect();

        $queryX = $this->getQueryMock($expression);
        $queryX->expects($this->once())->method('resolve');

        $queryY = $this->getQueryMock($expression);
        $queryY->expects($this->once())->method('resolve');

        $queries = new \ArrayObject([$queryX, $queryY]);

        $this->reactor->execIterator($queries->getIterator())->await();
    }

    public function testSelectWithQueryCascading()
    {
        $expression = Fixtures::getSelect();

        $cascadedQuery = $this->getQueryMock($expression);
        $cascadedQuery->expects($this->once())->method('resolve');

        $query = $this->getQueryMock($expression);
        $query->expects($this->once())
            ->method('resolve')
            ->will($this->returnValue($cascadedQuery));

        $this->queryAwait($query);
    }

    public function testSelectWithIteratorCascading()
    {
        $expression = Fixtures::getSelect();

        $cascadedQueryY = $this->getQueryMock($expression);
        $cascadedQueryY->expects($this->once())->method('resolve');

        $cascadedQueryZ = $this->getQueryMock($expression);
        $cascadedQueryZ->expects($this->once())->method('resolve');

        $cascading = new \ArrayObject([$cascadedQueryY, $cascadedQueryZ]);

        $query = $this->getQueryMock($expression);
        $query->expects($this->once())
            ->method('resolve')
            ->will($this->returnValue($cascading->getIterator()));

        $this->queryAwait($query);
    }
}
