<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CorrectionRequestBreak extends Model
{
    use HasFactory;

    protected $fillable = [
        'correction_request_id',
        'original_break_time_id',
        'requested_break_start_at',
        'requested_break_end_at',
    ];

    public function correctionRequest(): BelongsTo
    {
        return $this->belongsTo(CorrectionRequest::class);
    }

    public function originalBreakTime(): BelongsTo
    {
        return $this->belongsTo(BreakTime::class, 'original_break_time_id');
    }
}
