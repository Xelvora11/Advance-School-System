<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentDocument extends Model
{
    use BelongsToSchool;

    protected $fillable = ['school_id', 'student_id', 'title', 'file_path'];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
