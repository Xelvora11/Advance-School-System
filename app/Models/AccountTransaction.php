<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

class AccountTransaction extends Model
{
    use BelongsToSchool;

    public const TYPE_INCOME = 'income';
    public const TYPE_EXPENSE = 'expense';

    protected $fillable = [
        'school_id',
        'transaction_date',
        'reference_no',
        'type',
        'category',
        'source',
        'description',
        'income_amount',
        'expense_amount',
        'balance',
        'reference_type',
        'reference_id',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'transaction_date' => 'date',
            'income_amount' => 'decimal:2',
            'expense_amount' => 'decimal:2',
            'balance' => 'decimal:2',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public static function recordIncome(
        int $schoolId,
        string $category,
        string $description,
        float $amount,
        string $date,
        ?int $createdBy = null,
        ?string $notes = null,
        ?string $source = null,
        ?string $referenceType = null,
        ?int $referenceId = null,
    ): self {
        return self::recordTransaction(
            $schoolId,
            self::TYPE_INCOME,
            $category,
            $description,
            $amount,
            $date,
            $createdBy,
            $notes,
            $source,
            $referenceType,
            $referenceId,
        );
    }

    public static function recordExpense(
        int $schoolId,
        string $category,
        string $description,
        float $amount,
        string $date,
        ?int $createdBy = null,
        ?string $notes = null,
        ?string $source = null,
        ?string $referenceType = null,
        ?int $referenceId = null,
    ): self {
        return self::recordTransaction(
            $schoolId,
            self::TYPE_EXPENSE,
            $category,
            $description,
            $amount,
            $date,
            $createdBy,
            $notes,
            $source,
            $referenceType,
            $referenceId,
        );
    }

    public static function currentBalance(int $schoolId): float
    {
        $transaction = self::forSchool($schoolId)
            ->orderByDesc('transaction_date')
            ->orderByDesc('id')
            ->first();

        return (float) ($transaction?->balance ?? 0);
    }

    public function direction(): string
    {
        return $this->type === self::TYPE_INCOME ? 'In' : 'Out';
    }

    private static function recordTransaction(
        int $schoolId,
        string $type,
        string $category,
        string $description,
        float $amount,
        string $date,
        ?int $createdBy,
        ?string $notes,
        ?string $source,
        ?string $referenceType,
        ?int $referenceId,
    ): self {
        if ($amount <= 0) {
            throw new InvalidArgumentException('Account transaction amount must be greater than zero.');
        }

        if (! in_array($type, [self::TYPE_INCOME, self::TYPE_EXPENSE], true)) {
            throw new InvalidArgumentException('Invalid account transaction type.');
        }

        $transactionDate = Carbon::parse($date)->toDateString();
        $payload = [
            'school_id' => $schoolId,
            'transaction_date' => $transactionDate,
            'type' => $type,
            'category' => $category,
            'source' => $source,
            'description' => $description,
            'income_amount' => $type === self::TYPE_INCOME ? $amount : 0,
            'expense_amount' => $type === self::TYPE_EXPENSE ? $amount : 0,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'notes' => $notes,
            'created_by' => $createdBy,
        ];

        return DB::transaction(function () use ($schoolId, $type, $referenceType, $referenceId, $payload) {
            $transaction = null;

            if ($referenceType && $referenceId) {
                $transaction = self::where([
                    'school_id' => $schoolId,
                    'type' => $type,
                    'reference_type' => $referenceType,
                    'reference_id' => $referenceId,
                ])->first();
            }

            if ($transaction) {
                $transaction->fill($payload);
                $transaction->save();
            } else {
                $transaction = self::create($payload + [
                    'reference_no' => self::temporaryReferenceNo(),
                ]);
                $transaction->forceFill([
                    'reference_no' => self::finalReferenceNo($transaction),
                ])->saveQuietly();
            }

            self::refreshBalances($schoolId);

            return $transaction->refresh();
        });
    }

    private static function refreshBalances(int $schoolId): void
    {
        $runningBalance = 0.0;

        self::forSchool($schoolId)
            ->orderBy('transaction_date')
            ->orderBy('id')
            ->get()
            ->each(function (self $transaction) use (&$runningBalance) {
                $runningBalance += (float) $transaction->income_amount - (float) $transaction->expense_amount;
                $roundedBalance = round($runningBalance, 2);

                if ((float) $transaction->balance !== $roundedBalance) {
                    $transaction->forceFill(['balance' => $roundedBalance])->saveQuietly();
                }
            });
    }

    private static function temporaryReferenceNo(): string
    {
        return 'ACC-PENDING-'.Str::upper(Str::random(10));
    }

    private static function finalReferenceNo(self $transaction): string
    {
        return 'ACC-'.$transaction->transaction_date->format('Ymd').'-'.str_pad((string) $transaction->id, 5, '0', STR_PAD_LEFT);
    }
}
