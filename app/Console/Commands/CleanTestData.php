<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CleanTestData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:clean-test-data {email?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean test data from business_infos table';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->argument('email') ?: 'study-and-experience@ml.futurelink.co.jp';
        
        $this->info("Deleting business_infos records with email: {$email}");
        
        // まず business_info_id を取得
        $businessInfoIds = DB::table('business_infos')
            ->where('email', $email)
            ->pluck('id');
            
        if ($businessInfoIds->isEmpty()) {
            $this->info('No records found with that email.');
            return;
        }
        
        $this->info("Found business_info IDs: " . $businessInfoIds->implode(', '));
        
        // 関連するコース情報を削除
        $courseDeleted = DB::table('course_infos')
            ->whereIn('business_info_id', $businessInfoIds)
            ->delete();
            
        if ($courseDeleted > 0) {
            $this->info("Deleted {$courseDeleted} related course records.");
        }
        
        // 関連する教室情報を削除
        $classroomDeleted = DB::table('classroom_infos')
            ->whereIn('business_info_id', $businessInfoIds)
            ->delete();
            
        if ($classroomDeleted > 0) {
            $this->info("Deleted {$classroomDeleted} related classroom records.");
        }
        
        // 最後にビジネス情報を削除
        $deleted = DB::table('business_infos')->where('email', $email)->delete();
        
        $this->info("Deleted {$deleted} business_infos records.");
        $this->info('Test data cleanup completed.');
    }
}
