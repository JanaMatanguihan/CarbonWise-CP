<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ExportDummyData extends Command
{
    protected $signature = 'export:dummy';

    protected $description = 'Export user_info and carbon_records to CSV';

    public function handle()
    {
        $exportPath = storage_path('app/exports');

        if (!file_exists($exportPath)) {
            mkdir($exportPath, 0777, true);
        }

        $this->exportTable('user_info', $exportPath);
        $this->exportTable('carbon_records', $exportPath);

        $this->info('Export completed!');
        $this->info('Location: '.$exportPath);
    }

    private function exportTable($table, $path)
    {
        $rows = DB::table($table)->get();

        if ($rows->isEmpty()) {
            $this->warn("$table is empty.");
            return;
        }

        $file = fopen($path.'/'.$table.'.csv', 'w');

        // Header
        fputcsv($file, array_keys((array)$rows->first()));

        // Data
        foreach ($rows as $row) {
            fputcsv($file, (array)$row);
        }

        fclose($file);

        $this->info("$table exported successfully.");
    }
}