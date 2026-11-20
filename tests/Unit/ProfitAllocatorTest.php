<?php

namespace Tests\Unit;

use App\Services\ProfitAllocator;
use PHPUnit\Framework\TestCase;

class ProfitAllocatorTest extends TestCase
{
    public function test_cut_uses_actual_price_for_profit(): void
    {
        $a = ProfitAllocator::forCut(1, 250, 370, ['full_length' => 12, 'sold_length' => 6], 150);

        $this->assertEquals(125, $a['sold_cost']);
        $this->assertEquals(25,  $a['sold_profit']);
        $this->assertEquals(125, $a['rest_cost']);
    }

    public function test_cut_without_actual_price_keeps_old_behaviour(): void
    {
        $a = ProfitAllocator::forCut(1, 250, 370, ['full_length' => 12, 'sold_length' => 6]);

        $this->assertEquals(60, $a['sold_profit']);
    }

    public function test_leftover_profit_uses_real_revenue(): void
    {
        $a = ProfitAllocator::forRemnantUnits(125, 60, 1, 1, 200);

        $this->assertEquals(125, $a['sold_cost']);
        $this->assertEquals(75,  $a['sold_profit']);
    }

    public function test_partial_leftover_sale_splits_cost(): void
    {
        $a = ProfitAllocator::forRemnantUnits(250, 120, 2, 1, 160);

        $this->assertEquals(125, $a['sold_cost']);
        $this->assertEquals(35,  $a['sold_profit']);
        $this->assertEquals(125, $a['rest_cost']);
    }
}