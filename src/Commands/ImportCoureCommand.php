<?php

namespace Anwardote\ExportImportWpLaravel\Commands;

use Anwardote\ExportImportWpLaravel\Models\WpRegister;
use Axilweb\BackendPortal\App\Models\Driving\CourseType;
use Axilweb\BackendPortal\App\Models\Driving\Program;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportCoureCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'wp_import:courses';
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command to import programs and courses.';

    /**
     * Execute the console command.
     *
     * @return int
     */

    public function handle()
    {
        // step 1
        // Creating Programs
        $programs = [
            [
                'id' => 3,
                'name' => 'Driving School',
                'is_enabled' => 1,
                'type' => 1
            ],
            [
                'id' => 5,
                'name' => 'Traffic School',
                'is_enabled' => 1,
                'type' => 2
            ],
            [
                'id' => 6,
                'name' => 'Driving Test',
                'is_enabled' => 1,
                'type' => 3
            ],
        ];

        if ($this->ask('Truncate Program and import?', 'y') == 'y') {
            Program::query()->truncate();
            Program::query()->insertOrIgnore($programs);
        }


        // creating coures
        $coures = [
            [
                'id' => 1,
                'name' => 'Behind The Wheel',
                'driving_program_id' => 3,
                'is_enabled' => 1,
                'info_title' => '',
                'info_description' => '',
            ],
            [
                'id' => 2,
                'name' => 'Drivers Ed',
                'driving_program_id' => 3,
                'is_enabled' => 1,
                'info_title' => '',
                'info_description' => '',
            ],
            [
                'id' => 3,
                'name' => 'Traffic Course',
                'driving_program_id' => 5,
                'is_enabled' => 1,
                'info_title' => '',
                'info_description' => '',
            ],
            [
                'id' => 4,
                'name' => 'Driving Test - $200',
                'driving_program_id' => 6,
                'info_title' => "Driving test can only be scheduled by current or previous Northwest Driving School students.",
                'info_description' => "It is important that you must call us at 702-212-5667 to schedule your Driving Test so that we can coordinate your schedule, your instructor's schedule and the DMV schedule at the same time. <br> If you schedule your Driving Test online at the DMV and need to reschedule, the DMV will automatically delay your test by up to a month so we ask that you call our office so that we can help you to facilitate the best day, date and time for you to take your Driving Test.",
                'is_enabled' => 1
            ],
            [
                'id' => 5,
                'name' => 'Drive Test Auto Rental - $275',
                'driving_program_id' => 6,
                'info_title' => "Driving test can only be scheduled by current or previous Northwest Driving School students.",
                'info_description' => "It is important that you must call us at 702-212-5667 to schedule your Driving Test so that we can coordinate your schedule, your instructor's schedule and the DMV schedule at the same time. <br> If you schedule your Driving Test online at the DMV and need to reschedule, the DMV will automatically delay your test by up to a month so we ask that you call our office so that we can help you to facilitate the best day, date and time for you to take your Driving Test.",
                'is_enabled' => 1
            ],
        ];

        if ($this->ask('Truncate Courses and import?', 'y') == 'y') {
            CourseType::query()->truncate();
            CourseType::query()
                ->insertOrIgnore($coures);
        }


        // step 2
        // mapping  new_course_type_id by new course_type_id

        // Driving Behind the while new_course_type_id = 1 from course_type_id =3
        WpRegister::query()
            ->where('user_id', '>', 0)
            ->where('course_type_id', 3)
            ->where(function ($query) {
                return $query->whereNull('drivers_ed_class_date')
                    ->orWhere('drivers_ed_class_date', '=', '');
            })
            ->update([
                'driving_program_id' => 3,
                'new_course_type_id' => 1
            ]);
        $this->info("Behind The Wheel mapped");

        // Driving ED set new_course_type_id = 2 from course_type_id =3
        WpRegister::query()
            ->select('course_slot', 'course_type_id', 'new_course_type_id', 'drivers_ed_class_date')
            ->where('user_id', '>', 0)
            ->where('course_type_id', 3)
            ->where(function ($query) {
                return $query->whereNotNull('drivers_ed_class_date')
                    ->where('drivers_ed_class_date', "<>", "");
            })->update([
                'driving_program_id' => 3,
                'new_course_type_id' => 2
            ]);
        $this->info("Driving ED mapped");

        // Fixing wrong behind the while class
        WpRegister::query()
            ->select('course_slot', 'course_type_id', 'new_course_type_id', 'drivers_ed_class_date')
            ->whereIn('course_slot', ['15/10 with Driving Test - $600', '15/6 - $325'])
            ->where('course_type_id', 3)
            ->groupBy('course_slot')
            ->update([
                'driving_program_id' => 3,
                'new_course_type_id' => 2
            ]);
        $this->info("Wrong course slot fixed");

        // Traffic coure
        WpRegister::query()
            ->where('course_type_id', 5)
            ->where('user_id', '>', 0)
            ->update([
                'driving_program_id' => 5,
                'new_course_type_id' => 3
            ]);
        $this->info("Traffic Courses mapped");

        // Driving Test
        WpRegister::query()
            ->where('course_type_id', 6)
            ->where('user_id', '>', 0)
            ->update([
                'driving_program_id' => 6,
                'new_course_type_id' => 4
            ]);
        WpRegister::query()
            ->where('course_type_id', 9)
            ->where('user_id', '>', 0)
            ->update([
                'driving_program_id' => 6,
                'new_course_type_id' => 5
            ]);

        $this->info("Driving Test mapped");
    }
}
