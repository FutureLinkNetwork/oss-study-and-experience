<?php

namespace Tests\Feature\Database\Seeders;

use Database\Seeders\Main\BankBranchSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class BankBranchSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_imports_bank_branches_from_csv(): void
    {
        $this->assertDatabaseCount('bank_branches', 0);

        $this->seed(BankBranchSeeder::class);

        $this->assertDatabaseHas('bank_branches', [
            'management_code' => '0000-000',
            'bank_code' => '0000',
            'bank_name' => 'FLN',
            'bank_name_kana' => 'ｴﾌｴﾙｴﾇ',
            'branch_code' => '000',
            'branch_name' => '西船橋',
            'branch_name_kana' => 'ﾆｼﾌﾅﾊﾞｼ',
        ]);

        $this->assertSame(1, DB::table('bank_branches')->count());
    }

    public function test_seeder_is_idempotent_by_bank_and_branch_code(): void
    {
        $this->seed(BankBranchSeeder::class);
        $this->seed(BankBranchSeeder::class);

        $this->assertSame(1, DB::table('bank_branches')->count());
    }
}
