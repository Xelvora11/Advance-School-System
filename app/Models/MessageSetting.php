<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class MessageSetting extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id',
        'allow_parent_to_admin_messages',
        'allow_parent_to_teacher_messages',
        'allow_teacher_to_parent_messages',
        'allow_student_messages',
        'allow_teacher_class_notices',
        'allow_attachments_in_messages',
        'message_attachment_max_size',
        'message_retention_days',
    ];

    protected function casts(): array
    {
        return [
            'allow_parent_to_admin_messages' => 'boolean',
            'allow_parent_to_teacher_messages' => 'boolean',
            'allow_teacher_to_parent_messages' => 'boolean',
            'allow_student_messages' => 'boolean',
            'allow_teacher_class_notices' => 'boolean',
            'allow_attachments_in_messages' => 'boolean',
        ];
    }

    public static function forSchoolId(int $schoolId): self
    {
        return self::firstOrCreate(
            ['school_id' => $schoolId],
            [
                'allow_parent_to_admin_messages' => true,
                'allow_parent_to_teacher_messages' => false,
                'allow_teacher_to_parent_messages' => false,
                'allow_student_messages' => false,
                'allow_teacher_class_notices' => false,
                'allow_attachments_in_messages' => false,
                'message_attachment_max_size' => 2048,
            ],
        );
    }
}
