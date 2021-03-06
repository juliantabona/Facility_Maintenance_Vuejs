<?php

namespace App;

use App\Traits\CommonTraits;
use App\Traits\TransactionTraits;
use App\AdvancedFilter\Dataviewer;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use Dataviewer, CommonTraits, TransactionTraits;

    protected $casts = [
        'metadata' => 'array',
        'automatic' => 'boolean', //  Return the following 1/0 as true/false
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [

        /*  Transaction Details  */
        'type', 'status', 'automatic', 'payment_type', 'payment_amount',

        /*  Ownership Information  */
        'owner_id', 'owner_type',

        /*  Meta Data  */
        'metadata'

    ];

    protected $allowedFilters = [];

    protected $allowedOrderableColumns = [];

    /*
     *  Scope by successful transactions
     */
    public function scopeSuccessful($query)
    {
        return $query->where('transactions.status', 'success');
    }

    /*
     *  Scope by failed transactions
     */
    public function scopeFailed($query)
    {
        return $query->where('transactions.status', 'failed');
    }

    /*
     *  Scope by payment (sale) transactions
     */
    public function scopePayments($query)
    {
        return $query->where('transactions.type', 'payment');
    }

    /*
     *  Scope by refund transactions
     */
    public function scopeRefunds($query)
    {
        return $query->where('transactions.type', 'refund');
    }

    /* 
     *  Returns the owner of the transaction
     *  In most cases this returns an invoice
     */
    public function owner()
    {
        return $this->morphTo();
    }

    /* 
     *  Returns recent activities owned by this transaction
     */
    public function recentActivities()
    {
        return $this->morphMany('App\RecentActivity', 'owner')->orderBy('created_at', 'desc');
    }

    /* ATTRIBUTES */

    protected $appends = [
        'resource_type'
    ];

    /*
     *  Returns the current status name and description of the transaction
     */
    public function getStatusAttribute($value)
    {
        switch (ucwords($value)) {
            case 'Success':
                $status_description = 'The transaction was successful';
                break;
            case 'Failed':
                $status_description = 'The transaction failed';
                break;
            case 'Cancelled':
                $status_description = 'The transaction was cancelled';
                break;
            default:
                $status_description = 'The transaction status is unknown';
        }

        return [
            'name' => ucwords($value),
            'description' => $status_description,
        ];
    }

    /* 
     *  Returns the resource type
     */
    public function getResourceTypeAttribute()
    {
        return strtolower(class_basename($this));
    }

    public function setAutomaticAttribute($value)
    {
        $this->attributes['automatic'] = ( ($value == 'true' || $value == '1') ? 1 : 0);
    }

}
