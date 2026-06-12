<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeacherSalaryDeduction extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id',
        'salary_record_id',
        'teacher_id',
        'amount',
        'reason',
        'note',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
        ];
    }

    public function salaryRecord(): BelongsTo
    {
        return $this->belongsTo(TeacherSalaryPayment::class, 'salary_record_id');
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
