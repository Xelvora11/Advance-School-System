<?php

namespace App\Http\Controllers\School;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\StudentFee;
use App\Models\StudentFeeLedgerEntry;
use App\Models\StudentFine;
use App\Support\Activity;
use App\Support\SchoolContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class StudentFineController extends Controller
{
    public function store(Request $request, ?Student $student = null): RedirectResponse
    {
        if ($student) {
            $this->authorizeStudent($student);
        }

        $validated = $this->validated($request, $student);
        $student = $student ?: Student::forSchool(SchoolContext::id())->findOrFail($validated['student_id']);
        $studentFee = $this->linkedFee($validated['student_fee_id'] ?? null, $student);

        $fine = DB::transaction(function () use ($student, $studentFee, $validated) {
            $fine = StudentFine::create([
                'school_id' => SchoolContext::id(),
                'student_id' => $student->id,
                'student_fee_id' => $studentFee?->id,
                'fine_type' => $validated['fine_type'],
                'title' => $validated['title'],
                'amount' => $validated['amount'],
                'fine_date' => $validated['fine_date'],
                'due_date' => $validated['due_date'] ?? null,
                'status' => 'unpaid',
                'note' => $validated['note'] ?? null,
                'created_by' => SchoolContext::user()->id,
            ]);

            $this->syncLinkedFee($fine);
            StudentFeeLedgerEntry::recordEntry(
                SchoolContext::id(),
                $student->id,
                $studentFee?->id,
                'fine',
                $fine->title,
                (float) $fine->amount,
                0,
                $fine->fine_date->toDateString(),
                SchoolContext::user()->id,
            );

            return $fine;
        });

        Activity::log('student_fine_added', 'Student fine added for '.$student->name, ['student_id' => $student->id, 'fine_id' => $fine->id]);
        Activity::log('student_balance_recalculated', 'Student balance recalculated after fine.', ['student_id' => $student->id]);
        Activity::log('student_ledger_updated', 'Student fee ledger updated after fine.', ['student_id' => $student->id, 'fine_id' => $fine->id]);

        return back()->with('status', 'Student fine added.');
    }

    public function update(Request $request, StudentFine $studentFine): RedirectResponse
    {
        $this->authorizeFine($studentFine);

        $validated = $this->validated($request, $studentFine->student, $studentFine);
        $studentFee = $this->linkedFee($validated['student_fee_id'] ?? null, $studentFine->student);

        DB::transaction(function () use ($studentFine, $studentFee, $validated) {
            $oldAmount = (float) $studentFine->amount;
            $this->removeLinkedFine($studentFine);
            $studentFine->fill([
                'student_fee_id' => $studentFee?->id,
                'fine_type' => $validated['fine_type'],
                'title' => $validated['title'],
                'amount' => $validated['amount'],
                'fine_date' => $validated['fine_date'],
                'due_date' => $validated['due_date'] ?? null,
                'note' => $validated['note'] ?? null,
            ]);
            $studentFine->save();
            $this->syncLinkedFee($studentFine);

            $difference = (float) $studentFine->amount - $oldAmount;

            if ($studentFine->status === 'unpaid' && abs($difference) > 0.009) {
                StudentFeeLedgerEntry::recordEntry(
                    SchoolContext::id(),
                    $studentFine->student_id,
                    $studentFee?->id,
                    'adjustment',
                    'Fine adjusted: '.$studentFine->title,
                    $difference > 0 ? $difference : 0,
                    $difference < 0 ? abs($difference) : 0,
                    $studentFine->fine_date->toDateString(),
                    SchoolContext::user()->id,
                );
            }
        });

        Activity::log('student_fine_edited', 'Student fine edited.', ['fine_id' => $studentFine->id]);
        Activity::log('student_balance_recalculated', 'Student balance recalculated after fine edit.', ['student_id' => $studentFine->student_id]);
        Activity::log('student_ledger_updated', 'Student fee ledger updated after fine edit.', ['student_id' => $studentFine->student_id, 'fine_id' => $studentFine->id]);

        return back()->with('status', 'Student fine updated.');
    }

    public function markPaid(StudentFine $studentFine): RedirectResponse
    {
        $this->authorizeFine($studentFine);

        DB::transaction(function () use ($studentFine) {
            $this->removeLinkedFine($studentFine);
            $studentFine->update(['status' => 'paid']);
            StudentFeeLedgerEntry::recordEntry(
                SchoolContext::id(),
                $studentFine->student_id,
                $studentFine->student_fee_id,
                'payment',
                'Fine paid: '.$studentFine->title,
                0,
                (float) $studentFine->amount,
                today()->toDateString(),
                SchoolContext::user()->id,
            );
        });

        Activity::log('student_fine_paid', 'Student fine marked paid.', ['fine_id' => $studentFine->id]);
        Activity::log('student_balance_recalculated', 'Student balance recalculated after fine payment.', ['student_id' => $studentFine->student_id]);
        Activity::log('student_ledger_updated', 'Student fee ledger updated after fine payment.', ['student_id' => $studentFine->student_id, 'fine_id' => $studentFine->id]);

        return back()->with('status', 'Fine marked paid.');
    }

    public function waive(StudentFine $studentFine): RedirectResponse
    {
        $this->authorizeFine($studentFine);

        DB::transaction(function () use ($studentFine) {
            $this->removeLinkedFine($studentFine);
            $studentFine->update(['status' => 'waived']);
            StudentFeeLedgerEntry::recordEntry(
                SchoolContext::id(),
                $studentFine->student_id,
                $studentFine->student_fee_id,
                'waiver',
                'Fine waived: '.$studentFine->title,
                0,
                (float) $studentFine->amount,
                today()->toDateString(),
                SchoolContext::user()->id,
            );
        });

        Activity::log('student_fine_waived', 'Student fine waived.', ['fine_id' => $studentFine->id]);
        Activity::log('student_balance_recalculated', 'Student balance recalculated after fine waiver.', ['student_id' => $studentFine->student_id]);
        Activity::log('student_ledger_updated', 'Student fee ledger updated after fine waiver.', ['student_id' => $studentFine->student_id, 'fine_id' => $studentFine->id]);

        return back()->with('status', 'Fine waived.');
    }

    public function destroy(StudentFine $studentFine): RedirectResponse
    {
        $this->authorizeFine($studentFine);

        if ($studentFine->status === 'paid') {
            throw ValidationException::withMessages([
                'fine' => 'Paid fines cannot be deleted. Waive or edit only unpaid fine records.',
            ]);
        }

        $studentId = $studentFine->student_id;

        DB::transaction(function () use ($studentFine) {
            $amount = (float) $studentFine->amount;
            $studentId = $studentFine->student_id;
            $studentFeeId = $studentFine->student_fee_id;
            $title = $studentFine->title;
            $status = $studentFine->status;
            $this->removeLinkedFine($studentFine);
            $studentFine->delete();

            if ($status === 'unpaid') {
                StudentFeeLedgerEntry::recordEntry(
                    SchoolContext::id(),
                    $studentId,
                    $studentFeeId,
                    'adjustment',
                    'Fine deleted: '.$title,
                    0,
                    $amount,
                    today()->toDateString(),
                    SchoolContext::user()->id,
                );
            }
        });

        Activity::log('student_fine_deleted', 'Student fine deleted.', ['student_id' => $studentId]);
        Activity::log('student_balance_recalculated', 'Student balance recalculated after fine deletion.', ['student_id' => $studentId]);
        Activity::log('student_ledger_updated', 'Student fee ledger updated after fine deletion.', ['student_id' => $studentId]);

        return back()->with('status', 'Fine deleted.');
    }

    private function validated(Request $request, ?Student $student = null, ?StudentFine $fine = null): array
    {
        $schoolId = SchoolContext::id();

        return $request->validate([
            'student_id' => [$student ? 'nullable' : 'required', Rule::exists('students', 'id')->where('school_id', $schoolId)],
            'student_fee_id' => ['nullable', Rule::exists('student_fees', 'id')->where('school_id', $schoolId)],
            'fine_type' => ['required', Rule::in(array_keys(StudentFine::TYPES))],
            'title' => ['required', 'string', 'max:160'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'fine_date' => ['required', 'date'],
            'due_date' => ['nullable', 'date'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);
    }

    private function linkedFee(?int $studentFeeId, Student $student): ?StudentFee
    {
        if (! $studentFeeId) {
            return null;
        }

        $fee = StudentFee::forSchool(SchoolContext::id())->findOrFail($studentFeeId);

        if ((int) $fee->student_id !== (int) $student->id) {
            throw ValidationException::withMessages([
                'student_fee_id' => 'Selected fee record does not belong to this student.',
            ]);
        }

        return $fee;
    }

    private function syncLinkedFee(StudentFine $fine): void
    {
        if ($fine->status !== 'unpaid' || ! $fine->student_fee_id) {
            return;
        }

        $fee = $fine->studentFee()->lockForUpdate()->first();

        if (! $fee) {
            return;
        }

        $fee->fine = (float) $fee->fine + (float) $fine->amount;
        $fee->notes = trim(($fee->notes ? $fee->notes."\n" : '').'Fine added: '.$fine->title);
        $fee->updateStatusAndSave();

        $fine->forceFill(['applied_amount' => $fine->amount])->save();

        Activity::log('fee_balance_updated_due_to_fine', 'Fee balance updated due to linked fine.', ['student_fee_id' => $fee->id, 'fine_id' => $fine->id]);
    }

    private function removeLinkedFine(StudentFine $fine): void
    {
        if (! $fine->student_fee_id || (float) $fine->applied_amount <= 0) {
            return;
        }

        $fee = $fine->studentFee()->lockForUpdate()->first();

        if ($fee) {
            $fee->fine = max(0, (float) $fee->fine - (float) $fine->applied_amount);
            $fee->updateStatusAndSave();
            Activity::log('fee_balance_updated_due_to_fine', 'Fee balance updated after fine status change.', ['student_fee_id' => $fee->id, 'fine_id' => $fine->id]);
        }

        $fine->forceFill(['applied_amount' => 0])->save();
    }

    private function authorizeStudent(Student $student): void
    {
        abort_unless((int) $student->school_id === SchoolContext::id(), 404);
    }

    private function authorizeFine(StudentFine $fine): void
    {
        abort_unless((int) $fine->school_id === SchoolContext::id(), 404);
    }
}
