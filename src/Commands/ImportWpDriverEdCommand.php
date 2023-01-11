<?php

namespace Anwardote\ExportImportWpLaravel\Commands;

use Anwardote\ExportImportWpLaravel\Models\WpRegister;
use Axilweb\BackendPortal\App\Models\Driving\DriverEdClassDate;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;

class ImportWpDriverEdCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'wp_import:drivers_ed';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $driverEds = WpRegister::query()
            ->select('drivers_ed_class_date')
            ->where('user_id', '>', 0)
            ->whereNotNull('drivers_ed_class_date')
            ->distinct('drivers_ed_class_date')
            ->get();

        if ($this->ask('Will truncate drivers_ed_class_date table?', 'y') == 'y') {
            DriverEdClassDate::query()->truncate();
        }

        if ($this->ask('Import drivers_ed_class_date table?', 'y') == 'y') {
            foreach ($driverEds as $key => $driverEd) {
                if ($driverEd->drivers_ed_class_date) {
                    $driverDate = CarbonImmutable::parse($driverEd->drivers_ed_class_date);
                    $id = $key + 1;
                    $this->info($id.":".$driverDate->format('Y-m-d'));

                    if (DriverEdClassDate::query()->insertOrIgnore([
                        'id' => $key + 1,
                        'start_date' => $driverDate->format('Y-m-d'),
                        'end_date' => $driverDate->addDay()->format('Y-m-d'),
                    ])) {
                        WpRegister::query()
                            ->where('drivers_ed_class_date', $driverDate->format('Y-m-d'))
                            ->update(['driving_ed_class_date_id' => $id]);
                    }
                }
            }
        }
    }
}
