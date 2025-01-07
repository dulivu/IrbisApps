<?php

namespace IrbisApps\Manager;

use Irbis\Controller as iController;
use Irbis\Server;
use Irbis\Request;
use Irbis\RecordSet;
use Irbis\DataBase;

/**
 * Brinda una funcionalidad básica de inicio de sesión
 * vistas estandar para gestión de modelos, gestión de otras aplicaciones
 * 
 * @package irbis manager
 * @author Jorge Quico C. <jorge.quico@cavia.io>
 * @version 1.0
 * 
 * 1) gestionar que módulos existen y cuales estan instalados
 * crear, editar y ver registros lo básico
 * mantener una lista de modelos habilitados para el cliente
 * cargar automáticamente los modulos instalados
 * usuarios, permisos y roles por modelo
 * 
 */


class Controller extends iController {
	public $name 			= 'irbis';
	public $has_routes 		= true;
	public $installable 	= false;
	public $depends 		= ['IrbisApps/AdapterTwig', 'IrbisApps/AdapterRest'];

	# atributos particulares
	private $session_user 	= null;
	private $applications 	= [];
	private $desktop_path 	= '/irbis/desktop';

    # obtiene todas la aplicaciones disponibles "installable = true"
	# @param $force [bool] fuerza la carga de aplicaciones
	# @return [Irbis\Controller]
	public function availableApps ($force = false) {
		if (!$force && $this->applications)
			return $this->applications;
		# considera todos los directorios estilo PackageApps/AppName
		# y que contengan un archivo Controller.php
		$apps = glob('*Apps/*/Controller.php');
		foreach ($apps as $app) {
			$namespace = path_to_namespace($app);
			$controller = new $namespace();
			if ($controller->installable)
				$this->applications[] = $controller;
		}
		return $this->applications;
	}

    # de las aplicaciones disponibles filtra las ensambladas
	# @return [Irbis\Controller]
	public function enabledApps () {
		$list = $this->state('assembled_apps') ?: [];
		$apps = $this->availableApps();
		
		return array_filter($apps, function ($app) use ($list) {
			return in_array($app->key(), $list);
		});
	}

    public function __construct () {
        parent::__construct();
        $server = Server::getInstance();
        # esta primera condicional es para validar que este objeto
        # no se haya construido y agregado previamente 
        if (!$server->getController('IrbisApps/Manager')) {
            $server->on('addController', function ($controller) use ($server) {
                if ($controller->key() == 'IrbisApps/ModuleMap')
                    foreach ($controller->enabledApps() as $app)
                        $server->addController($app);
            });
        }
	}

    

	# devuelve un aplicación disponible por espacio de nombre
	# sólo si la aplicación no está previamente ensamblada
	# @return Irbis/Controller || false
	public function toAssembleApp (string $namespace) {
		$assembled_apps = $this->state('assembled_apps') ?: [];
		if (!in_array($namespace, $assembled_apps)) {
			$apps = $this->availableApps();
			foreach ($apps as $app)
				if ($app->key() == $namespace)
					return $app;
		}
		return false;
	}

	public function assemble () {
		$users = new RecordSet('users');
		$users->bind();
		$users->insert([
			"name" => "Administrador",
			"email" => "admin",
			"password" => "admin"
		]);

		$this->state('installed', True);
	}

	/**
	 * @route /irbis/install
	 */
	public function install ($request) {
		//$this->session();

		if ($request->is('POST')) {
			if (!$this->state('installed'))
				$this->assemble();
			
			$apps_to_assemble = $request->input('apps', []);
			$assembled_apps = $this->state('assembled_apps') ?: [];

			foreach ($apps_to_assemble as $namespace) {
				if ($app = $this->toAssembleApp($namespace)) {
					$this->server->addController($app);
					$app->assemble();
					$assembled_apps[] = $app->key();
				}
			}
			$this->state('assembled_apps', $assembled_apps);
			redirect($this->desktop_path);
		}
		
		return ["@irbis/install.html", [
			"desktop_path" => $this->desktop_path,
			"irbis_installed" => !!$this->state('installed'),
			"applications" =>  $this->availableApps(),
		]];
	}

	/**
	 * @return [[model_key, model_name, [read, create, write, delete]]]
	 */
	public function availableModels ($all=false) {
		$user = $this->session();
		$model_list = [];
		$this->server->forEachController(function ($controller) use (&$model_list, $user, $all) {
			foreach ($controller->file('models/*.php') as $model_path) {
				$model_key = basename($model_path, '.php');
				$model_skeleton = include($model_path);

				if ($model_name = $model_skeleton['__label'] ?? $all) {
					$model_access = $user->modelAccess($model_key);
					if ($model_access[0])
						$model_list[] = [$model_key, $model_name, $model_access];
				}
			}
		});
		return $model_list;
	}

	/**
	 * @route /irbis/desktop
	 */
	public function desktop ($request) {
		$user = $this->session();
		return ["@irbis/desktop.html", [
			'user' => $user,
			'apps' => array_merge([$this], $this->enabledApps()),
			'models' => $this->availableModels()
		]];
	}

