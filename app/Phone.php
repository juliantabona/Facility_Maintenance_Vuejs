<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;

Relation::morphMap([
    'user' => 'App\User',
    'company' => 'App\Company',
]);

class Phone extends Model
{
    //  protected $with = ['createdBy'];

    protected $casts = [
        'calling_code' => 'array',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'number', 'calling_code', 'type', 'company_branch_id', 'company_id', 'created_by',
    ];

    /**
     * Get all of the owning documentable models.
     */
    public function trackable()
    {
        return $this->morphTo();
    }

    public function createdBy()
    {
        return $this->belongsTo('App\User', 'created_by');
    }

    public function recentActivities()
    {
        return $this->morphMany('App\RecentActivity', 'trackable')
                    ->orderBy('created_at', 'desc');
    }
}