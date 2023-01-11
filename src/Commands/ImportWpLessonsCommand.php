<?php

namespace Anwardote\ExportImportWpLaravel\Commands;

use Anwardote\ExportImportWpLaravel\Models\WpRegister;
use Axilweb\BackendPortal\App\Models\Driving\Lesson;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class ImportWpLessonsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'wp_import:lessons';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command to import lessons to laravel users.';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $lessons = WpRegister::query()
            ->select([
                'course_slot', 'course_type_id',
                'new_course_type_id', 'course_slot_num',
                'driving_program_id'
            ])->where('user_id', '>', 0)
            ->distinct('course_slot')
            ->get();

        if ($this->ask('Will truncate lessons table?', 'y') == 'y') {
            Lesson::query()->truncate();
        }

        ray()->clearAll();
        if ($this->ask('Import lessons table?', 'y') == 'y') {

            WpRegister::query()->update(['driving_lesson_id' => null]);

            $wplessons = collect($lessons)->reduce(function ($results, $item){
                $courseSlot = explode('-', $item->course_slot);
                $name = trim($courseSlot[0]);
                $results[$name] = $item;

                return $results;
            },[]);
//
//            dd(WpRegister::query()
//                ->where('course_slot','REGEXP', '2 Hr%')
//                ->get());
//            dd(array_keys($wplessons));

            $key = 0;
            foreach ($wplessons as $lesson) {
                $courseSlot = explode('-', $lesson->course_slot);
                $id = $key + 1;
                $key++;
                $name = $courseSlot[0];
                $price = Str::of($courseSlot[1] ?? 0)->replace('$', '')->trim()->toInteger();
                $this->info($id.":".$lesson->course_slot);

                try {
                    $lessonData = [
                        'id' => $id,
                        'name' => $name,
                        'driving_program_id' => $lesson->driving_program_id,
                        'driving_course_type_id' => $lesson->new_course_type_id,
                        'no_of_lessons' => $lesson->course_slot_num,
                        'price' => $price,
                    ];
                    if(Lesson::query()->insert($lessonData)){
                        WpRegister::query()
                            ->where('course_slot','REGEXP', $name)
                            ->update(['driving_lesson_id' => $id]);
                    }
                } catch (\Exception $exception) {
                    ray($lessonData);
                    dd($exception->getMessage());
                }


            }
        }
    }
}
