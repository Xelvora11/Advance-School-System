<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class SchoolTemplateSetting extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id',
        'template_type',
        'header_text',
        'footer_text',
        'show_grading_table',
        'show_attendance_summary',
        'show_teacher_remarks',
        'show_principal_remarks',
        'paper_size',
        'orientation',
        'extra_settings',
    ];

    protected function casts(): array
    {
        return [
            'show_grading_table' => 'boolean',
            'show_attendance_summary' => 'boolean',
            'show_teacher_remarks' => 'boolean',
            'show_principal_remarks' => 'boolean',
            'extra_settings' => 'array',
        ];
    }
}
