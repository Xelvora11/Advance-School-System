<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class School extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'short_name',
        'logo_path',
        'email',
        'phone',
        'address',
        'city',
        'province',
        'status',
        'academic_year',
        'principal_name',
        'default_fee_due_day',
        'currency',
        'stamp_path',
        'signature_path',
        'primary_color',
        'secondary_color',
        'setup_completed',
        'plan_name',
        'manual_payment_status',
        'start_date',
        'end_date',
        'internal_payment_note',
        'internal_support_note',
        'account_status',
    ];

    protected function casts(): array
    {
        return [
            'setup_completed' => 'boolean',
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function admins(): HasMany
    {
        return $this->hasMany(User::class)->where('role', User::ROLE_SCHOOL_ADMIN);
    }

    public function students(): HasMany
    {
        return $this->hasMany(Student::class);
    }

    public function teachers(): HasMany
    {
        return $this->hasMany(Teacher::class);
    }

    public function classes(): HasMany
    {
        return $this->hasMany(SchoolClass::class);
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class);
    }

    public function internalNotes(): HasMany
    {
        return $this->hasMany(SchoolInternalNote::class);
    }

    public function loginHistories(): HasMany
    {
        return $this->hasMany(LoginHistory::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active' && in_array($this->account_status, ['active', 'trial'], true);
    }
}
