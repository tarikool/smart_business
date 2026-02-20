<?php

namespace Soluta\Subscription\Models\Concerns;

use Illuminate\Support\Carbon;
use Soluta\Subscription\Enums\PeriodicityType;

trait HandlesRecurrence
{
    public function calculateNextRecurrenceEnd(Carbon|string|null $start = null): Carbon
    {
        if (empty($start)) {
            $start = now();
        }

        if (is_string($start)) {
            $start = Carbon::parse($start);
        }

        //        $recurrences = max(PeriodicityType::getDateDifference(from: $start, to: now(), unit: $this->periodicity_type), 0,);

        $expirationDate = $start->copy()->add($this->periodicity_type, $this->periodicity);

        return $expirationDate;
    }
}
