<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TeacherSalaryPayment extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id',
        'teacher_id',
        'salary_month',
        'salary_year',
        'gross_salary',
        'deductions',
        'deduction_reason',
        'paid_amount',
        'balance',
        'payment_status',
        'payment_method',
        'payment_date',
        'note',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'gross_salary' => 'decimal:2',
            'deductions' => 'decimal:2',
            'paid_amount' => 'decimal:2',
            'balance' => 'decimal:2',
            'payment_date' => 'date',
        ];
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function paymentEntries(): HasMany
    {
        return $this->hasMany(TeacherSalaryPaymentEntry::class, 'salary_record_id');
    }

    public function deductionEntries(): HasMany
    {
        return $this->hasMany(TeacherSalaryDeduction::class, 'salary_record_id');
    }

    public function payableAmount(): float
    {
        return max(0, (float) $this->gross_salary - (float) $this->deductions);
    }

    public function recalculate(): void
    {
        $this->balance = max(0, $this->payableAmount() - (float) $this->paid_amount);
        $this->payment_status = match (true) {
            $this->balance <= 0 => 'paid',
            (float) $this->paid_amount > 0 => 'partial',
            default => 'unpaid',
        };
    }

    public function refreshLedgerTotals(): void
    {
        $this->paid_amount = (float) $this->paymentEntries()->sum('amount');
        $this->deductions = (float) $this->deductionEntries()->sum('amount');
        $this->recalculate();

        $latestPayment = $this->paymentEntries()->latest('payment_date')->latest()->first();
        $this->payment_method = $latestPayment?->payment_method;
        $this->payment_date = $latestPayment?->payment_date;
    }
}
