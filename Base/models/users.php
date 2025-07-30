<?php

use Irbis\Json;
use Irbis\RecordSet;
use Irbis\Server;

return [
	'@delegate' => 'actor_id',
	'@form' => '@irbis/users/form.html',

	'actor_id' => ['n1', 'target' => 'actors'],
	'email' => ['varchar', 'required' => true, 'primary_key' => true],
	'password' => ['varchar', 'required' => true, 'store' => 'encrypt'],
	'model_map' => ['text'],

	'token_type' => ['varchar'], # acción a realizar para reestablecimiento de contraseña
	'token_password' => ['varchar', 'store' => 'encrypt', 'retrieve' => 'nullify'],

	# ============================================================================

	'encrypt' => function ($value) {
		return password_hash($value ?: str_unique_id(), PASSWORD_DEFAULT);
	},

	'getAccessList' => function () {
		return Json::decode($this->model_map ?: '{}');
	},

	# el arreglo que recibe o devuelve debe ser [read, write, create, delete]
	'modelAccess'=> function ($model_name, $access=false) {
		$map = Json::decode($this->model_map ?: '{}');
		if ($access) {
			$map[$model_name] = is_array($access) & count($access) == 4 ? 
				array_map(function ($i) { return intval(!!$i); }, $access) : [1,0,0,0];
			$this->model_map = Json::encode($map);
		}
		return $map[$model_name] ?? [0,0,0,0];
	},

	# Obtiene un usuario por sus credenciales
	# si la tabla de usuario no existe, corre la instalación
	'@selectByCredentials' => function ($email, $pass) {
		try {
			$this->select(['email' => $email]);
		} catch (\PDOException $e) {
			if ($e->getCode() == 'HY000') { # no existe tabla
				# enlaza el modelo de aplicaciones
				$apps = new RecordSet('apps');
				$apps->bind();
				
				# crear la aplicación base
				$apps->insert(['file' => 'IrbisApps/Base/Controller.php']);
				$apps[0]->transmuteData();
				$apps[0]->assembled = true;
				$apps[0]->active = true;
				
				# enlaza el modelo de usuario y actores
				$this->bind();

				# crear el usuario administrador
				$this->insert([
					"name" => "Administrador",
					"email" => $email,
					"password" => $pass
				]);

				# cambia el estado de la aplicacion
				$server = Server::getInstance();
				$server->getController('irbis')->state('installed', 1);
				
				return $this[0];
			} else throw $e;
		}
		if (!$u = $this->count() == 1 ? $this[0] : false) 
			return false;
        if (!$pass) 
            return false;
        if (!password_verify($pass, $u->password))
            return false;
		return $u;
	}
];