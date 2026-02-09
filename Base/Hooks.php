<?php
namespace IrbisApps\Base;

use Irbis\Controller;
use Irbis\Orm\RecordSet;
use Irbis\Interfaces\ComponentInterface;
use Irbis\Interfaces\HooksInterface;
use Irbis\Traits\Component;


class Hooks implements ComponentInterface, HooksInterface {
    use Component;

    public function install () {
        $users = RecordSet::bind('users');

        $users->select(['email' => 'admin']);
        if ($users->count() == 0) {
            $users->insert([
                "name" => "Administrador",
                "email" => 'admin',
                "password" => 'admin'
            ]);
        }

        $directories = RecordSet::bind('directories');
    }

    public function uninstall () {
        return;
    }
}