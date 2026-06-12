<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Admission extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id',
        'requested_class_id',
        'converted_student_id',
        'student_name',
        'gender',
        'date_of_birth',
        'guardian_name',
        'guardian_phone',
        'address',
        'status',
        'test_notes',
        'document_checklist',
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'document_checklist' => 'array',
        ];
    }

    public function requestedClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'requested_class_id');
    }
}
