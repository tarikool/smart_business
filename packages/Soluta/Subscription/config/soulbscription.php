<?php

return [
    'database' => [
        'cancel_migrations_autoloading' => false,
    ],

    'feature_tickets' => env('SOULBSCRIPTION_FEATURE_TICKETS', false),

    'models' => [

        'feature' => \Soluta\Subscription\Models\Feature::class,

        'feature_consumption' => \Soluta\Subscription\Models\FeatureConsumption::class,

        'feature_ticket' => \Soluta\Subscription\Models\FeatureTicket::class,

        'feature_plan' => \Soluta\Subscription\Models\FeaturePlan::class,

        'plan' => \Soluta\Subscription\Models\Plan::class,

        'subscriber' => [
            'uses_uuid' => env('SOULBSCRIPTION_SUBSCRIBER_USES_UUID', false),
        ],

        'subscription' => \Soluta\Subscription\Models\Subscription::class,

        'subscription_renewal' => \Soluta\Subscription\Models\SubscriptionRenewal::class,
    ],
];
