<?php

namespace App\Models\SecondaryDB;

use Illuminate\Database\Eloquent\Model;

class PurchaseItem extends Model
{
    protected $connection = 'purchaseDB';

    protected $table = 'transaction_items';
}
