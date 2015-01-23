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

use Repo2\QueryBuilder;
use Repo2\QueryBuilder\DDL;

class Fixtures
{
    const TABLE_NAME = 'repo2_table';

    public static function getCreateTable()
    {
        return DDL\create(self::TABLE_NAME, [
            DDL\column('id')->integer()->primary(),
            DDL\column('name')->varchar(50)->required()
        ]);
    }

    public static function getDropTable()
    {
        return DDL\drop(self::TABLE_NAME);
    }

    public static function getInsert()
    {
        return QueryBuilder\insert(self::TABLE_NAME, ['id' => 1, 'name' => 'foo']);
    }

    public static function getSelect()
    {
        return QueryBuilder\select(self::TABLE_NAME, ['id', 'name']);
    }

    public static function getBadSyntax()
    {
        return QueryBuilder\assign('foo', 'bar');
    }
}
