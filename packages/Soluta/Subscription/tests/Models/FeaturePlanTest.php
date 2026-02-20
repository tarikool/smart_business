<?php

namespace Tests\Feature\Models;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Soluta\Subscription\Models\Feature;
use Soluta\Subscription\Models\FeaturePlan;
use Soluta\Subscription\Models\Plan;
use Tests\TestCase;

class FeaturePlanTest extends TestCase
{
    use RefreshDatabase;
    use WithFaker;

    public function test_model_can_retrieve_plan()
    {
        $feature = Feature::factory()
            ->create();

        $plan = Plan::factory()->create();
        $plan->features()->attach($feature);

        $featurePlanPivot = FeaturePlan::first();

        $this->assertEquals($plan->id, $featurePlanPivot->plan->id);
    }

    public function test_model_can_retrieve_feature()
    {
        $feature = Feature::factory()
            ->create();

        $plan = Plan::factory()->create();
        $plan->features()->attach($feature);

        $featurePlanPivot = FeaturePlan::first();

        $this->assertEquals($feature->id, $featurePlanPivot->feature->id);
    }
}
