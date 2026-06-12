<?php

namespace App\Http\Controllers\School;

use App\Http\Controllers\Controller;
use App\Models\Guardian;
use App\Models\Student;
use App\Models\User;
use App\Support\Activity;
use App\Support\SchoolContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class StudentAccessController extends Controller
{
    public function createParentLogin(Request $request, Student $student): RedirectResponse
    {
        $this->authorizeStudent($student);

        $validated = $request->validate([
            'parent_id' => ['nullable', Rule::exists('parents', 'id')->where('school_id', SchoolContext::id())],
            'relationship_type' => ['nullable', Rule::in(['father', 'mother', 'guardian', 'other'])],
            'password' => ['nullable', 'string', 'min:8'],
        ]);

        $password = $validated['password'] ?? Str::random(10).'A1';

        DB::transaction(function () use ($password, $student, $validated) {
            $parent = ! empty($validated['parent_id'])
                ? Guardian::forSchool(SchoolContext::id())->findOrFail($validated['parent_id'])
                : ($this->matchingGuardian($student) ?: new Guardian(['school_id' => SchoolContext::id()]));

            $parent->fill([
                'name' => $parent->name ?: ($student->guardian_name ?: $student->name.' Guardian'),
                'phone' => $parent->phone ?: $student->guardian_phone,
                'whatsapp' => $parent->whatsapp ?: $student->guardian_whatsapp,
                'email' => $parent->email ?: $student->guardian_email,
                'cnic' => $parent->cnic ?: $student->guardian_cnic,
                'address' => $parent->address ?: ($student->guardian_address ?: $student->address),
                'status' => 'active',
            ])->save();

            $email = $parent->email ?: 'parent-'.$parent->id.'-'.SchoolContext::id().'@school.local';
            $user = $parent->user ?: User::where('email', $email)->first();

            if ($user && ((int) $user->school_id !== SchoolContext::id() || $user->role !== User::ROLE_PARENT)) {
                throw ValidationException::withMessages([
                    'parent_id' => 'This parent email is already used by another portal account.',
                ]);
            }

            $user = $user ?: User::create([
                'school_id' => SchoolContext::id(),
                'name' => $parent->name,
                'email' => $email,
                'phone' => $parent->phone,
                'password' => Hash::make($password),
                'role' => User::ROLE_PARENT,
                'is_active' => true,
            ]);

            $user->update([
                'name' => $parent->name,
                'phone' => $parent->phone,
                'password' => Hash::make($password),
                'role' => User::ROLE_PARENT,
                'is_active' => true,
            ]);

            $parent->update(['user_id' => $user->id]);
            $parent->students()->syncWithoutDetaching([
                $student->id => [
                    'school_id' => SchoolContext::id(),
                    'relationship_type' => $validated['relationship_type'] ?? 'guardian',
                    'is_primary' => true,
                ],
            ]);
        });

        Activity::log('parent_login_created', 'Parent login created from student profile.', ['student_id' => $student->id]);

        return back()->with('status', 'Parent login ready. Temporary password: '.$password);
    }

    public function linkParent(Request $request, Student $student): RedirectResponse
    {
        $this->authorizeStudent($student);
        $validated = $request->validate([
            'parent_id' => ['required', Rule::exists('parents', 'id')->where('school_id', SchoolContext::id())],
            'relationship_type' => ['nullable', Rule::in(['father', 'mother', 'guardian', 'other'])],
        ]);

        $parent = Guardian::forSchool(SchoolContext::id())->findOrFail($validated['parent_id']);
        $parent->students()->syncWithoutDetaching([
            $student->id => [
                'school_id' => SchoolContext::id(),
                'relationship_type' => $validated['relationship_type'] ?? 'guardian',
                'is_primary' => false,
            ],
        ]);

        Activity::log('parent_linked_to_student', 'Existing parent linked from student profile.', ['parent_id' => $parent->id, 'student_id' => $student->id]);

        return back()->with('status', 'Parent linked to student.');
    }

    public function createStudentLogin(Request $request, Student $student): RedirectResponse
    {
        $this->authorizeStudent($student);
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($student->user_id)],
            'password' => ['nullable', 'string', 'min:8'],
        ]);

        $password = $validated['password'] ?? Str::random(10).'A1';

        DB::transaction(function () use ($password, $student, $validated) {
            $user = $student->user ?: new User;
            $user->fill([
                'school_id' => SchoolContext::id(),
                'name' => $student->name,
                'email' => $validated['email'],
                'phone' => $student->guardian_phone,
                'password' => Hash::make($password),
                'role' => User::ROLE_STUDENT,
                'is_active' => true,
            ])->save();

            $student->update(['user_id' => $user->id]);
        });

        Activity::log('student_login_created', 'Student login created.', ['student_id' => $student->id]);

        return back()->with('status', 'Student login ready. Temporary password: '.$password);
    }

    public function resetStudentPassword(Request $request, Student $student): RedirectResponse
    {
        $this->authorizeStudent($student);
        abort_unless($student->user, 404);
        $request->validate(['password' => ['nullable', 'string', 'min:8']]);
        $password = $request->input('password') ?: Str::random(10).'A1';

        $student->user->update([
            'password' => Hash::make($password),
            'is_active' => true,
        ]);

        Activity::log('student_password_reset', 'Student password reset.', ['student_id' => $student->id]);

        return back()->with('status', 'Student password reset. Temporary password: '.$password);
    }

    public function disableStudentAccess(Student $student): RedirectResponse
    {
        $this->authorizeStudent($student);
        $student->user?->update(['is_active' => false]);

        Activity::log('student_access_disabled', 'Student access disabled.', ['student_id' => $student->id]);

        return back()->with('status', 'Student access disabled.');
    }

    public function disableParentAccess(Student $student, Guardian $parent): RedirectResponse
    {
        $this->authorizeStudent($student);
        abort_unless((int) $parent->school_id === SchoolContext::id(), 404);

        $parent->user?->update(['is_active' => false]);

        Activity::log('parent_access_disabled', 'Parent access disabled from student profile.', ['student_id' => $student->id, 'parent_id' => $parent->id]);

        return back()->with('status', 'Parent access disabled.');
    }

    private function authorizeStudent(Student $student): void
    {
        abort_unless((int) $student->school_id === SchoolContext::id(), 404);
    }

    private function matchingGuardian(Student $student): ?Guardian
    {
        $query = Guardian::forSchool(SchoolContext::id());

        if ($student->guardian_email) {
            return (clone $query)->where('email', $student->guardian_email)->first();
        }

        if ($student->guardian_phone) {
            return (clone $query)->where('phone', $student->guardian_phone)->first();
        }

        if ($student->guardian_cnic) {
            return (clone $query)->where('cnic', $student->guardian_cnic)->first();
        }

        return null;
    }
}
