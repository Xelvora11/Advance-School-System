<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentFine extends Model
{
    use BelongsToSchool;

    public const TYPES = [
        'late_fee' => 'Late Fee Payment Fine',
        'discipline' => 'Discipline',
        'damage' => 'Damage',
        'lost_book' => 'Lost Book',
        'absence' => 'Absence Fine',
        'late_arrival' => 'Late Arrival Fine',
        'exam' => 'Exam',
        'id_card' => 'ID Card Reprint Fine',
        'other' => 'Other',
    ];

    protected $fillable = [
        'school_id',
        'student_id',
        'student_fee_id',
        'fine_type',
        'title',
        'amount',
        'applied_amount',
        'fine_date',
        'due_date',
        'status',
        'note',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'applied_amount' => 'decimal:2',
            'fine_date' => 'date',
            'due_date' => 'date',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function studentFee(): BelongsTo
    {
        return $this->belongsTo(StudentFee::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isOutstanding(): bool
    {
        return $this->status === 'unpaid';
    }
}
