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

namespace Repo2\QueryReactor\Tests\Sharding;

use Repo2\QueryBuilder\ExpressionInterface;
use Repo2\QueryReactor\Sharding\ShardingController;
use Repo2\QueryReactor\Sharding\ShardingService;
use Repo2\QueryReactor\Tests\ControllerTestCase;
use Repo2\QueryReactor\Tests\TestUtil;
use Repo2\QueryReactor\Tests\Fixtures;

class ShardingControllerTest extends ControllerTestCase
{
    private function getShardingMock()
    {
        return $this->getMock('\Repo2\QueryReactor\Sharding\ShardingService');
    }

    private function createShardingController(ShardingService $sharding)
    {
        return new ShardingController($sharding, '\Repo2\QueryReactor\Controller\PoolingController');
    }

    private function getShardedQueryMock(ExpressionInterface $expr)
    {
        return $this->getQueryMock($expr, '\Repo2\QueryReactor\Tests\Sharding\ShardedStubQuery');
    }

    protected function createController(array $params)
    {
        $sharding = $this->getShardingMock();
        $sharding->expects($this->once())
            ->method('selectGlobal')
            ->will($this->returnValue($params));

        return $this->createShardingController($sharding);
    }

    public function testQueryToSingleShard()
    {
        $shardParams = TestUtil::getControllerParams() + ['id' => 1];

        $sharding = $this->getShardingMock();
        $sharding->expects($this->never())->method('selectGlobal');
        $sharding->expects($this->once())
            ->method('selectShard')
            ->will($this->returnValue($shardParams));

        $query = $this->getShardedQueryMock(Fixtures::getSelect());
        $query->expects($this->once())->method('getDistributionName');
        $query->expects($this->once())->method('getDistributionValue');
        $query->expects($this->once())->method('resolve');

        $reactor = $this->createReactor($this->createShardingController($sharding));

        $reactor->execQuery($query)->await();
    }

    public function testQueriesToTwoShards()
    {
        $sharding = $this->getShardingMock();

        $sharding->expects($this->never())->method('selectGlobal');
        $sharding->expects($this->exactly(2))
            ->method('selectShard')
            ->will($this->returnCallback(function ($distributionName, $distributionValue) {
                return TestUtil::getControllerParams() + ['name' => $distributionName, 'id' => $distributionValue];
            }));

        $queryX = $this->getShardedQueryMock(Fixtures::getSelect());
        $queryX->expects($this->once())->method('getDistributionValue')->will($this->returnValue(1));
        $queryX->expects($this->once())->method('resolve');

        $queryY = $this->getShardedQueryMock(Fixtures::getSelect());
        $queryY->expects($this->once())->method('getDistributionValue')->will($this->returnValue(2));
        $queryY->expects($this->once())->method('resolve');

        $queries = new \ArrayObject([$queryX, $queryY]);

        $reactor = $this->createReactor($this->createShardingController($sharding));
        $reactor->execIterator($queries->getIterator())->await();
    }
}
