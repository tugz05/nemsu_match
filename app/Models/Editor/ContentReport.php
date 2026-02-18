<?php

namespace App\Models\Editor;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class ContentReport extends Model
{
    protected $fillable = [
        'reporter_id',
        'reportable_type',
        'reportable_id',
        'reason',
        'description',
        'status',
        'reviewed_by',
        'reviewed_at',
        'review_notes',
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
    ];

    public function reporter()
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function reportable()
    {
        return $this->morphTo();
    }

    public static function reasons(): array
    {
        return [
            'spam'          => 'Spam',
            'inappropriate' => 'Inappropriate Content',
            'harassment'    => 'Harassment / Bullying',
            'fake_profile'  => 'Fake Profile',
            'hate_speech'   => 'Hate Speech',
            'other'         => 'Other',
        ];
    }
}