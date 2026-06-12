<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentDiscount extends Model
{
    use BelongsToSchool;

    public const TYPES = [
        'sibling_discount' => 'Sibling Discount',
        'scholarship' => 'Scholarship',
        'staff_child_discount' => 'Staff Child Discount',
        'orphan_support' => 'Orphan Support',
        'management_discount' => 'Special Management Discount',
        'partial_fee_waiver' => 'Partial Fee Waiver',
        'full_fee_waiver' => 'Full Fee Waiver',
    ];

    protected $fillable = [
        'school_id',
        'student_id',
        'student_fee_id',
        'discount_type',
        'amount',
        'reason',
        'approved_by',
        'discount_date',
        'note',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'discount_date' => 'date',
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
}
