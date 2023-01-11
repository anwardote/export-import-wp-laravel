<?php

namespace Anwardote\ExportImportWpLaravel\Services;

class ExportRoleService
{

    public static function allRoles()
    {
        return [
            static::studentRole(),
            static::subscriberRole(),
            static::instructorRole(),
            static::adminRole(),
            static::adminWithInstructorRole(),
            static::instructorWithAdminRole(),
            static::editorRole()
        ];
    }

    public static function adminRole()
    {
        return 'a:1:{s:13:"administrator";b:1;}';
    }

    public static function editorRole()
    {
        return 'a:1:{s:6:"editor";b:1;}';
    }


    public static function adminWithInstructorRole()
    {
        return 'a:2:{s:13:"administrator";b:1;s:10:"instructor";b:1;}';
    }

    public static function instructorRole()
    {
        return 'a:1:{s:10:"instructor";b:1;}';
    }

    public static function instructorWithAdminRole()
    {
        return 'a:2:{s:10:"instructor";b:1;s:13:"administrator";b:1;}';
    }

    public static function subscriberRole()
    {
        return 'a:1:{s:10:"subscriber";b:1;}';
    }

    public static function studentRole()
    {
        return 'a:1:{s:7:"student";b:1;}';
    }

    public function getRoleIds($cap)
    {
        $roles = unserialize($cap);
        if ($roles) {
            if (!$roles) {
                return 'Student';
            }
        }
    }


}
