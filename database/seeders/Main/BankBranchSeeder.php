<?php

namespace Database\Seeders\Main;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BankBranchSeeder extends Seeder
{
    private const string CSV_PATH = 'database/seeders/data/bank_branches.csv';

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $csvPath = base_path(self::CSV_PATH);

        if (! is_readable($csvPath)) {
            $this->command?->error('bank_branches.csv が見つかりません: '.$csvPath);

            return;
        }

        $handle = fopen($csvPath, 'r');

        if ($handle === false) {
            $this->command?->error('bank_branches.csv を開けませんでした: '.$csvPath);

            return;
        }

        $header = fgetcsv($handle);

        if ($header === false) {
            fclose($handle);

            return;
        }

        $rows = [];

        while (($record = fgetcsv($handle)) !== false) {
            if ($record === [null] || $record === []) {
                continue;
            }

            $row = $this->mapCsvRow($header, $record);

            if ($row === null) {
                continue;
            }

            $rows[] = $row;
        }

        fclose($handle);

        foreach ($rows as $row) {
            DB::table('bank_branches')->updateOrInsert(
                [
                    'bank_code' => $row['bank_code'],
                    'branch_code' => $row['branch_code'],
                ],
                $row
            );
        }

        $this->command?->info('bank_branches: '.count($rows).' 件を投入しました。');
    }

    /**
     * @param  array<int, string|null>  $header
     * @param  array<int, string|null>  $record
     * @return array<string, mixed>|null
     */
    private function mapCsvRow(array $header, array $record): ?array
    {
        /** @var array<string, string|null> $data */
        $data = array_combine($header, $record);

        if ($data === false) {
            return null;
        }

        $bankCode = trim((string) ($data['bank_code'] ?? ''));
        $branchCode = trim((string) ($data['branch_code'] ?? ''));

        if ($bankCode === '' || $branchCode === '') {
            return null;
        }

        $now = now();

        return [
            'management_code' => trim((string) ($data['management_code'] ?? '')),
            'bank_code' => $bankCode,
            'bank_name' => trim((string) ($data['bank_name'] ?? '')),
            'bank_name_kana' => trim((string) ($data['bank_name_kana'] ?? '')),
            'branch_code' => $branchCode,
            'branch_name' => trim((string) ($data['branch_name'] ?? '')),
            'branch_name_kana' => trim((string) ($data['branch_name_kana'] ?? '')),
            'created_at' => $this->parseTimestamp($data['created_at'] ?? null) ?? $now,
            'updated_at' => $this->parseTimestamp($data['updated_at'] ?? null) ?? $now,
        ];
    }

    private function parseTimestamp(?string $value): ?Carbon
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        try {
            return Carbon::createFromFormat('j/n/Y H:i:s', trim($value));
        } catch (\Throwable) {
            return null;
        }
    }
}
