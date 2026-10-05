<?php

namespace App\Services;

use App\Models\User;
use DateTimeImmutable;
use RuntimeException;
use Semilore\CsvImportPipeline\Domain\RowContext;
use Semilore\CsvImportPipeline\Import\Writing\AcceptedRowWriter;

class ActivityCsvAcceptedRowWriter implements AcceptedRowWriter
{
    public function __construct(
        private readonly User $user,
    ) {}

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
    }

    public function close(): void
    {
        // this method is intentionally left empty as no specific action is required upon closing the writer.
    }

    private function assertNotDuplicate(string $title, mixed $dueAt): void
    {
        $normalizedDueAt = $dueAt instanceof DateTimeImmutable
            ? $dueAt->format('Y-m-d H:i:s')
            : null;

        $query = $this->user->activities()->whereRaw('LOWER(title) = ?', [mb_strtolower($title)]);

        foreach ($query->get() as $existingActivity) {
            if ($existingActivity->due_at === null && $normalizedDueAt === null) {
                throw new RuntimeException('Duplicate activity already exists for this user.');
            }

            if ($existingActivity->due_at && $normalizedDueAt && $existingActivity->due_at->format('Y-m-d H:i:s') === $normalizedDueAt) {
                throw new RuntimeException('Duplicate activity already exists for this user.');
            }
        }
    }
}
