<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;

Relation::morphMap([
    'product' => 'App\Product',
    'shop' => 'App\Shop',
]);

class Coupon extends Model
{
    protected $casts = [
        'meta' => 'array',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'description', 'code', 'type', 'rate', 'meta', 'start_date', 'end_date'
    ];

    /**
     * Get all of the owning coupon models.
     */
    public function couponable()
    {
        return $this->morphTo();
    }

    public function recentActivities()
    {
        return $this->morphMany('App\RecentActivity', 'trackable')
                    ->where('trackable_id', $this->id)
                    ->where('trackable_type', 'coupon')
                    ->orderBy('created_at', 'desc');
    }
}