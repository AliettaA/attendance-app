<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BreakTime extends Model
{
    use HasFactory;

    protected $fillable = [
        'attendance_id',
        'break_start_at',
        'break_end_at',
    ];

    public function attendance()
    {
        return $this->belongsTo(Attendance::class);
    }

    public function correctionRequestBreaks()
    {
        return $this->hasMany(CorrectionRequestBreak::class, 'original_break_time_id');
    }
}