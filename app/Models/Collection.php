<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Collection extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'house_id'
    ];

    public function user()
    {
        // $foreignKey -> User key
        // $localKey -> Collection key
        return $this->hasOne(User::class, 'id', 'user_id');
    }

    public function house()
    {
        // $foreignKey -> House key
        // $localKey -> Collection key
        return $this->hasOne(House::class, 'id', 'house_id');
    }

    public $timestamps = false;

    public static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->created_at = $model->freshTimestamp();
        });
    }
}
