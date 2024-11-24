<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Death extends Model
{
    protected $fillable = [
        'name', 
        'start_date', 
        'end_date', 
        'profession', 
        'status',  // "pending", "approved", etc.
        'user_id', // Foreign key to associate with the User model
    ];

    /**
     * Define the inverse relationship between Death and User.
     */
    public function user()
    {
        return $this->belongsTo(User::class); // A death record belongs to a single user
    }
}
