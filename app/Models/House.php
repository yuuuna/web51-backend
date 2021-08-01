<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class House extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'area_id',
        'title',
        'thumbnail_path',
        'price',
        'total_area',
        'public_area',
        'bedroom_count',
        'living_room_count',
        'dining_room_count',
        'kitchen_count',
        'license_date',
        'floor',
        'bathroom_count'
    ];

    public function user()
    {
        // $foreignKey -> User key
        // $localKey -> House key
        return $this->hasOne(User::class, 'id', 'user_id');
    }
}
