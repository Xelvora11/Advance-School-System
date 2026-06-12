<?php

namespace App\Http\Controllers\School;

use App\Http\Controllers\Controller;
use App\Models\Guardian;
use App\Models\MessageThread;
use App\Models\MessageThreadParticipant;
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
use Illuminate\View\View;

class ParentManagementController extends Controller
{
    public function index(Request $request): View
    {
        $query = Guardian::with(['user', 'students.schoolClass', 'students.section'])
            ->forSchool(SchoolContext::id())
            ->latest();

        if ($request->filled('search')) {
            $search = '%'.trim((string) $request->search).'%';
            $query->where(function ($inner) use ($search) {
                $inner->where('name', 'like', $search)
                    ->orWhere('email', 'like', $search)
                    ->orWhere('phone', 'like', $search)
                    ->orWhere('whatsapp', 'like', $search)
                    ->orWhereHas('students', fn ($student) => $student->where('name', 'like', $search)->orWhere('registration_number', 'like', $search));
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        return view('school.parents.index', [
            'parents' => $query->paginate(15)->withQueryString(),
            'students' => Student::with(['schoolClass', 'section'])->forSchool(SchoolContext::id())->orderBy('name')->get(),
        ]);
    }

    public function show(Guardian $parent): View
    {
        $this->authorizeParent($parent);

        return view('school.parents.show', [
            'parent' => $parent->load(['user', 'students.schoolClass', 'students.section']),
            'students' => Student::with(['schoolClass', 'section'])->forSchool(SchoolContext::id())->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validatedParent($request);
        $studentIds = $validated['student_ids'] ?? [];
        unset($validated['student_ids']);

        $parent = DB::transaction(function () use ($request, $studentIds, $validated) {
            $parent = Guardian::create($validated + [
                'school_id' => SchoolContext::id(),
                'status' => $validated['status'] ?? 'active',
            ]);

            $this->syncStudents($parent, $studentIds, $request->input('relationship_type', 'guardian'));

            if ($request->boolean('create_login')) {
                $this->ensureLogin($parent, $request->input('password'));
            }

            return $parent;
        });

        Activity::log('parent_account_created', 'Parent profile created: '.$parent->name, ['parent_id' => $parent->id]);

        return redirect()->route('parents.show', $parent)->with('status', 'Parent profile saved.');
    }

    public function update(Request $request, Guardian $parent): RedirectResponse
    {
        $this->authorizeParent($parent);
        $validated = $this->validatedParent($request, $parent);
        $studentIds = $validated['student_ids'] ?? [];
        unset($validated['student_ids']);

        DB::transaction(function () use ($parent, $request, $studentIds, $validated) {
            $parent->update($validated);
            $parent->user?->update([
                'name' => $validated['name'],
                'email' => $validated['email'] ?: $parent->user?->email,
                'phone' => $validated['phone'] ?? null,
                'is_active' => ($validated['status'] ?? 'active') === 'active',
            ]);
            $this->syncStudents($parent, $studentIds, $request->input('relationship_type', 'guardian'));
        });

        return back()->with('status', 'Parent profile updated.');
    }

    public function createLogin(Request $request, Guardian $parent): RedirectResponse
    {
        $this->authorizeParent($parent);
        $request->validate(['password' => ['nullable', 'string', 'min:8']]);

        $password = DB::transaction(fn () => $this->ensureLogin($parent, $request->input('password')));

        Activity::log('parent_login_created', 'Parent login created or reset.', ['parent_id' => $parent->id]);

        return back()->with('status', 'Parent login ready. Temporary password: '.$password);
    }

    public function toggle(Guardian $parent): RedirectResponse
    {
        $this->authorizeParent($parent);

        $newStatus = $parent->status === 'active' ? 'inactive' : 'active';
        $parent->update(['status' => $newStatus]);
        $parent->user?->update(['is_active' => $newStatus === 'active']);

        Activity::log('parent_access_toggled', 'Parent access status changed.', ['parent_id' => $parent->id, 'status' => $newStatus]);

        return back()->with('status', 'Parent marked '.$newStatus.'.');
    }

    public function message(Guardian $parent): RedirectResponse
    {
        $this->authorizeParent($parent);
        abort_unless($parent->user_id, 404);

        $thread = DB::transaction(function () use ($parent) {
            $thread = MessageThread::create([
                'school_id' => SchoolContext::id(),
                'subject' => 'Message for '.$parent->name,
                'created_by' => SchoolContext::user()->id,
            ]);

            foreach ([SchoolContext::user()->id, $parent->user_id] as $userId) {
                MessageThreadParticipant::create([
                    'thread_id' => $thread->id,
                    'user_id' => $userId,
                    'last_read_at' => $userId === SchoolContext::user()->id ? now() : null,
                ]);
            }

            return $thread;
        });

        return redirect()->route('messages.show', $thread);
    }

    private function validatedParent(Request $request, ?Guardian $parent = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('users', 'email')->ignore($parent?->user_id)],
            'phone' => ['nullable', 'string', 'max:50'],
            'whatsapp' => ['nullable', 'string', 'max:50'],
            'cnic' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:1000'],
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
            'relationship_type' => ['nullable', Rule::in(['father', 'mother', 'guardian', 'other'])],
            'student_ids' => ['nullable', 'array'],
            'student_ids.*' => [Rule::exists('students', 'id')->where('school_id', SchoolContext::id())],
            'create_login' => ['nullable', 'boolean'],
            'password' => ['nullable', 'string', 'min:8'],
        ]);
    }

    private function syncStudents(Guardian $parent, array $studentIds, string $relationshipType): void
    {
        $sync = collect($studentIds)
            ->filter()
            ->unique()
            ->mapWithKeys(fn ($studentId) => [(int) $studentId => [
                'school_id' => SchoolContext::id(),
                'relationship_type' => $relationshipType ?: 'guardian',
                'is_primary' => false,
            ]])
            ->all();

        $parent->students()->sync($sync);

        foreach (array_keys($sync) as $studentId) {
            Activity::log('parent_linked_to_student', 'Parent linked to student.', ['parent_id' => $parent->id, 'student_id' => $studentId]);
        }
    }

    private function ensureLogin(Guardian $parent, ?string $password = null): string
    {
        $password = $password ?: Str::random(10).'A1';
        $email = $parent->email ?: 'parent-'.$parent->id.'-'.SchoolContext::id().'@school.local';
        $existingUser = User::where('email', $email)
            ->when($parent->user_id, fn ($query) => $query->where('id', '!=', $parent->user_id))
            ->first();

        if ($existingUser) {
            throw ValidationException::withMessages([
                'email' => 'This email is already used by another portal account.',
            ]);
        }

        $user = $parent->user ?: User::create([
            'school_id' => SchoolContext::id(),
            'name' => $parent->name,
            'email' => $email,
            'phone' => $parent->phone,
            'password' => Hash::make($password),
            'role' => User::ROLE_PARENT,
            'is_active' => $parent->status === 'active',
        ]);

        $user->update([
            'school_id' => SchoolContext::id(),
            'name' => $parent->name,
            'email' => $email,
            'phone' => $parent->phone,
            'password' => Hash::make($password),
            'role' => User::ROLE_PARENT,
            'is_active' => $parent->status === 'active',
        ]);

        $parent->update(['user_id' => $user->id]);

        return $password;
    }

    private function authorizeParent(Guardian $parent): void
    {
        abort_unless((int) $parent->school_id === SchoolContext::id(), 404);
    }
}
