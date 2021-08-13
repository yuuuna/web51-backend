<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HousesExtra extends Model
{
    use HasFactory;

    public $timestamps = false;
    protected $table = 'houses_extra';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'house_id',
        'description',
        'full_address'
    ];

    public function house()
    {
        // $foreignKey -> House key
        // $localKey -> HouseExtra key
        return $this->belongsTo(User::class, 'id', 'house_id');
    }
}
