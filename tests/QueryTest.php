<?php

namespace Mblarsen\LaravelRepository\Tests;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Mblarsen\LaravelRepository\Tests\Models\User;
use Mblarsen\LaravelRepository\Tests\Repositories\UserRepository;

class QueryTest extends TestCase
{
    private $driver_name;

    public function setUp(): void
    {
        parent::setUp();

        $this->driver_name = DB::getDriverName();
    }

    /** @test */
    public function outputs_query()
    {

        $repository = resolve(UserRepository::class);

        /** @var Builder */
        $query_all = $repository->allQuery();
        /** @var Builder */
        $query_find = $repository->findQuery(3);
        /** @var Builder */
        $query_list = $repository->listQuery('first_name');
        /** @var Builder */
        $query_list_callable = $repository->listQuery(function ($model) {
            return $model->first_name;
        });

        $this->assertInstanceOf(Builder::class, $query_all);
        $this->assertInstanceOf(Builder::class, $query_find);
        $this->assertInstanceOf(Builder::class, $query_list);
        $this->assertInstanceOf(Builder::class, $query_list_callable);

        $this->assertInstanceOf(User::class, $query_all->getModel());
        $this->assertInstanceOf(User::class, $query_find->getModel());
        $this->assertInstanceOf(User::class, $query_list->getModel());
        $this->assertInstanceOf(User::class, $query_list_callable->getModel());

        $this->assertEquals(
            $this->fixSQL('select * from "users" order by "first_name" asc'),
            $query_all->toBase()->toSql()
        );
        $this->assertEquals(
            $this->fixSQL('select * from "users" where "id" = ?'),
            $query_find->toBase()->toSql()
        );
        $this->assertEquals(
            $this->fixSQL('select "id" as "value", "first_name" as "label" from "users" order by "first_name" asc'),
            $query_list->toBase()->toSql()
        );
        $this->assertEquals(
            $this->fixSQL('select * from "users" order by "first_name" asc'),
            $query_list_callable->toBase()->toSql()
        );
    }

    protected function fixSQL($sql)
    {
        switch ($this->driver_name) {
            case "mysql":
                return str_replace('"', '`', $sql);
            case "sqlite":
                return str_replace('`', '"', $sql);
            default:
                break;
        }
        return $sql;
    }
}
