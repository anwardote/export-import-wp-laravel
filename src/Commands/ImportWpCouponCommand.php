<?php

namespace Anwardote\ExportImportWpLaravel\Commands;

use Anwardote\ExportImportWpLaravel\Models\WpCoupon;
use Anwardote\ExportImportWpLaravel\Models\WpRegister;
use Axilweb\BackendPortal\App\Models\Driving\Coupon;
use Axilweb\BackendPortal\App\Models\Driving\DriverEdClassDate;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;

class ImportWpCouponCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'wp_import:coupons';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command to import Coupon into laravel.';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $coupons = WpCoupon::query()->get();
        $couponData = [];
        foreach ($coupons as $coupon) {
            $couponData[] = [
                'id' => $coupon->id,
                'code' => $coupon->code,
                'discount_type' => $coupon->percent_off > 0 ? 1 : 2,
                'amount_off' => $coupon->amount_off,
                'percent_off' => $coupon->percent_off,
                'activated_at' => CarbonImmutable::parse($coupon->activated_date)->format('Y-m-d H:i:s'),
                'expired_at' => CarbonImmutable::parse($coupon->expired_date)->format('Y-m-d H:i:s'),
                'is_id_card_required' => $coupon->id_card == 'YES',
                'applicable_for' => $coupon->id,
                'student_of_school' => $coupon->id,
                'is_enabled' => $coupon->status == 'active',
            ];
        }

        $this->info("Total Coupon Records: ".count($couponData));

        if ($this->ask('Will truncate coupon old data?', 'y') == 'y') {
            Coupon::query()->truncate();
        }

        Coupon::query()->insert($couponData);
    }
}
