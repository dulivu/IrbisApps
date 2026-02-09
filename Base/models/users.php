<?php

use Irbis\Json;
use Irbis\RecordSet;
use Irbis\Server;

// model: users

return [    
    '@delegate' => 'actor_id',
    '@unique' => 'email',
    '@form' => '@irbis/form-users.html',

    // -= properties =-

    'actor_id' => ['n1', 'target' => 'actors'],
    'email' => ['varchar', 'required' => true],
    'password' => ['varchar', 'required' => true, 'store' => '$encrypt'],
    'model_map' => ['text'],

    '$encrypt' => function ($value) {
        return password_hash($value ?: str_unique_id(), PASSWORD_DEFAULT);
    },

    // -= statics =-

    // Obtiene un usuario por sus credenciales
    '@findByCredentials' => function ($email, $pass) {
        $this->select(['email' => $email]);
        if (!$u = $this->count() == 1 ? $this[0] : false)
            return false;
        if (!$pass) 
            return false;
        if (!password_verify($pass, $u->password))
            return false;
        return $u;
    },

    // -= methods =-

    'getAccessList' => function () {
        return Json::decode($this->model_map ?: '{}');
    },

    // el arreglo que recibe o devuelve debe ser [read, write, create, delete]
    'modelAccess'=> function ($model_name, $access=false) {
        $map = Json::decode($this->model_map ?: '{}');
        if ($access) {
            $map[$model_name] = is_array($access) & count($access) == 4 ? 
                array_map(function ($i) { return intval(!!$i); }, $access) : [1,0,0,0];
            $this->model_map = Json::encode($map);
        }
        return $map[$model_name] ?? [0,0,0,0];
    }
];