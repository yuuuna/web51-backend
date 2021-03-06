<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Area extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id',
        'city_id',
        'name'
    ];

    public function city()
    {
        // $foreignKey -> City key
        // $localKey -> Area key
        return $this->hasOne(City::class, 'id', 'city_id');
    }
}
