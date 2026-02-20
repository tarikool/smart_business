<?php

namespace Database\Seeders;

use App\Enums\GatewayType;
use App\Models\Country;
use Illuminate\Database\Seeder;
use Soluta\Subscription\Models\Feature;
use Soluta\Subscription\Models\PaymentGateway;
use Soluta\Subscription\Models\Plan;

class SubscriptionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //        Feature::factory()->count(5)->create();
        //        Plan::factory()->count(3)->create();

        collect(GatewayType::cases())->each(function ($gateway) {
            $country = Country::whereIsoCode($gateway->countryCode())->first();

            PaymentGateway::updateOrCreate(
                ['name' => $gateway],
                ['country_id' => $country?->id]
            );
        });
    }
}
