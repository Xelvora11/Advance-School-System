<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeacherSalaryPaymentEntry extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id',
        'salary_record_id',
        'teacher_id',
        'amount',
        'payment_method',
        'payment_date',
        'reference_number',
        'note',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'payment_date' => 'date',
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
