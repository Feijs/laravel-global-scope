<?php

use Mockery as m;

class GlobalScopeTest extends PHPUnit_Framework_TestCase
{
    public function tearDown()
    {
        m::close();
    }

    public function testRemove()
    {
        $nestedQuery = m::mock('Illuminate\Database\Query\Expression');
        $nestedQuery->shouldReceive('getValue')->andReturn(
            '(select count(*) from "table" where ("table"."stub_id" = "table"."id" and "flub" = ? or "flob" = ? or "plugh" is null) and "tenant_id" = ?)'
        );

        $nestedValue = m::mock('Illuminate\Database\Query\Expression');
        $nestedValue->shouldReceive('getValue')->andReturn(1);

        $expectedWheres = [
            [
                'type'      => "Basic",
                'column'    => "baz",
                'operator'  => "=",
                'value'     => 3,
                'boolean'   => 'and'
            ],
            [
                'type'      => "NotNull",
                'column'    => "quux",
                'boolean'   => 'or'
            ],
            [
                'type'      => "Basic",
                'column'    => $nestedQuery,
                'operator'  => ">=",
                'value'     => $nestedValue,
                'boolean'   => 'and'
            ],
        ];

        $scopedQuery = m::mock('Illuminate\Database\Query\Builder');
        $scopedQuery->wheres = [[
            'type'      => "Basic",
            'column'    => "foo",
            'operator'  => "=",
            'value'     => 2,
            'boolean'   => 'and'
        ]];

        $query = m::mock('Illuminate\Database\Query\Builder');
        $query->wheres = array_merge($expectedWheres, $scopedQuery->wheres);
        $query->shouldReceive('getRawBindings')->once()->andReturn(['where' => [3, 'x', 'y', 'z', 2] ]);
        $query->shouldReceive('setBindings')->once()->andReturn([3, 'x', 'y', 'z'], 'where');

        $builder = m::mock('Illuminate\Database\Eloquent\Builder');
        $builder->shouldReceive('getQuery')->times(2)->andReturn($query, $scopedQuery);

        $model = m::mock('Illuminate\Database\Eloquent\Model');
        $model->shouldReceive('newQueryWithoutScopes')->once()->andReturn($builder);

        $scope = m::mock('Sofa\GlobalScope\GlobalScope[apply]');
        $scope->shouldReceive('apply')->once()->with($builder, $model);

        $scope->remove($builder, $model);

        $this->assertEquals($expectedWheres, $query->wheres);
    }
}