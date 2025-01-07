<?php

use Irbis\Json;

return [
	'__label' => 'Usuarios',
	
	'name' => ['varchar', 'required' => true],
	'active' => ['boolean', 'default' => 0],
	'email' => ['varchar', 'primary_key' => true],
	'password' => ['varchar', 'required' => true, 'store' => 'encrypt'],
	'model_map' => ['text'],

	'token_type' => ['varchar'], # acción a realizar para reestablecimiento de contraseña
	'token_password' => ['varchar', 'store' => 'encrypt', 'retrieve' => 'nullify'],

	# ============================================================================

	'encrypt' => function ($value) {
		return password_hash($value ?: str_unique_id(), PASSWORD_DEFAULT);
	},

	'getAccessList' => function () {
		return JSON::decode($this->model_map ?: '{}');
	},

	# el arreglo que recibe o devuelve debe ser [read, write, create, delete]
	'modelAccess'=> function ($model_name, $access=false) {
		$map = JSON::decode($this->model_map ?: '{}');
		if ($access) {
			$map[$model_name] = is_array($access) & count($access) == 4 ? 
				array_map(function ($i) { return intval(!!$i); }, $access) : [1,0,0,0];
			$this->model_map = JSON::encode($map);
		}
		return $map[$model_name] ?? [0,0,0,0];
	},

	/**
	 * valida credenciales y devuelve el usuario, no requiere capturar un registro
	 * 
	 * @param string $email string, nombre de usuario
	 * @param string $pass string, contraseña de usuario
	 * @param string $token_password string
	 * 
	 * @return Record(user)|false
	 */
	'@selectByCredentials' => function ($email, $pass) {
		$this->select(['email' => $email]);
		if (!$u = $this->count() == 1 ? $this[0] : false) 
			return false;
        if (!$pass) 
            return false;
        if (!password_verify($pass, $u->password))
            return false;
		return $u;
	},
];