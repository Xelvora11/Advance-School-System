<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\School;
use App\Models\User;
use App\Support\Activity;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class SchoolController extends Controller
{
    public function index(Request $request): View
    {
        $query = School::query()
            ->with(['admins' => fn ($query) => $query->oldest('id')])
            ->withCount(['students', 'teachers'])
            ->withMax('loginHistories', 'logged_in_at')
            ->latest();

        if ($request->filled('search')) {
            $query->where(function ($inner) use ($request) {
                $search = '%'.$request->search.'%';
                $inner->where('name', 'like', $search)
                    ->orWhere('city', 'like', $search)
                    ->orWhere('province', 'like', $search)
                    ->orWhere('email', 'like', $search)
                    ->orWhereHas('admins', function ($adminQuery) use ($search) {
                        $adminQuery->where('name', 'like', $search)
                            ->orWhere('email', 'like', $search)
                            ->orWhere('phone', 'like', $search);
                    });
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('account_status')) {
            $query->where('account_status', $request->account_status);
        }

        if ($request->filled('manual_payment_status')) {
            $query->where('manual_payment_status', $request->manual_payment_status);
        }

        if ($request->filled('plan_name')) {
            $query->where('plan_name', $request->plan_name);
        }

        return view('super-admin.schools.index', [
            'schools' => $query->paginate(15)->withQueryString(),
            'accountStatuses' => $this->accountStatuses(),
            'plans' => $this->planOptions(),
            'paymentStatuses' => $this->paymentStatuses(),
        ]);
    }

    public function create(): View
    {
        return view('super-admin.schools.create', [
            'accountStatuses' => $this->accountStatuses(),
            'plans' => $this->planOptions(),
            'paymentStatuses' => $this->paymentStatuses(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'short_name' => ['nullable', 'string', 'max:80'],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('schools', 'email')],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:1000'],
            'city' => ['nullable', 'string', 'max:100'],
            'province' => ['nullable', 'string', 'max:100'],
            'academic_year' => ['nullable', 'string', 'max:50'],
            'logo' => ['nullable', 'image', 'max:2048'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'account_status' => ['required', Rule::in($this->accountStatusValidationOptions())],
            'plan_name' => ['nullable', Rule::in($this->planOptions())],
            'manual_payment_status' => ['required', Rule::in(array_keys($this->paymentStatuses()))],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'internal_payment_note' => ['nullable', 'string', 'max:2000'],
            'internal_support_note' => ['nullable', 'string', 'max:2000'],
            'admin_name' => ['required', 'string', 'max:255'],
            'admin_email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'admin_phone' => ['nullable', 'string', 'max:50'],
            'admin_password' => ['required', 'string', 'min:8'],
        ]);

        $school = DB::transaction(function () use ($request, $validated) {
            $school = School::create([
                'name' => $validated['name'],
                'short_name' => $validated['short_name'] ?? null,
                'email' => $validated['email'] ?? null,
                'phone' => $validated['phone'] ?? null,
                'address' => $validated['address'] ?? null,
                'city' => $validated['city'] ?? null,
                'province' => $validated['province'] ?? null,
                'academic_year' => $validated['academic_year'] ?? null,
                'logo_path' => $request->hasFile('logo') ? $request->file('logo')->store('schools/logos', 'public') : null,
                'status' => $validated['status'],
                'account_status' => $validated['account_status'],
                'plan_name' => $validated['plan_name'] ?? null,
                'manual_payment_status' => $validated['manual_payment_status'],
                'start_date' => $validated['start_date'] ?? null,
                'end_date' => $validated['end_date'] ?? null,
                'internal_payment_note' => $validated['internal_payment_note'] ?? null,
                'internal_support_note' => $validated['internal_support_note'] ?? null,
            ]);

            User::create([
                'school_id' => $school->id,
                'name' => $validated['admin_name'],
                'email' => $validated['admin_email'],
                'phone' => $validated['admin_phone'] ?? null,
                'password' => Hash::make($validated['admin_password']),
                'role' => User::ROLE_SCHOOL_ADMIN,
                'is_active' => true,
            ]);

            return $school;
        });

        Activity::log('school_created', "School account created: {$school->name}", ['school_id' => $school->id], $school->id);

        return redirect()->route('super-admin.schools.show', $school)->with('status', 'School account created.');
    }

    public function show(School $school): View
    {
        return view('super-admin.schools.show', [
            'school' => $school->loadCount(['students', 'teachers', 'classes']),
            'admins' => $school->admins()->get(),
            'recentLogins' => $school->loginHistories()->with('user')->latest('logged_in_at')->take(8)->get(),
            'activityLogs' => $school->activityLogs()->with('user')->latest()->take(10)->get(),
            'internalNotes' => $school->internalNotes()->with('user')->latest()->paginate(8, ['*'], 'notes_page'),
            'paymentStatuses' => $this->paymentStatuses(),
        ]);
    }

    public function edit(School $school): View
    {
        return view('super-admin.schools.edit', [
            'school' => $school,
            'accountStatuses' => $this->accountStatuses(),
            'plans' => $this->planOptions(),
            'paymentStatuses' => $this->paymentStatuses(),
        ]);
    }

    public function update(Request $request, School $school): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'short_name' => ['nullable', 'string', 'max:80'],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('schools', 'email')->ignore($school->id)],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:1000'],
            'city' => ['nullable', 'string', 'max:100'],
            'province' => ['nullable', 'string', 'max:100'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'account_status' => ['required', Rule::in($this->accountStatusValidationOptions())],
            'academic_year' => ['nullable', 'string', 'max:50'],
            'logo' => ['nullable', 'image', 'max:2048'],
            'plan_name' => ['nullable', Rule::in($this->planOptions())],
            'manual_payment_status' => ['required', Rule::in(array_keys($this->paymentStatuses()))],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'internal_payment_note' => ['nullable', 'string', 'max:2000'],
            'internal_support_note' => ['nullable', 'string', 'max:2000'],
        ]);

        if ($request->hasFile('logo')) {
            if ($school->logo_path) {
                Storage::disk('public')->delete($school->logo_path);
            }

            $validated['logo_path'] = $request->file('logo')->store('schools/logos', 'public');
        }

        $school->update($validated);
        Activity::log('school_updated', "School account updated: {$school->name}", ['school_id' => $school->id], $school->id);

        return redirect()->route('super-admin.schools.show', $school)->with('status', 'School updated.');
    }

    public function toggleStatus(School $school): RedirectResponse
    {
        $newStatus = $school->isActive() ? 'inactive' : 'active';
        $newAccountStatus = $newStatus === 'active' ? 'active' : 'suspended';

        $school->update([
            'status' => $newStatus,
            'account_status' => $newAccountStatus,
        ]);

        Activity::log(
            $newStatus === 'active' ? 'school_activated' : 'school_deactivated',
            "School {$newStatus}: {$school->name}",
            ['school_id' => $school->id, 'status' => $newStatus, 'account_status' => $newAccountStatus],
            $school->id,
        );

        return back()->with('status', $newStatus === 'active' ? 'School activated.' : 'School deactivated.');
    }

    public function storeNote(Request $request, School $school): RedirectResponse
    {
        $validated = $request->validate([
            'note' => ['required', 'string', 'max:2000'],
        ]);

        $note = $school->internalNotes()->create([
            'user_id' => auth()->id(),
            'note' => $validated['note'],
        ]);

        Activity::log('school_internal_note_added', 'Internal Note Added for '.$school->name, [
            'school_id' => $school->id,
            'note_id' => $note->id,
        ], $school->id);

        return back()->with('status', 'Internal note added.');
    }

    public function resetAdminPassword(Request $request, School $school, User $user): RedirectResponse
    {
        abort_unless($user->school_id === $school->id && $user->role === User::ROLE_SCHOOL_ADMIN, 404);

        $validated = $request->validate([
            'auto_generate' => ['nullable', 'boolean'],
            'password' => ['nullable', 'string', 'min:8'],
        ]);

        $generatedPassword = null;
        $password = $validated['password'] ?? null;

        if ($request->boolean('auto_generate')) {
            $generatedPassword = Str::random(12).'A1';
            $password = $generatedPassword;
        }

        if (! $password) {
            throw ValidationException::withMessages([
                'password' => 'Enter a new password or choose auto-generate.',
            ]);
        }

        $user->update(['password' => Hash::make($password)]);
        Activity::log('school_admin_password_reset', "Admin password reset for {$user->email}", ['school_id' => $school->id, 'admin_user_id' => $user->id, 'auto_generated' => (bool) $generatedPassword], $school->id);

        $redirect = back()->with('status', 'School admin password reset.');

        if ($generatedPassword) {
            $redirect->with('generated_password', $generatedPassword);
        }

        return $redirect;
    }

    private function accountStatuses(): array
    {
        return [
            'active' => 'Active',
            'suspended' => 'Suspended',
            'trial' => 'Trial',
        ];
    }

    private function accountStatusValidationOptions(): array
    {
        return [...array_keys($this->accountStatuses()), 'inactive'];
    }

    private function planOptions(): array
    {
        return ['Trial', 'Starter', 'Standard', 'Premium', 'Custom'];
    }

    private function paymentStatuses(): array
    {
        return [
            'paid' => 'Paid',
            'pending' => 'Pending',
            'overdue' => 'Overdue',
            'trial' => 'Trial',
        ];
    }
}
