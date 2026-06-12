<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StudentFee extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id',
        'student_id',
        'fee_head_id',
        'month',
        'year',
        'amount',
        'discount',
        'fine',
        'arrears',
        'paid_amount',
        'carried_forward_amount',
        'due_date',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'discount' => 'decimal:2',
            'fine' => 'decimal:2',
            'arrears' => 'decimal:2',
            'paid_amount' => 'decimal:2',
            'carried_forward_amount' => 'decimal:2',
            'due_date' => 'date',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function feeHead(): BelongsTo
    {
        return $this->belongsTo(FeeHead::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function fines(): HasMany
    {
        return $this->hasMany(StudentFine::class);
    }

    public function discounts(): HasMany
    {
        return $this->hasMany(StudentDiscount::class);
    }

    public function ledgerEntries(): HasMany
    {
        return $this->hasMany(StudentFeeLedgerEntry::class);
    }

    public function payableAmount(): float
    {
        return max(0, (float) $this->amount + (float) $this->fine + (float) $this->arrears - (float) $this->discount);
    }

    public function balance(): float
    {
        return max(0, $this->payableAmount() - (float) $this->paid_amount - (float) $this->carried_forward_amount);
    }

    public function recalculateStatus(): void
    {
        $balance = $this->balance();

        $this->status = match (true) {
            $balance <= 0 && (float) $this->carried_forward_amount > 0 && (float) $this->paid_amount < $this->payableAmount() => 'carried_forward',
            $balance <= 0 => 'paid',
            (float) $this->paid_amount > 0 => 'partial',
            $this->due_date && $this->due_date->lt(today()) => 'overdue',
            default => 'unpaid',
        };
    }

    public function updateStatusAndSave(): void
    {
        $this->recalculateStatus();
        $this->save();
    }
}
