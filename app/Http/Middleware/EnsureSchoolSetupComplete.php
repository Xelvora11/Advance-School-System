<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSchoolSetupComplete
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || $user->isSuperAdmin() || $user->role === 'parent') {
            return $next($request);
        }

        if ($user->school?->setup_completed) {
            return $next($request);
        }

        if ($user->role === 'school_admin') {
            return redirect()->route('school.setup.edit')
                ->withErrors(['setup' => 'Please complete school setup before using this module.']);
        }

        abort(403, 'School setup is not completed yet. Please contact the school admin.');
    }
}
