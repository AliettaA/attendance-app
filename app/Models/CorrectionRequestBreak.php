<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CorrectionRequestBreak extends Model
{
    use HasFactory;

    protected $fillable = [
        'correction_request_id',
        'original_break_time_id',
        'requested_break_start_at',
        'requested_break_end_at',
    ];

    public function correctionRequest()
    {
        return $this->belongsTo(CorrectionRequest::class);
    }

    public function originalBreakTime()
    {
        return $this->belongsTo(BreakTime::class, 'original_break_time_id');
    }
}