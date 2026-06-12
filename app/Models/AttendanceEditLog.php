<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class AttendanceEditLog extends Model
{
    use BelongsToSchool;

    protected $fillable = ['school_id', 'attendance_id', 'user_id', 'old_status', 'new_status', 'reason'];
}
