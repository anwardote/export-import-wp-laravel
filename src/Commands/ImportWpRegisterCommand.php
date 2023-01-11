<?php

namespace Anwardote\ExportImportWpLaravel\Commands;

use Anwardote\ExportImportWpLaravel\Models\WpRegister;
use Anwardote\ExportImportWpLaravel\Models\WpRegisterPiv;
use Anwardote\ExportImportWpLaravel\Models\WpUser;
use Axilweb\Acl\App\Models\User;
use Axilweb\BackendPortal\App\Models\Driving\Appointment;
use Axilweb\BackendPortal\App\Models\Driving\AppointmentTime;
use Axilweb\BackendPortal\App\Models\Driving\Lesson;
use Axilweb\BackendPortal\App\Models\Driving\Schedule;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ImportWpRegisterCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'wp_import:registers';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command to import registers to appointment tables';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
//        $users = User::query()->whereHas('roles', function ($query) {
//            $query->where('name', 'subscriber');
//        })->update(['type' => 3]);
//        dd($users->count());
//
//        dd('wait');

        if ($this->ask('Will truncate appointments old data?', 'y') == 'y') {
            Appointment::query()->truncate();
        }

        $wpRegistersCount = WpRegister::query()->where('user_id', '>', 0)->count();
        $this->info("Total register found: ".$wpRegistersCount);

        $wpRegisters = WpRegister::query()
            ->where('user_id', '>', 0)
            ->whereNotNull('user_uuid');

        foreach ($wpRegisters->cursor() as $wpRegister) {
            $apointmentData = $this->getRegisterData($wpRegister);
            try {
                Appointment::query()->insertOrIgnore($apointmentData);
                $wpSchedules = WpRegisterPiv::query()
                    ->where('r_uuid', $wpRegister->uuid)
                    ->get();

                $apointmentTimes = [];
                if ($wpSchedules->count()) {
                    foreach ($wpSchedules as $wpSchedule) {
                        $scheduleData = Schedule::query()->create([
                            'driving_program_id' => $wpRegister->driving_program_id,
                            'instructor_id' => $wpRegister->instructor_uuid,
                            'start_datetime' => $wpSchedule->r_start_date_time,
                            'end_datetime' => $wpSchedule->r_end_date_time,
                        ]);

                        $apointmentTimes[] = [
                            'driving_schedule_id' => $scheduleData->id,
                            'driving_appointment_id' => $wpRegister->uuid,
                        ];
                    }
                }

                if ($apointmentTimes) {
                    AppointmentTime::query()->insert($apointmentTimes);
                }

                $this->info("Running: - ".$wpRegister->id);
            } catch (\Exception $exception) {
                dd($exception->getMessage());
                ray($apointmentData);
            }
        }

        $this->info('Importing completed!');
    }

    protected function getRegisterData($regiData)
    {
        $courseSlot = explode('-', $regiData->course_slot);
        $actualPrice = Str::of($courseSlot[1] ?? 0)->replace('$', '')->trim()->toInteger();
        $permitDate = empty($regiData->permit_expired)
            ? null
            : Carbon::parse($regiData->permit_expired)->format('Y-m-d');

        $regiData = [
            'id' => $regiData->uuid,
            'student_id' => $regiData->user_uuid,
            'driving_program_id' => $regiData->driving_program_id,
            'driving_course_type_id' => $regiData->new_course_type_id,
            'driving_lesson_id' => $regiData->driving_lesson_id,
            'driving_ed_class_date_id' => $regiData->driving_ed_class_date_id,
            'no_of_lessons' => $regiData->course_slot_num,
            'actual_price' => $actualPrice,
            'driving_coupon_id' => null,
            'coupon_code' => null,
            'gift_code' => null,
            'discount_amount' => $actualPrice - $regiData->total_amount,
            'paid_amount' => $regiData->total_amount,
            'permit_no' => $regiData->permit_number,
            'permit_exp_date' => $permitDate,
            'payment_status' => $regiData->payment_status == 'success' ? 1 : 3,
            'status' => $regiData->status,
            'card_number' => $regiData->last_four_digits_card,
            'transaction_id' => null,
            'id_card' => $regiData->attachment_id,
            'created_at' => $regiData->created_at,
            'updated_at' => $regiData->updated_at,
        ];

        return $regiData;
    }


}
