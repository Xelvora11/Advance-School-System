<?php

namespace App\Support;

use App\Models\School;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class SchoolContext
{
    public static function user(): User
    {
        /** @var User $user */
        $user = Auth::user();

        return $user;
    }

    public static function id(): int
    {
        return (int) self::user()->school_id;
    }

    public static function school(): School
    {
        /** @var School $school */
        $school = self::user()->school;

        return $school;
    }
}
