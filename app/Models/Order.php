<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = ['user_id', 'total_amount', 'status', 'phone', 'address', 'note'];

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }
}
