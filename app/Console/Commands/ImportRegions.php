<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

/**
 * Loads Indonesian regions from a "kode,nama" SQL dump such as cahyadsn/wilayah (MIT, Kepmendagri 300.2.2-2138/2025).
 * Code length decides the level: 2 provinsi, 5 kabupaten/kota, 8 kecamatan, 13 kelurahan/desa. Safe to re-run.
 */
#[Signature('regions:import {--file= : Local dump to import instead of downloading} {--url= : Override the download URL}')]
#[Description('Import provinces, regencies, districts and villages (idempotent upsert)')]
class ImportRegions extends Command
{
    private const DEFAULT_URL = 'https://raw.githubusercontent.com/cahyadsn/wilayah/master/db/wilayah.sql';

    private const LEVELS = [
        2 => ['provinces', null],
        5 => ['regencies', 'province_code'],
        8 => ['districts', 'regency_code'],
        13 => ['villages', 'district_code'],
    ];

    public function handle(): int
    {
        $file = $this->option('file');
        $temp = null;

        if (! $file) {
            $this->info('Downloading region data...');
            $response = Http::timeout(120)->get($this->option('url') ?: self::DEFAULT_URL);

            if ($response->failed()) {
                $this->error('Download failed (HTTP '.$response->status().').');

                return self::FAILURE;
            }

            $file = $temp = tempnam(sys_get_temp_dir(), 'regions');
            file_put_contents($file, $response->body());
        }

        if (! is_readable($file)) {
            $this->error("Cannot read {$file}.");

            return self::FAILURE;
        }

        $rows = $this->parse($file);
        $temp && @unlink($temp);

        if (empty($rows[2])) {
            $this->error('No regions found in the file; expected lines like ('."'11','Aceh'".').');

            return self::FAILURE;
        }

        DB::transaction(function () use ($rows) {
            foreach (self::LEVELS as $length => [$table]) {
                foreach (array_chunk($rows[$length] ?? [], 1000) as $chunk) {
                    DB::table($table)->upsert($chunk, ['code'], array_diff(array_keys($chunk[0]), ['code']));
                }
                $this->line(sprintf('  %-10s %d', $table, count($rows[$length] ?? [])));
            }
        });

        $this->info('Regions imported.');

        return self::SUCCESS;
    }

    /** @return array<int, list<array<string, string>>> rows grouped by code length */
    private function parse(string $file): array
    {
        $rows = [2 => [], 5 => [], 8 => [], 13 => []];
        $handle = fopen($file, 'r');

        while (($line = fgets($handle)) !== false) {
            if (! preg_match("/^\\('([0-9.]+)','((?:[^']|'')*)'\\)[,;]?\\s*$/", $line, $m)) {
                continue;
            }

            [, $code, $name] = $m;
            $length = strlen($code);

            if (! isset(self::LEVELS[$length])) {
                continue;
            }

            $row = ['code' => $code, 'name' => str_replace("''", "'", $name)];

            if ($parent = self::LEVELS[$length][1]) {
                $row[$parent] = substr($code, 0, strrpos($code, '.'));
            }

            $rows[$length][] = $row;
        }

        fclose($handle);

        return $rows;
    }
}
