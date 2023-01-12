<?php

namespace Anwardote\ExportImportWpLaravel\Commands;

use Anwardote\ExportImportWpLaravel\Models\WpRegister;
use Anwardote\ExportImportWpLaravel\Models\WpRegisterPiv;
use Axilweb\BackendPortal\App\Models\Driving\Appointment;
use Axilweb\BackendPortal\App\Models\Driving\AppointmentTime;
use Axilweb\BackendPortal\App\Models\Driving\Schedule;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
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
        if ($this->ask('Download ID Cards?(y/n)', 'n') == 'y') {
            $regiCards = Http::get('https://northwestdrivingschool.com/wp-json/private/v1/id-cards');
            if ($regiCards->ok()) {
                $cards = $regiCards->json();
                if ($cards) {
                    foreach ($cards as $id => $card) {
                        if (isset($card['url']) && !empty($card['url'])) {
                            $cardUrl = $card['url'];
                            try {
                                $fileContent = file_get_contents($cardUrl);
                                $this->info('Downloading .. :'.$cardUrl);
                                if ($fileContent) {
                                    Storage::disk('public')->put("/id-cards/".$id.'.jpg', $fileContent);
                                }
                            } catch (\Exception $exception) {
                                $this->warn('failed .. :'.$cardUrl);
                            }
                        }
                    }
                }
            }
        }

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
                $this->warn($exception->getMessage());
                dd($apointmentData);
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

        $idCardFile = null;
        if ($regiData->attachment_id > 0) {
            $idCardFile = "id-cards/{$regiData->attachment_id}.jpg";
        }

        $couponCode = null;
        $discountAmount = 0;
        $giftCode = null;
        $giftAmount = 0;

        if ($regiData->gift_amount > 0) {
            $giftAmount = $regiData->gift_amount;
        }

        $registrationString = unserialize($regiData->registration_string);
        $lastFourDigitCard = '';
        if (is_array($registrationString)) {
            // last 4 digits card numbers
            $cardNumString = $registrationString['card_num'] ?? '';
            if (strlen($cardNumString) > 4) {
                $lastFourDigitCard = Str::substr($cardNumString, -4, 4);
            }

            $couponCodeString = $registrationString['coupon_code'] ?? '';
            $codeTypeString = $registrationString['code_type'] ?? '';

            if ($giftAmount == 0 && $couponCodeString) {
                if (!$codeTypeString || $codeTypeString == 'coupon') {
                    $couponCode = $couponCodeString;
                    $discountAmount = $registrationString['discount_amount'] ?? 0;
                    $discountAmount = floatval($discountAmount);
                }
            }

            if ($giftAmount > 0) {
                $giftCode = $couponCodeString;
            }
        }


        if($couponCode && !$discountAmount){
            $discountAmount = $actualPrice - $regiData->total_amount;
        }

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
            'coupon_code' => trim($couponCode),
            'discount_amount' => $discountAmount,
            'gift_code' => trim($giftCode),
            'gift_amount' => $giftAmount,
            'paid_amount' => $regiData->total_amount,
            'permit_no' => $regiData->permit_number,
            'permit_exp_date' => $permitDate,
            'payment_status' => $regiData->payment_status == 'success' ? 1 : 3,
            'status' => $regiData->status,
            'last_four_digit_card' => $lastFourDigitCard,
            'transaction_id' => null,
            'id_card_file' => $idCardFile,
            'created_at' => $regiData->created_at,
            'updated_at' => $regiData->updated_at,
        ];

        return $regiData;
    }

}
