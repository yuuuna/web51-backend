<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Ad extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'ad_request_id',
        'house_id',
        'publish_start_date',
        'publish_end_date'
    ];

    public function ad_request()
    {
        // $foreignKey -> Ad Request key
        // $localKey -> Ad key
        return $this->hasOne(AdRequest::class, 'id', 'ad_request_id');
    }

    public function house()
    {
        // $foreignKey -> House key
        // $localKey -> Ad key
        return $this->hasOne(House::class, 'id', 'house_id');
    }
}
