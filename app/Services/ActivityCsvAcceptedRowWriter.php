<?php

namespace App\Services;

use App\Models\User;
use DateTimeImmutable;
use RuntimeException;
use Semilore\CsvImportPipeline\Domain\RowContext;
use Semilore\CsvImportPipeline\Import\Writing\AcceptedRowWriter;

class ActivityCsvAcceptedRowWriter implements AcceptedRowWriter
{
    /** @var array<string, true> */
    private array $existingKeys = [];

    public function __construct(
        private readonly User $user,
    ) {
        foreach ($user->activities()->get(['title', 'due_at']) as $existingActivity) {
            $this->existingKeys[$this->duplicateKey(
                (string) $existingActivity->title,
                $existingActivity->due_at instanceof DateTimeImmutable
                    ? $existingActivity->due_at
                    : ($existingActivity->due_at ? DateTimeImmutable::createFromInterface($existingActivity->due_at) : null),
            )] = true;
        }
    }

    public function write(RowContext $row): void
    {
        $title = trim((string) ($row->field('title')->sanitized ?? ''));
        $description = trim((string) ($row->field('description')->sanitized ?? ''));
        $priority = strtolower(trim((string) ($row->field('priority')->sanitized ?? 'medium')));
        $status = strtolower(trim((string) ($row->field('activity_status')->sanitized ?? 'pending')));
        $dueAt = $row->field('due_at')->typed;

        if ($title === '') {
            throw new RuntimeException('title is required');
        }

        if ($description === '') {
            $description = null;
        }

        if (! in_array($priority, ['low', 'medium', 'high'], true)) {
            $priority = 'medium';
        }

        if (! in_array($status, ['pending', 'in_progress', 'completed'], true)) {
            $status = 'pending';
        }

        $this->assertNotDuplicate($title, $dueAt);

        $this->user->activities()->create([
            'title' => $title,
            'description' => $description,
            'priority' => $priority,
            'activity_status' => $status,
            'due_at' => $dueAt instanceof DateTimeImmutable ? $dueAt : null,
        ]);

        $this->existingKeys[$this->duplicateKey($title, $dueAt instanceof DateTimeImmutable ? $dueAt : null)] = true;
    }

    public function close(): void
    {
        // this method is intentionally left empty as no specific action is required upon closing the writer.
    }

    private function assertNotDuplicate(string $title, mixed $dueAt): void
    {
        $key = $this->duplicateKey($title, $dueAt instanceof DateTimeImmutable ? $dueAt : null);

        if (isset($this->existingKeys[$key])) {
            throw new RuntimeException('Duplicate activity already exists for this user.');
        }
    }

    private function duplicateKey(string $title, ?DateTimeImmutable $dueAt): string
    {
        $normalizedDueAt = $dueAt?->format('Y-m-d H:i:s') ?? 'no-due-date';

        return mb_strtolower(trim($title)).'|'.$normalizedDueAt;
    }
}
