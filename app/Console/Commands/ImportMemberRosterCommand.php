<?php

namespace App\Console\Commands;

use App\Services\People\Imports\MemberRosterImportService;
use Illuminate\Console\Command;
use Throwable;

class ImportMemberRosterCommand extends Command
{
    protected $signature = 'import:member-roster
        {path : Absolute path to the roster file}
        {--source=Manual import : Label stored with the batch}
        {--apply : Apply exact matches and new people immediately}';

    protected $description = 'Stage or apply a member roster spreadsheet import';

    /**
     * @throws Throwable
     */
    public function handle(MemberRosterImportService $service): int
    {
        $path = (string) $this->argument('path');

        if (!is_file($path)) {
            $this->error("File not found: {$path}");
            return self::FAILURE;
        }

        $batch = $service->stagePath(
            absolutePath: $path,
            originalFilename: basename($path),
            storedPath: $path,
            sourceLabel: (string) $this->option('source'),
        );

        $summary = $batch->summary ?? [];

        $this->info("Created batch #($batch->id)");
        $this->line('Summary: '.json_encode($summary));

        if ($this->option('apply')) {
            $batch = $service->applyBatch($batch);
            $this->info("Applied batch #{$batch->id}");
        }

        return self::SUCCESS;
    }
}
