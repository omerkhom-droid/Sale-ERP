<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class BackupController extends Controller
{
    private function assertSupportUser(): void
    {
        abort_unless(
            in_array(auth()->user()?->user_type, ['master', 'system_admin'], true),
            403,
            'صفحة النسخ الاحتياطي مخصصة لإدارة النظام فقط.'
        );
    }

    public function index()
    {
        abort_unless(auth()->user()?->can('backups.view'), 403);

        $this->assertSupportUser();

        Storage::disk('local')->makeDirectory('backups');

        $files = collect(Storage::disk('local')->files('backups'))
            ->filter(fn ($file) => str_ends_with($file, '.sql'))
            ->map(function ($file) {
                return [
                    'name' => basename($file),
                    'path' => $file,
                    'size' => Storage::disk('local')->size($file),
                    'last_modified' => Storage::disk('local')->lastModified($file),
                ];
            })
            ->sortByDesc('last_modified')
            ->values();

        return view('backups.index', compact('files'));
    }

    public function create(Request $request)
    {
        abort_unless(auth()->user()?->can('backups.create'), 403);

        $this->assertSupportUser();

        $connection = config('database.default');
        $config = config("database.connections.{$connection}");

        abort_unless(
            ($config['driver'] ?? null) === 'mysql',
            422,
            'النسخ الاحتياطي الحالي يدعم MySQL فقط.'
        );

        Storage::disk('local')->makeDirectory('backups');

        $fileName = 'backup-' . now()->format('Ymd-His') . '.sql';
        $relativePath = 'backups/' . $fileName;
        $absolutePath = Storage::disk('local')->path($relativePath);

        $directory = dirname($absolutePath);

        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $handle = fopen($absolutePath, 'w');

        if (! $handle) {
            return redirect()
                ->route('backups.index')
                ->with('error', 'تعذر إنشاء ملف النسخة الاحتياطية.');
        }

        try {
            $this->writeDatabaseDump($handle, $config['database']);

            fclose($handle);

            return redirect()
                ->route('backups.index')
                ->with('success', 'تم إنشاء النسخة الاحتياطية بنجاح.');
        } catch (\Throwable $e) {
            fclose($handle);

            Storage::disk('local')->delete($relativePath);

            return redirect()
                ->route('backups.index')
                ->with('error', 'فشل إنشاء النسخة الاحتياطية: ' . $e->getMessage());
        }
    }

    private function writeDatabaseDump($handle, string $database): void
    {
        fwrite($handle, "-- Wazin ERP Database Backup\n");
        fwrite($handle, "-- Database: {$database}\n");
        fwrite($handle, "-- Date: " . now()->format('Y-m-d H:i:s') . "\n\n");

        fwrite($handle, "SET FOREIGN_KEY_CHECKS=0;\n");
        fwrite($handle, "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\n");
        fwrite($handle, "START TRANSACTION;\n\n");

        $tables = $this->getDatabaseTables();

        foreach ($tables as $table) {
            $this->dumpTableStructure($handle, $table);
            $this->dumpTableData($handle, $table);
        }

        fwrite($handle, "COMMIT;\n");
        fwrite($handle, "SET FOREIGN_KEY_CHECKS=1;\n");
    }

    private function getDatabaseTables(): array
    {
        $rows = DB::select("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'");

        return collect($rows)
            ->map(function ($row) {
                $values = array_values((array) $row);

                return $values[0] ?? null;
            })
            ->filter()
            ->values()
            ->toArray();
    }

    private function dumpTableStructure($handle, string $table): void
    {
        $escapedTable = $this->escapeIdentifier($table);

        fwrite($handle, "\n-- ----------------------------\n");
        fwrite($handle, "-- Table structure for {$table}\n");
        fwrite($handle, "-- ----------------------------\n\n");

        fwrite($handle, "DROP TABLE IF EXISTS `{$escapedTable}`;\n");

        $result = DB::select("SHOW CREATE TABLE `{$escapedTable}`");

        $createTable = (array) ($result[0] ?? []);

        $sql = $createTable['Create Table'] ?? null;

        if (! $sql) {
            throw new \RuntimeException("تعذر قراءة بنية الجدول: {$table}");
        }

        fwrite($handle, $sql . ";\n\n");
    }

    private function dumpTableData($handle, string $table): void
    {
        $escapedTable = $this->escapeIdentifier($table);

        fwrite($handle, "-- ----------------------------\n");
        fwrite($handle, "-- Data for {$table}\n");
        fwrite($handle, "-- ----------------------------\n\n");

        $rows = DB::table($table)->cursor();

        $batch = [];
        $batchSize = 100;

        foreach ($rows as $row) {
            $rowArray = (array) $row;

            if (empty($rowArray)) {
                continue;
            }

            $columns = array_keys($rowArray);

            $values = array_map(function ($value) {
                return $this->sqlValue($value);
            }, array_values($rowArray));

            $batch[] = '(' . implode(', ', $values) . ')';

            if (count($batch) >= $batchSize) {
                $this->writeInsertBatch($handle, $table, $columns, $batch);
                $batch = [];
            }
        }

        if (! empty($batch)) {
            $columns = array_keys((array) DB::table($table)->first());

            if (! empty($columns)) {
                $this->writeInsertBatch($handle, $table, $columns, $batch);
            }
        }

        fwrite($handle, "\n");
    }

    private function writeInsertBatch($handle, string $table, array $columns, array $batch): void
    {
        $escapedTable = $this->escapeIdentifier($table);

        $escapedColumns = collect($columns)
            ->map(fn ($column) => '`' . $this->escapeIdentifier($column) . '`')
            ->implode(', ');

        fwrite(
            $handle,
            "INSERT INTO `{$escapedTable}` ({$escapedColumns}) VALUES\n" .
            implode(",\n", $batch) .
            ";\n"
        );
    }

    private function sqlValue($value): string
    {
        if (is_null($value)) {
            return 'NULL';
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        return DB::getPdo()->quote((string) $value);
    }

    private function escapeIdentifier(string $identifier): string
    {
        return str_replace('`', '``', $identifier);
    }

    public function download(string $file)
    {
        abort_unless(auth()->user()?->can('backups.download'), 403);

        $this->assertSupportUser();

        $file = basename($file);
        $path = 'backups/' . $file;

        abort_unless(Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->download($path);
    }

    public function destroy(Request $request, string $file)
    {
        abort_unless(auth()->user()?->can('backups.delete'), 403);

        $this->assertSupportUser();

        $file = basename($file);
        $path = 'backups/' . $file;

        abort_unless(Storage::disk('local')->exists($path), 404);

        Storage::disk('local')->delete($path);

        return redirect()
            ->route('backups.index')
            ->with('success', 'تم حذف النسخة الاحتياطية بنجاح.');
    }
}