<?php

namespace Anwardote\ExportImportWpLaravel\Commands;

use Anwardote\ExportImportWpLaravel\Models\WpUser;
use Anwardote\ExportImportWpLaravel\Models\WpUsermeta;
use Axilweb\Acl\App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ImportWpUserCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'wp_import:users';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command to import users to laravel users.';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        if ($this->ask('Download User Avatars?(y/n)', 'n') == 'y') {
            $avatarUsers = WpUsermeta::query()->where('meta_key', 'nwds_avatar')->get();
            foreach ($avatarUsers as $avatarUser) {
                if ($avatarUser->meta_key) {
                    $avatar = unserialize($avatarUser->meta_value);
                    if (isset($avatar['url'])) {
                        $avatarUrl = $avatar['url'];
                        $filename = basename($avatarUrl);
                        $extention = explode('.', $filename);
                        $extention = end($extention);
                        $fileContent = file_get_contents($avatarUrl);
                        $this->info('Downloading .. :'.$avatarUrl);
                        if ($fileContent) {
                            Storage::disk('public')->put("/avatars/".$avatarUser->user_id.'.jpg', $fileContent);
                        }
                    }
                }
            }
        }

        if ($this->ask('Will truncate users table?', 'y') == 'y') {
            User::query()->truncate();
            DB::table('model_has_roles')->truncate();
        }

        if ($this->ask('Import All Users from wp_users?', 'y') == 'y') {
            $wpUsers = WpUser::query()
//                ->limit(100)
            ;
            foreach ($wpUsers->cursor() as $wpUser) {
                $userData = $this->getUserProfile($wpUser);

                try {
                    $user = User::query()->create(Arr::except($userData, 'role'));
                    if ($userData['role']) {
                        $user->assignRole($userData['role']);
                    }
                    $this->info("Running: - ".$wpUser->ID);
                } catch (\Exception $exception) {
                    ray($exception->getMessage());
                    ray($userData);
                }
            }
        }
    }

    protected function getUserProfile($user)
    {
        $userMeta = (object)$this->getUserMeta(
            $user->userMeta()->whereIn('meta_key', [
                'first_name',
                'last_name',
                'phone',
                'em_phone',
                'street',
                'apt_ste',
                'city',
                'state',
                'zip',
                'gate',
                'cross_street1',
                'cross_street2',
                'residence_state',
                'sex',
                'dob',
                'nwds_bio',
                'nwds_instrctor_show',
                'nwds_education',
                'nwds_designation',
                'wp_9t854h_capabilities',
                'nwds_avatar',
            ])->get()
        );

        $firstName = $userMeta->first_name ?? '';
        $instructorShow = $userMeta->nwds_instrctor_show ?? '';
        $dob = $userMeta->dob ?? null;
        $role = (array)unserialize($userMeta->wp_9t854h_capabilities);
        $role = array_keys($role);

        $avatar = null;
        if($userMeta->nwds_avatar ?? ''){
            $avatar = "avatars/{$user->ID}.jpg";
        }

        $userType = 3;
        if (in_array('administrator', $role)) {
            $userType = 1;
        } elseif (in_array('instructor', $role)) {
            $userType = 2;
        }

        return [
            'id' => $user->app_uuid,
            'first_name' => empty($firstName) ? $user->user_login : $firstName,
            'last_name' => $userMeta->last_name ?? '',
            'email' => $user->user_email,
            'password' => $user->user_pass,
            'dob' => empty($dob) ? null : Carbon::parse($dob)?->format('Y-m-d'),
            'gender' => $userMeta->sex ?? 'male',
            'phone' => $userMeta->phone ?? '',
            'em_phone' => $userMeta->em_phone ?? '',
            'legal_residence_state' => $userMeta->residence_state ?? '',
            'street' => $userMeta->street ?? '',
            'apartment' => $userMeta->apt_ste ?? '',
            'city' => $userMeta->city ?? '',
            'state' => $userMeta->state ?? '',
            'zip_code' => $userMeta->zip ?? '',
            'gate_code' => $userMeta->gate ?? '',
            'cross_street_1' => $userMeta->cross_street1 ?? '',
            'cross_street_2' => $userMeta->cross_street2 ?? '',
            'designation' => $userMeta->nwds_designation ?? '',
            'education' => $userMeta->nwds_education ?? '',
            'bio' => $userMeta->nwds_bio ?? '',
            'is_invisible' => $instructorShow != 'NO',
            'is_active' => 1,
            'type' => $userType,
            'role' => $role,
            'avatar' => $avatar,
        ];
    }

    protected function getUserMeta($meta)
    {
        return collect($meta)->mapWithKeys(function ($item) {
            return [$item->meta_key => $item->meta_value];
        })->toArray();
    }
}
