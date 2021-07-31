<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class House extends Model
{
    use HasFactory;

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