	/**
	 * @route /irbis/model/(:any)/select
	 */
	public function callModelSelect ($request) {
		$user 		= $this->session();
		$model 		= $request->path(0);
		$access 	= $user->modelAccess($model);

		if ($access[0] == 0) {
			header("HTTP/1.1 401 Unauthorized");
			return [];
		}

		$query 		= $request->query('*') ?: null;
		$order 		= $request->input('order') ?: [];
		$limit 		= $request->input('limit') ?: '0-50';
		$fields 	= $request->input('fields') ?: ['id', 'name'];
		$records 	= new RecordSet($model);

		$records->select($query, $order, $limit);

		foreach ($records as $record)
			$result[] = array_map(function ($i) use ($record) { 
				return $record->{$i}; 
			}, $fields);
		return $result ?? [];
	}

	/**
	 * @route /irbis/model/(:any)/insert
	 */
	public function callModelInsert ($request) {
		$user 		= $this->session();
		$model 		= $request->path(0);
		$access 	= $user->modelAccess($model);

		if ($access[0] == 0) {
			header("HTTP/1.1 401 Unauthorized");
			return [];
		}

		$model 		= new RecordSet($model);

		if ($request->is('POST')) {
			if ($access[1] == 0) {
				header("HTTP/1.1 401 Unauthorized");
				return [];
			}
			
			$values = $request->input('*');
			$model->insert($values);
			redirect("/irbis/model/{$model->__name}/update/{$model[0]->id}");
		}

		return ["@irbis/model_form.html", [
			'user' => 		$user,
			'apps' => 		array_merge([$this], $this->enabledApps()),
			'model' => 		$model,
			'properties' => $model->__properties,
		]];
	}

	/**
	 * @route /irbis/model/(:any)/update/(:num)
	 */
	public function callModelUpdate ($request) {
		$user 		= $this->session();
		$model 		= $request->path(0);
		$access 	= $user->modelAccess($model);

		if ($access[0] == 0) {
			header("HTTP/1.1 401 Unauthorized");
			return [];
		}

		$model 		= new RecordSet($model);
		$modelID 	= $request->path(1);
		
		$model->select($modelID);

		if ($request->is('POST')) {
			if ($access[2] == 0) {
				header("HTTP/1.1 401 Unauthorized");
				return [];
			}
			
			$values = $request->input('*');
			$model->update($values);
		}

		return ["@irbis/model_form.html", [
			'user' => $user,
			'apps' => array_merge([$this], $this->enabledApps()),
			'model' => $model,
			'properties' => $model->__properties,
		]];
	}

	public function session () {
		/**
		 * valida que exista una sesión creada, o redirige al inicio de sesion
		 * otros controladores pueden llamar a este método para validar la sesión
		 * ejemplo:
		 * 
		 * class Controller {
		 *   public $has_routes = true;
		 * 	 public function route_one ($request) {
		 *		$manager = $this->server->getController('irbis');
		 *		$user = $manager->session($request, '/route_one');
		 *   }
		 * }
		 * 
		 * el segundo parámetro indica a donde debe redirigir si no hay sesión
		 * si no se envía un segundo parámetro sólo devuelve cabecera 401
		 */
		$unauthorized = function ($request) {
			header("HTTP/1.1 401 Unauthorized");
			if ($request->is(JSON_REQUEST))
				die("");
			redirect($request->base.'/irbis/login?redirect='.base64_encode($request.''));
		};

		$request = Request::getInstance();
		if (!isset($_SESSION['user'])) {
			header("HTTP/1.1 401 Unauthorized");
			$unauthorized($request);
		} elseif ($this->session_user === null) {
			$users = new RecordSet('users');
			$users->select($_SESSION['user']);
			if ($users->count() != 1) {
				header("HTTP/1.1 401 Unauthorized");
				$unauthorized($request);
			}
			$this->session_user = $users[0];
		}
		return $this->session_user;
	}

	/**
	 * @route /irbis/login
	 * 
	 * pantalla de inicio de sesión, en caso de no existir la sesión
	 * el método de autenticación 'session' en este controlador
	 * redirigirá a esta ruta para que el usuario inicie sesión
	 */
	public function login ($request) {
		if ($request->is('POST')) {
			$username = $request->input('username');
			$userpass = $request->input('userpass');
			$redirect = $request->query('redirect');
			
			$users = new RecordSet('users');
			$user = $users->selectByCredentials($username, $userpass);

			if ($user) {
				$_SESSION['user'] = $user->id;
				$redirect = $redirect ? base64_decode($redirect) : '/';
				redirect($redirect);
			} else {
				$data = [
					'status' => 'error',
					'message' => '¡Datos incorrectos!',
				];
			}
		}

		return ['@irbis/login.html', $data ?? []];
	}

	/**
	 * @route /irbis/logout
	 * 
	 * ruta para cerrar la sesión, esta volverá a redigir
	 * a la ruta incial que puede ser tomada por otro controlador
	 */
	public function logout ($request) {
		session_start();
		session_destroy();
		redirect('/');
	}

	/**
	 * @route /irbis/login/config
	 * 
	 * TODO: ruta pensada para ser llamada desde el cliente
	 * para cambiar la contraseña del usuario, pero no muestra
	 * ninguna vista, llamar por fetch js
	 */
	public function change_password ($request) {
		$config = new ConfigFile($this->file('auth.ini'));
		$pass = $request->input('userpass');
		$config->set('admin', password_hash($pass, PASSWORD_DEFAULT));
	}

	/**
	 * @route /
	 */
	public function index ($request) {
		if (!$this->state('installed'))
			redirect('/irbis/install');
		# NOTE: heredar esta ruta para mostrar un sitio web
		$this->session();
		redirect($this->desktop_path);
	}
}