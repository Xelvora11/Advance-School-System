<?php

namespace App\Http\Controllers;

use App\Models\Guardian;
use App\Models\Message;
use App\Models\MessageSetting;
use App\Models\MessageThread;
use App\Models\MessageThreadParticipant;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\TeacherAssignment;
use App\Models\User;
use App\Support\Activity;
use App\Support\SchoolContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class MessageController extends Controller
{
    public function index(Request $request): View
    {
        $user = SchoolContext::user();
        $query = MessageThread::with(['participants.user', 'latestMessage.sender'])
            ->where('school_id', SchoolContext::id())
            ->whereHas('participants', fn ($participant) => $participant->where('user_id', $user->id))
            ->latest('updated_at');

        if ($request->filled('search')) {
            $search = '%'.$request->search.'%';
            $query->where(function ($inner) use ($search) {
                $inner->where('subject', 'like', $search)
                    ->orWhereHas('messages', fn ($message) => $message->where('body', 'like', $search))
                    ->orWhereHas('participants.user', fn ($participant) => $participant->where('name', 'like', $search)->orWhere('email', 'like', $search));
            });
        }

        if ($request->filled('role')) {
            $query->whereHas('participants.user', fn ($participant) => $participant->where('role', $request->role));
        }

        $threads = $query->paginate(15)->withQueryString();
        $participantRows = MessageThreadParticipant::where('user_id', $user->id)
            ->whereIn('thread_id', $threads->getCollection()->pluck('id'))
            ->get()
            ->keyBy('thread_id');
        $unreadCounts = $threads->getCollection()
            ->mapWithKeys(function (MessageThread $thread) use ($participantRows, $user) {
                $participant = $participantRows->get($thread->id);
                $count = $thread->messages()
                    ->where('sender_id', '!=', $user->id)
                    ->when($participant?->last_read_at, fn ($query, $lastRead) => $query->where('created_at', '>', $lastRead))
                    ->count();

                return [$thread->id => $count];
            });

        return view('messages.index', [
            'threads' => $threads,
            'participantRows' => $participantRows,
            'unreadCounts' => $unreadCounts,
            'roles' => ['school_admin', 'principal', 'teacher', 'parent', 'student'],
        ]);
    }

    public function create(Request $request): View
    {
        $recipients = $this->allowedRecipients(SchoolContext::user());

        if ($request->filled('role')) {
            $recipients = $recipients->filter(fn (User $user) => $user->role === $request->role);
        }

        return view('messages.create', [
            'recipients' => $recipients->sortBy('name')->values(),
            'roles' => $this->availableRecipientRoles($recipients),
            'settings' => MessageSetting::forSchoolId(SchoolContext::id()),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = SchoolContext::user();
        $recipientIds = $this->allowedRecipients($user)->pluck('id')->all();
        $settings = MessageSetting::forSchoolId(SchoolContext::id());

        $validated = $request->validate([
            'recipient_id' => ['required', Rule::in($recipientIds)],
            'subject' => ['nullable', 'string', 'max:180'],
            'body' => ['required', 'string', 'max:5000'],
        ]);
        $attachment = $this->validateAttachment($request, $settings);

        $thread = DB::transaction(function () use ($attachment, $user, $validated) {
            $thread = MessageThread::create([
                'school_id' => SchoolContext::id(),
                'subject' => $validated['subject'] ?? null,
                'created_by' => $user->id,
            ]);

            MessageThreadParticipant::create([
                'thread_id' => $thread->id,
                'user_id' => $user->id,
                'last_read_at' => now(),
            ]);
            MessageThreadParticipant::create([
                'thread_id' => $thread->id,
                'user_id' => $validated['recipient_id'],
            ]);

            Message::create([
                'school_id' => SchoolContext::id(),
                'thread_id' => $thread->id,
                'sender_id' => $user->id,
                'body' => $validated['body'],
                'attachment_path' => $attachment['path'] ?? null,
                'attachment_original_name' => $attachment['name'] ?? null,
            ]);

            $thread->touch();

            return $thread;
        });

        Activity::log('message_sent', 'Internal message sent.', ['thread_id' => $thread->id]);

        return redirect()->route('messages.show', $thread)->with('status', 'Message sent successfully.');
    }

    public function show(MessageThread $message): View
    {
        $this->authorizeThread($message);

        $participant = $message->participants()->where('user_id', SchoolContext::user()->id)->firstOrFail();
        $messages = $message->messages()->with('sender')->oldest()->get();
        $participant->update(['last_read_at' => now()]);

        return view('messages.show', [
            'thread' => $message->load('participants.user'),
            'messages' => $messages,
            'settings' => MessageSetting::forSchoolId(SchoolContext::id()),
        ]);
    }

    public function reply(Request $request, MessageThread $message): RedirectResponse
    {
        $this->authorizeThread($message);
        $settings = MessageSetting::forSchoolId(SchoolContext::id());

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
        ]);
        $attachment = $this->validateAttachment($request, $settings);

        Message::create([
            'school_id' => SchoolContext::id(),
            'thread_id' => $message->id,
            'sender_id' => SchoolContext::user()->id,
            'body' => $validated['body'],
            'attachment_path' => $attachment['path'] ?? null,
            'attachment_original_name' => $attachment['name'] ?? null,
        ]);

        $message->touch();
        $message->participants()
            ->where('user_id', SchoolContext::user()->id)
            ->update(['last_read_at' => now()]);

        Activity::log('message_sent', 'Internal message reply sent.', ['thread_id' => $message->id]);

        return back()->with('status', 'Message sent successfully.');
    }

    private function authorizeThread(MessageThread $thread): void
    {
        abort_unless((int) $thread->school_id === SchoolContext::id(), 404);
        abort_unless($thread->participants()->where('user_id', SchoolContext::user()->id)->exists(), 404);
    }

    private function validateAttachment(Request $request, MessageSetting $settings): array
    {
        if (! $request->hasFile('attachment')) {
            return [];
        }

        if (! $settings->allow_attachments_in_messages) {
            throw ValidationException::withMessages([
                'attachment' => 'Attachments are disabled for school messages.',
            ]);
        }

        $request->validate([
            'attachment' => [
                'file',
                'mimes:pdf,jpg,jpeg,png,doc,docx',
                'max:'.$settings->message_attachment_max_size,
            ],
        ]);

        $file = $request->file('attachment');

        return [
            'path' => $file->store('message-attachments/'.SchoolContext::id(), 'public'),
            'name' => $file->getClientOriginalName(),
        ];
    }

    private function allowedRecipients(User $user): Collection
    {
        $settings = MessageSetting::forSchoolId(SchoolContext::id());
        $base = User::query()
            ->where('school_id', SchoolContext::id())
            ->where('id', '!=', $user->id)
            ->where('is_active', true);

        return match ($user->role) {
            User::ROLE_SCHOOL_ADMIN => (clone $base)
                ->whereIn('role', [User::ROLE_PRINCIPAL, User::ROLE_TEACHER, User::ROLE_PARENT, User::ROLE_STUDENT])
                ->get(),
            User::ROLE_PRINCIPAL => (clone $base)
                ->whereIn('role', [User::ROLE_SCHOOL_ADMIN, User::ROLE_TEACHER, User::ROLE_PARENT, User::ROLE_STUDENT])
                ->get(),
            User::ROLE_TEACHER => $this->teacherRecipients($user, $base, $settings),
            User::ROLE_PARENT => $this->parentRecipients($user, $base, $settings),
            User::ROLE_STUDENT => $this->studentRecipients($user, $base, $settings),
            default => collect(),
        };
    }

    private function teacherRecipients(User $user, $base, MessageSetting $settings): Collection
    {
        $recipients = (clone $base)
            ->whereIn('role', [User::ROLE_SCHOOL_ADMIN, User::ROLE_PRINCIPAL])
            ->get();

        if (! $settings->allow_teacher_to_parent_messages) {
            return $this->withStudentRecipientsForTeacher($user, $base, $settings, $recipients);
        }

        $studentIds = $this->studentIdsForTeacher($user);
        $parentIds = Guardian::forSchool(SchoolContext::id())
            ->whereNotNull('user_id')
            ->whereHas('students', fn ($query) => $query->whereIn('students.id', $studentIds))
            ->pluck('user_id');

        $recipients = $recipients->merge((clone $base)->whereIn('id', $parentIds)->get());

        return $this->withStudentRecipientsForTeacher($user, $base, $settings, $recipients);
    }

    private function parentRecipients(User $user, $base, MessageSetting $settings): Collection
    {
        $recipients = collect();

        if ($settings->allow_parent_to_admin_messages) {
            $recipients = $recipients->merge((clone $base)
                ->whereIn('role', [User::ROLE_SCHOOL_ADMIN, User::ROLE_PRINCIPAL])
                ->get());
        }

        if (! $settings->allow_parent_to_teacher_messages) {
            return $recipients->unique('id')->values();
        }

        $studentRows = Guardian::with('students')
            ->forSchool(SchoolContext::id())
            ->where('user_id', $user->id)
            ->get()
            ->flatMap(fn (Guardian $guardian) => $guardian->students);

        $classIds = $studentRows->pluck('school_class_id')->filter()->unique();
        $sectionIds = $studentRows->pluck('section_id')->filter()->unique();

        $teacherUserIds = TeacherAssignment::forSchool(SchoolContext::id())
            ->whereIn('school_class_id', $classIds)
            ->where(function ($query) use ($sectionIds) {
                $query->whereNull('section_id')->orWhereIn('section_id', $sectionIds);
            })
            ->whereHas('teacher', fn ($query) => $query->whereNotNull('user_id')->where('status', 'active'))
            ->with('teacher')
            ->get()
            ->pluck('teacher.user_id')
            ->filter()
            ->unique();

        return $recipients->merge((clone $base)->whereIn('id', $teacherUserIds)->get())->unique('id')->values();
    }

    private function studentRecipients(User $user, $base, MessageSetting $settings): Collection
    {
        if (! $settings->allow_student_messages) {
            return collect();
        }

        $student = Student::forSchool(SchoolContext::id())
            ->where('user_id', $user->id)
            ->first();

        if (! $student) {
            return collect();
        }

        $recipients = (clone $base)
            ->whereIn('role', [User::ROLE_SCHOOL_ADMIN, User::ROLE_PRINCIPAL])
            ->get();

        $teacherUserIds = TeacherAssignment::forSchool(SchoolContext::id())
            ->where('school_class_id', $student->school_class_id)
            ->where(function ($query) use ($student) {
                $query->whereNull('section_id')->orWhere('section_id', $student->section_id);
            })
            ->whereHas('teacher', fn ($query) => $query->whereNotNull('user_id')->where('status', 'active'))
            ->with('teacher')
            ->get()
            ->pluck('teacher.user_id')
            ->filter()
            ->unique();

        return $recipients->merge((clone $base)->whereIn('id', $teacherUserIds)->get())->unique('id')->values();
    }

    private function withStudentRecipientsForTeacher(User $user, $base, MessageSetting $settings, Collection $recipients): Collection
    {
        if (! $settings->allow_student_messages) {
            return $recipients->unique('id')->values();
        }

        $studentUserIds = Student::forSchool(SchoolContext::id())
            ->whereIn('id', $this->studentIdsForTeacher($user))
            ->whereNotNull('user_id')
            ->pluck('user_id');

        return $recipients->merge((clone $base)->whereIn('id', $studentUserIds)->get())->unique('id')->values();
    }

    private function studentIdsForTeacher(User $user): Collection
    {
        $teacher = Teacher::forSchool(SchoolContext::id())->where('user_id', $user->id)->first();

        if (! $teacher) {
            return collect();
        }

        $assignments = TeacherAssignment::forSchool(SchoolContext::id())
            ->where('teacher_id', $teacher->id)
            ->get();

        return $assignments->flatMap(function (TeacherAssignment $assignment) {
            return Student::forSchool(SchoolContext::id())
                ->where('school_class_id', $assignment->school_class_id)
                ->when($assignment->section_id, fn ($query) => $query->where('section_id', $assignment->section_id))
                ->pluck('id');
        })->unique()->values();
    }

    private function availableRecipientRoles(Collection $recipients): Collection
    {
        return $recipients
            ->pluck('role')
            ->unique()
            ->sort()
            ->values();
    }
}
