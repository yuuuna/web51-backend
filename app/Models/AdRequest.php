<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdRequest extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'house_id',
        'reviewer_id',
        'review_status',
        'reviewed_at'
    ];

    public function house()
    {
        // $foreignKey -> House key
        // $localKey -> AdRequest key
        return $this->hasOne(House::class, 'id', 'house_id');
    }

    public function reviewer()
    {
        // $foreignKey -> User key
        // $localKey -> AdRequest key
        return $this->hasOne(User::class, 'id', 'user_id');
    }
}
