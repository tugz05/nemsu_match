<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Report extends Model
{
    use HasFactory;

    protected $guarded = [];

    // The person who submitted the report
    public function reporter()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // The person being reported
    public function reportedUser()
    {
        return $this->belongsTo(User::class, 'reported_user_id');
    }
}
