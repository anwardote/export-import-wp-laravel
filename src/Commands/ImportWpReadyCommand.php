<?php

namespace Anwardote\ExportImportWpLaravel\Commands;

use Anwardote\ExportImportWpLaravel\Models\WpRegister;
use Anwardote\ExportImportWpLaravel\Models\WpUser;
use Anwardote\ExportImportWpLaravel\Models\WpUsermeta;
use Anwardote\ExportImportWpLaravel\Services\ExportRoleService;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ImportWpReadyCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'wp_import:ready';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command to ready wp db';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        ray()->clearAll();
//        ID: 8  maybe instructor
//        ID : 3286  maybe student
//        They are not assigned any role.. thats why count is generating wrong counter
        DB::connection('wp')->statement("ALTER TABLE wp_9t854h_users CHANGE `user_registered` `user_registered` DATETIME NULL DEFAULT NULL");
        WpUsermeta::query()->updateOrCreate(
            ['user_id' => 8, 'meta_key' => 'wp_9t854h_capabilities'],
            ['meta_value' => ExportRoleService::instructorRole()]
        );

        WpUsermeta::query()->updateOrCreate(
            ['user_id' => 3286, 'meta_key' => 'wp_9t854h_capabilities'],
            ['meta_value' => ExportRoleService::studentRole()]
        );

//        $students = WpUser::query()
//            ->select('ID')
//            ->leftJoin('usermeta','usermeta.user_id','users.ID')
//            ->where('usermeta.meta_key','=','wp_9t854h_capabilities')
//            ->get()->pluck('ID');
//
//        $students2 = WpUser::query()
//            ->select('ID')
//            ->get()->pluck('ID');
////
//        dd(array_diff($students2->toArray(), $students->toArray()));

        $totalUsers = WpUser::query()->leftJoin('usermeta', 'usermeta.user_id', 'users.ID')
            ->where('usermeta.meta_key', '=', 'wp_9t854h_capabilities')
            ->where(function ($query) {
                $query->whereIn('usermeta.meta_value', ExportRoleService::allRoles());
            })->count();
        $this->info("Total User: ".$totalUsers);

        // 4977
        $totalStudent = WpUser::query()->leftJoin('usermeta', 'usermeta.user_id', 'users.ID')
            ->where('usermeta.meta_key', 'wp_9t854h_capabilities')
            ->where(function ($query) {
                $query->whereIn('usermeta.meta_value', [
                    ExportRoleService::studentRole()
                ]);
            })->count();
        $this->info("Total Student: ".$totalStudent);

