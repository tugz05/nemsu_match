<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContentReport extends Model
{
    protected $fillable = [
        'reporter_id', 'reportable_type', 'reportable_id',
        'reason', 'description', 'status',
        'review_notes', 'reviewed_by', 'reviewed_at',
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
    ];

    public function reporter()
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }

    public function reportable()
    {
        return $this->morphTo();
    }

    public static function reasons(): array
    {
        return [
            'spam'           => 'Spam',
            'harassment'     => 'Harassment',
            'hate_speech'    => 'Hate Speech',
            'inappropriate'  => 'Inappropriate Content',
            'misinformation' => 'Misinformation',
            'other'          => 'Other',
        ];
    }
}