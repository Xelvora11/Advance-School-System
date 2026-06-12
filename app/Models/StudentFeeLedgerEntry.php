<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentFeeLedgerEntry extends Model
{
    use BelongsToSchool;

    public const TYPES = [
        'fee' => 'Fee',
        'payment' => 'Payment',
        'fine' => 'Fine',
        'discount' => 'Discount',
        'waiver' => 'Waiver',
        'adjustment' => 'Adjustment',
    ];

    protected $fillable = [
        'school_id',
        'student_id',
        'student_fee_id',
        'type',
        'description',
        'debit',
        'credit',
        'balance_after',
        'entry_date',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'debit' => 'decimal:2',
            'credit' => 'decimal:2',
            'balance_after' => 'decimal:2',
            'entry_date' => 'date',
        ];
    }

    public static function recordEntry(
        int $schoolId,
        int $studentId,
        ?int $studentFeeId,
        string $type,
        string $description,
        float $debit = 0,
        float $credit = 0,
        ?string $entryDate = null,
        ?int $createdBy = null,
    ): self {
        $lastBalance = (float) static::forSchool($schoolId)
            ->where('student_id', $studentId)
            ->latest('entry_date')
            ->latest('id')
            ->value('balance_after');

        $balanceAfter = max(0, $lastBalance + $debit - $credit);

        return static::create([
            'school_id' => $schoolId,
            'student_id' => $studentId,
            'student_fee_id' => $studentFeeId,
            'type' => $type,
            'description' => $description,
            'debit' => $debit,
            'credit' => $credit,
            'balance_after' => $balanceAfter,
            'entry_date' => $entryDate ?: today()->toDateString(),
            'created_by' => $createdBy,
        ]);
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
}
