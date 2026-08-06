<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('subdomains', function (Blueprint $table) {
            $table->text('invoice_preamble')->nullable()->after('system_name')->comment('会計用請求書前文');
        });

        DB::table('subdomains')->where('subdomain', 'itami')->update([
            'invoice_preamble' => '伊丹市子どもの習い事応援事業実施要綱第９条第２項の規定に基づき、下記のとおり請求します。',
        ]);

        DB::table('subdomains')
            ->where('subdomain', '!=', 'itami')
            ->whereNull('invoice_preamble')
            ->update([
                'invoice_preamble' => '下記のとおり請求します。',
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subdomains', function (Blueprint $table) {
            $table->dropColumn('invoice_preamble');
        });
    }
};
