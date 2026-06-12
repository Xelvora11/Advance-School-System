<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class FeeHead extends Model
{
    use BelongsToSchool;

    public const CATEGORY_TYPES = [
        'monthly_fee' => 'Monthly Fee',
        'one_time_fee' => 'One-Time Fee',
        'annual_fee' => 'Annual Fee',
        'fine_penalty' => 'Fine/Penalty',
        'discount_waiver' => 'Discount/Waiver',
        'other' => 'Other',
    ];

    protected $fillable = ['school_id', 'name', 'code', 'category_type', 'default_amount', 'frequency', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }
}
