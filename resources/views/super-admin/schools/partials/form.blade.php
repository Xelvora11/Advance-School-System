<div class="space-y-6">
    <section>
        <h2 class="text-lg font-semibold text-[#0B1F3A]">School Information</h2>
        <div class="mt-4 grid gap-4 md:grid-cols-2">
            <label class="block text-sm font-medium">School name
                <input name="name" value="{{ old('name', $school?->name) }}" class="mt-1 w-full rounded-md border-gray-300" required>
            </label>
            <label class="block text-sm font-medium">Short name
                <input name="short_name" value="{{ old('short_name', $school?->short_name) }}" class="mt-1 w-full rounded-md border-gray-300">
            </label>
            <label class="block text-sm font-medium">Logo
                <input type="file" name="logo" accept="image/*" class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2">
            </label>
            <label class="block text-sm font-medium">Academic year
                <input name="academic_year" value="{{ old('academic_year', $school?->academic_year) }}" class="mt-1 w-full rounded-md border-gray-300" placeholder="2026-2027">
            </label>
            <label class="block text-sm font-medium md:col-span-2">Address
                <textarea name="address" rows="3" class="mt-1 w-full rounded-md border-gray-300">{{ old('address', $school?->address) }}</textarea>
            </label>
            <label class="block text-sm font-medium">City
                <input name="city" value="{{ old('city', $school?->city) }}" class="mt-1 w-full rounded-md border-gray-300">
            </label>
            <label class="block text-sm font-medium">Province
                <input name="province" value="{{ old('province', $school?->province) }}" class="mt-1 w-full rounded-md border-gray-300">
            </label>
            <label class="block text-sm font-medium">Phone
                <input name="phone" value="{{ old('phone', $school?->phone) }}" class="mt-1 w-full rounded-md border-gray-300">
            </label>
            <label class="block text-sm font-medium">Email
                <input type="email" name="email" value="{{ old('email', $school?->email) }}" class="mt-1 w-full rounded-md border-gray-300">
            </label>
        </div>
    </section>

    <section class="border-t border-gray-200 pt-6">
        <h2 class="text-lg font-semibold text-[#0B1F3A]">Internal Manual Account Tracking</h2>
        <p class="mt-1 text-sm text-gray-500">Manual company-side plan and payment status only. No online checkout.</p>
        <div class="mt-4 grid gap-4 md:grid-cols-2">
            <label class="block text-sm font-medium">School status
                <select name="status" class="mt-1 w-full rounded-md border-gray-300">
                    @foreach (['active' => 'Active', 'inactive' => 'Inactive'] as $value => $label)
                        <option value="{{ $value }}" @selected(old('status', $school?->status ?? 'active') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>
            <label class="block text-sm font-medium">Account status
                <select name="account_status" class="mt-1 w-full rounded-md border-gray-300">
                    @php($selectedAccountStatus = old('account_status', $school?->account_status ?? 'active'))
                    @if ($selectedAccountStatus && ! array_key_exists($selectedAccountStatus, $accountStatuses))
                        <option value="{{ $selectedAccountStatus }}" selected>{{ Str::headline($selectedAccountStatus) }}</option>
                    @endif
                    @foreach ($accountStatuses as $value => $label)
                        <option value="{{ $value }}" @selected($selectedAccountStatus === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>
            <label class="block text-sm font-medium">Plan name
                <select name="plan_name" class="mt-1 w-full rounded-md border-gray-300">
                    <option value="">Select plan</option>
                    @foreach ($plans as $plan)
                        <option value="{{ $plan }}" @selected(old('plan_name', $school?->plan_name) === $plan)>{{ $plan }}</option>
                    @endforeach
                </select>
            </label>
            <label class="block text-sm font-medium">Manual payment status
                <select name="manual_payment_status" class="mt-1 w-full rounded-md border-gray-300" required>
                    @foreach ($paymentStatuses as $value => $label)
                        <option value="{{ $value }}" @selected(old('manual_payment_status', $school?->manual_payment_status ?? 'trial') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>
            <label class="block text-sm font-medium">Start date
                <input type="date" name="start_date" value="{{ old('start_date', optional($school?->start_date)->format('Y-m-d')) }}" class="mt-1 w-full rounded-md border-gray-300">
            </label>
            <label class="block text-sm font-medium">End date
                <input type="date" name="end_date" value="{{ old('end_date', optional($school?->end_date)->format('Y-m-d')) }}" class="mt-1 w-full rounded-md border-gray-300">
            </label>
            <label class="block text-sm font-medium md:col-span-2">Internal payment note
                <textarea name="internal_payment_note" rows="3" class="mt-1 w-full rounded-md border-gray-300">{{ old('internal_payment_note', $school?->internal_payment_note) }}</textarea>
            </label>
            <label class="block text-sm font-medium md:col-span-2">Internal support note
                <textarea name="internal_support_note" rows="3" class="mt-1 w-full rounded-md border-gray-300">{{ old('internal_support_note', $school?->internal_support_note) }}</textarea>
            </label>
        </div>
    </section>
</div>
