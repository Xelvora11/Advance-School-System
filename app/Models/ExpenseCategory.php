<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ExpenseCategory extends Model
{
    use BelongsToSchool;

    public const DEFAULTS = [
        'Salaries',
        'Electricity',
        'Internet',
        'Water',
        'Stationery',
        'Furniture',
        'Maintenance',
        'Marketing',
        'Event Expense',
        'Repairs',
        'Transport Expense',
        'Misc Expense',
    ];

    protected $fillable = [
        'school_id',
        'name',
        'slug',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public static function ensureDefaultsForSchool(int $schoolId): void
    {
        foreach (self::DEFAULTS as $name) {
            self::firstOrCreate(
                ['school_id' => $schoolId, 'slug' => Str::slug($name)],
                ['name' => $name, 'is_active' => true],
            );
        }
    }
}
