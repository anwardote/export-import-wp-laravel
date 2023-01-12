<?php

namespace Anwardote\ExportImportWpLaravel\Commands;

use Anwardote\ExportImportWpLaravel\Models\WpCoupon;
use Axilweb\BackendPortal\App\Models\Driving\Coupon;
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
            $applicable = [];
            if($coupon->vendor == 'any'){
                $applicable = ['ncc', 'nwds'];
            }elseif($coupon->vendor == 'nwcc'){
                $applicable = ['ncc'];
            }elseif($coupon->vendor == 'nwds'){
                $applicable = ['nwds'];
            }

            $couponData[] = [
                'id' => $coupon->id,
                'code' => $coupon->code,
                'discount_type' => $coupon->percent_off > 0 ? 1 : 2,
                'amount_off' => $coupon->amount_off,
                'percent_off' => $coupon->percent_off,
                'activated_at' => CarbonImmutable::parse($coupon->activated_date)->format('Y-m-d H:i:s'),
                'expired_at' => CarbonImmutable::parse($coupon->expired_date)->format('Y-m-d H:i:s'),
                'is_id_card_required' => $coupon->id_card == 'YES',
//                'applicable_for' => $coupon->id,
                'applicable_for' => json_encode($applicable),
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
