<?php

namespace App\Services;

use App\Models\User;
use DateTimeImmutable;
use Illuminate\Http\UploadedFile;
use Semilore\CsvImportPipeline\Import\Configuration\ConfigurationValidator;
use Semilore\CsvImportPipeline\Import\Deduplication\DuplicateChecker;
use Semilore\CsvImportPipeline\Import\ImportPipeline;
use Semilore\CsvImportPipeline\Import\Logging\FileLogger;
use Semilore\CsvImportPipeline\Import\Mapping\HeaderMapper;
use Semilore\CsvImportPipeline\Import\Parsing\ParseResult;
use Semilore\CsvImportPipeline\Import\Parsing\ParsesField;
use Semilore\CsvImportPipeline\Import\Reading\CsvReader;
use Semilore\CsvImportPipeline\Import\Sanitization\TrimSanitizer;
use Semilore\CsvImportPipeline\Import\Validation\RequiredRule;
use Semilore\CsvImportPipeline\Import\Validation\ValidationEngine;
use Semilore\CsvImportPipeline\Import\Writing\RejectWriter;

class ActivityCsvImportService
{
    /** @return array{imported: int, rejected: int, rejections: list<array{row: int, values: list<string>, reasons: list<string>}>} */
    public function import(User $user, UploadedFile $file): array
    {
        $filePath = $file->getRealPath();

        if ($filePath === false || ! is_file($filePath)) {
            throw new \RuntimeException('Unable to read uploaded CSV file.');
        }

        $aliases = [
            'title' => ['title', 'task title', 'Task Title', 'Title', 'task', 'Task', 'activity title', 'Activity', 'Activity Title', 'User title'],
            'description' => ['description', 'details', 'Description', 'Details', 'task description', 'Task Description', 'activity description', 'Activity Description'],
            'priority' => ['priority', 'Priority'],
            'activity_status' => ['activity_status', 'status', 'Status'],
            'due_at' => ['due_at', 'date', 'Due Date', 'due date', 'due', 'Due', 'due date', 'Due Date', 'due_at', 'Due At', 'Time', 'time', 'Deadline', 'deadline'],
        ];

        $sanitizers = [
            'title' => new TrimSanitizer,
            'description' => new TrimSanitizer,
            'priority' => new TrimSanitizer,
            'activity_status' => new TrimSanitizer,
            'due_at' => new TrimSanitizer,
        ];

        $parsers = [
            'title' => new class implements ParsesField
            {
                public function parse(string $sanitized): ParseResult
                {
                    $value = trim($sanitized);

                    if ($value === '') {
                        return ParseResult::failure('title is required');
                    }

                    return ParseResult::success($value);
                }
            },
            'description' => new class implements ParsesField
            {
                public function parse(string $sanitized): ParseResult
                {
                    return ParseResult::success($sanitized === '' ? null : $sanitized);
                }
            },
            'priority' => new class implements ParsesField
            {
                public function parse(string $sanitized): ParseResult
                {
                    $value = strtolower(trim($sanitized));

                    if ($value === '') {
                        return ParseResult::success('medium');
                    }

                    if (! in_array($value, ['low', 'medium', 'high'], true)) {
                        return ParseResult::failure('priority must be low, medium or high');
                    }

                    return ParseResult::success($value);
                }
            },
            'activity_status' => new class implements ParsesField
            {
                public function parse(string $sanitized): ParseResult
                {
                    $value = strtolower(trim($sanitized));

                    if ($value === '') {
                        return ParseResult::success('pending');
                    }

                    if (! in_array($value, ['pending', 'in_progress', 'completed'], true)) {
                        return ParseResult::failure('activity_status must be pending, in_progress or completed');
                    }

                    return ParseResult::success($value);
                }
            },
            'due_at' => new class implements ParsesField
            {
                public function parse(string $sanitized): ParseResult
                {
                    if ($sanitized === '') {
                        return ParseResult::success(null);
                    }

                    try {
                        return ParseResult::success(new DateTimeImmutable($sanitized));
                    } catch (\Exception $exception) {
                        return ParseResult::failure('due_at is not a valid date');
                    }
                }
            },
        ];

        $rulesByField = [
            'title' => [new RequiredRule('title')],
            'description' => [],
            'priority' => [],
            'activity_status' => [],
            'due_at' => [],
        ];

        (new ConfigurationValidator)->validate(
            $aliases,
            $sanitizers,
            $parsers,
            'title',
        );

        $pipeline = new ImportPipeline(
            reader: new CsvReader($filePath),
            headerMapper: new HeaderMapper($aliases),
            sanitizers: $sanitizers,
            parsers: $parsers,
            validationEngine: new ValidationEngine($rulesByField),
            duplicateChecker: new DuplicateChecker,
            duplicateCheckField: 'title',
            outputWriter: new ActivityCsvAcceptedRowWriter($user),
            rejectWriter: new RejectWriter(storage_path('app/csv-import-rejects-'.now()->format('Ymd_His').'.csv')),
            logger: new FileLogger(storage_path('logs/csv-import-'.now()->format('Ymd_His').'.log')),
            duplicateKeyResolver: function ($row) {
                $title = strtolower(trim((string) ($row->field('title')->sanitized ?? '')));
                $dueAt = $row->field('due_at')->typed;

                if ($dueAt instanceof DateTimeImmutable) {
                    return $title.'|'.$dueAt->format('Y-m-d H:i:s');
                }

                return $title.'|no-due-date';
            },
        );

        $report = $pipeline->run();

        return $report->toArray();
    }
}