//      // 12
        $totalSubscribers = WpUser::query()->leftJoin('usermeta', 'usermeta.user_id', 'users.ID')
            ->where('usermeta.meta_key', 'wp_9t854h_capabilities')
            ->where(function ($query) {
                $query->whereIn('usermeta.meta_value', [
                    ExportRoleService::subscriberRole()
                ]);
            })->count();
        $this->info("Total Subscriber: ".$totalSubscribers);

        //19
        $totalInstructors = WpUser::query()->leftJoin('usermeta', 'usermeta.user_id', 'users.ID')
            ->where('usermeta.meta_key', 'wp_9t854h_capabilities')
            ->where(function ($query) {
                $query->whereIn('usermeta.meta_value', [
                    ExportRoleService::instructorRole()
                ]);
            })->count();
        $this->info("Total Instructor: ".$totalInstructors);


        //19 (18 -admin, editor - 1)
        $totalAdminWithEditor = WpUser::query()->leftJoin('usermeta', 'usermeta.user_id', 'users.ID')
            ->where('usermeta.meta_key', 'wp_9t854h_capabilities')
            ->where(function ($query) {
                $query->whereIn('usermeta.meta_value', [
                    ExportRoleService::adminRole(),
                    ExportRoleService::adminWithInstructorRole(),
                    ExportRoleService::instructorWithAdminRole(),
                    ExportRoleService::editorRole()
                ]);
            })->count();

        $this->info("Total Admin with Instructors: ".$totalAdminWithEditor);
        $this->info(
            "Total Sum: ".array_sum([$totalStudent, $totalSubscribers, $totalInstructors, $totalAdminWithEditor])
        );

        //SELECT * FROM `wp_9t854h_nwds_registration_pivot` where not r_time in ("9:00 AM - 2:00 PM","9:00 AM - 5:00 PM");
        // Step 1
        if ($this->ask('Will update Course_id 8 => 9?', 'y') == 'y') {
            WpRegister::query()->whereIn('id', [3450, 3499, 3525])
                ->where('course_type_id', 8)
                ->update(['course_type_id' => 9]);

            $this->info('updated');
        }

        // Step 2
        if ($this->ask('Will map users ID to app_uuid(UUID)?', 'y') == 'y') {
            foreach (WpUser::query()->cursor() as $user) {
                $userUuid = Str::uuid();

                $user->update(['app_uuid' => $userUuid]);
                $user->update(['role' => $userUuid]);

                WpRegister::query()
                    ->where('user_id', $user->ID)
                    ->update(['user_uuid' => $userUuid]);

                $this->info('User ID :'.$user->ID);
            }
            $this->info('Success Action');
        }

        // Step 3
        if ($this->ask('Will map register instructor_id to instructor_uuid?', 'y') == 'y') {
            $instructors = WpRegister::query()
                ->select('id', 'instructor_id')
                ->addSelect([
                    'app_uuid' => WpUser::query()
                        ->select('app_uuid')
                        ->whereColumn('ID', 'instructor_id')
                ])
                ->groupBy('instructor_id')
                ->get();

            $adminUser = WpUser::query()
                ->where('ID', 1)
                ->first(); // for for missing users which will be replace by user_id 1

            foreach ($instructors as $instructor) {
                if ($instructor->app_uuid) {
                    $userUuid = $instructor->app_uuid;
                } else {
                    $userUuid = $adminUser->app_uuid;
                }

                WpRegister::query()
                    ->where('instructor_id', $instructor->instructor_id)
                    ->update(['instructor_uuid' => $userUuid]);
            }

            $this->info('Success Action');
        }

        //Step 4
        if ($this->ask('Will map RegisterId to regi_uuid', 'y') == 'y') {
            foreach (WpRegister::query()->cursor() as $regi) {
                $regUuid = Str::uuid()->toString();
                $regi->update(['uuid' => $regUuid]);

                DB::connection('wp')
                    ->table('nwds_registration_pivot')
                    ->where('r_id', $regi->id)
                    ->update(['r_uuid' => $regUuid]);

                $this->info('Register ID :'.$regi->id);
            }
            $this->info('Success Action');
        }

        //Step 5
        if ($this->ask('Will map regi_time to regi_time24', 'y') == 'y') {
            $times = DB::connection('wp')
                ->table('nwds_registration_pivot')
//                ->whereNotIn('r_time', ['9:00 AM - 2:00 PM', '9:00 AM - 5:00 PM'])
//                ->groupBy('r_time')
                ->get();

            foreach ($times as $time) {
                $date = $time->r_date;
                $time12 = $time->r_time;

                if ($time->r_time == '9:00 AM - 2:00 PM') {
                    $timeObj = CarbonImmutable::parse("$date 9:00 AM");
                    $startDateTime = $timeObj->format('Y-m-d H:i:s');
                    $endDateTime = $timeObj->addHours(5)->format('Y-m-d H:i:s');
                } elseif ($time->r_time == '9:00 AM - 5:00 PM') {
                    $timeObj = CarbonImmutable::parse("$date 9:00 AM");
                    $startDateTime = $timeObj->format('Y-m-d H:i:s');
                    $endDateTime = $timeObj->addHours(8)->format('Y-m-d H:i:s');
                } else {
                    $timeObj = CarbonImmutable::parse("$date $time12");
                    $startDateTime = $timeObj->format('Y-m-d H:i:s');
                    $endDateTime = $timeObj->addHours(2)->format('Y-m-d H:i:s');
                }
                DB::connection('wp')
                    ->table('nwds_registration_pivot')
                    ->where('r_uuid', $time->r_uuid)
                    ->where('r_date', $time->r_date)
                    ->where('r_time', $time->r_time)
                    ->update([
                        'r_start_date_time' => $startDateTime,
                        'r_end_date_time' => $endDateTime
                    ]);
                $this->info('Register ID : '."$date $time12: $startDateTime - $endDateTime");
            }
        }

//        if ($this->ask('Will map user_id(app_uuid) at register table by UUID??', 'y')) {
//            foreach (WpUser::query()->cursor() as $user) {
//                $user->update(['app_uuid' => Str::uuid()]);
//                $this->info('User ID :'.$user->ID);
//            }
//            $this->info('Success to map User ID as uuid');
//        }
    }
}
