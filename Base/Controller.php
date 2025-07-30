<?php

namespace IrbisApps\Base;

use Irbis\Controller as iController;
use Irbis\RecordSet;
use Irbis\Request;
use Irbis\Server;
use Irbis\Json;
use Twig\Loader\FilesystemLoader;
use Twig\Environment;
use Twig\Extension\DebugExtension;
use Irbis\Exceptions\HttpException;


class Controller extends iController {
    public static $name 		= 'irbis';
    public static $use_routes 	= true;
    public static $depends 		= [];
	public static $label 		= 'Irbis';
	public static $version 		= '1.0';
	public static $description 	= 'Aplicación base de gestión de aplicaciones';
	public static $author 		= 'jorge.quico@cavia.io';
	public static $assets 		= [
		'css' => [
			'assets/normalize.css',
			'assets/font-awesome.min.css',
			'https://unpkg.com/7.css',
			'assets/w7.css'
		],
		'js' => [
			'assets/functions.js',
			'components/menu-bar.js',
		]
	];

    public $user = null;
	public $route_logout = '/irbis/login';
	public $route_logged = '/';

	public function setupTwig () {
		$server = Server::getInstance();
		$loader = $this->loader = new FilesystemLoader(BASE_PATH);
		$environment = $this->environment = new Environment($loader, ['debug' => DEBUG_MODE]);

		$environment->addGlobal('DEBUG_MODE', DEBUG_MODE ? 1 : 0);
		$environment->addExtension(new DebugExtension());

		# establecemos la función de renderización
		$server->render = function ($view, $data) use ($environment) {
			die($environment->render($view, $data));
		};

		# por cada controlador agregado registrar en twig una ruta
		# así se puede usar rutas de vista e: @twig/index.html
		$server->on('addController', function ($controller, $name) use ($loader) {
			if (!$name) throw new \Exception("Twig: el controlador '{$controller->namespace()}' requiere un nombre alias");
			if ($controller::$views) $loader->addPath($controller->filePath($controller::$views), $name);
		});

		# cambiar las vistas de error por plantilla twig
		$server->setViewError([
			404 => '@irbis/errors/404.html',
			500 => '@irbis/errors/500.html',
		]);
	}

	public function setupAssemblies () {
		$server = Server::getInstance();
		if ($this->state('installed') ?: false) {
			$apps = new RecordSet('apps');	
			$apps->select([
				'active' => true,
				'file:<>' => 'IrbisApps/Base/Controller.php'
			]);
			foreach ($apps as $app) {
				$namespace = $app->namespace;
				$server->addController(new $namespace);
			}
		}
	}

	public function init () {
		session_start();
		$server = Server::getInstance();
		$request = Request::getInstance();
		$user = $this->user = $this->logup($request);

		if ($request->is(JSON_REQUEST)) {
			# Procesa solicitudes JSON y prepara las cabeceras de respuesta
			# de existir una vista la quita, se debe forzar el envio de data json
			# si hay un error, acorta el objeto json ya que suele ser muy largo
			$_POST += Json::decode($request->getRawContent('{}'));

			$server->on('response', function ($response) {
				$response->setHeader("Access-Control-Allow-Origin: *");
				$response->setHeader("Content-Type: application/json; charset=UTF-8");
				$response->setView(null);
				// esta sección transforma el trace del error para no generar "error"
				// al convertirlo en un json por recursividad
				$error = $response->getData('error');
				if ($error) {
					if (isset($error['trace'])) {
						$new_trace = [];
						foreach ($error['trace'] as $trace) {
							$new_trace[] = ($trace['file'] ?? $trace['function'])." (".($trace['line'] ?? 0).")";
						}
						$error['trace'] = $new_trace;
						$response->setData('error', $error);
					}
				}
			});
		} else {
			# agrega la ruta de los componentes que tenga cada controlador registrado
			# en el servidor, en una variable 'apps' para que la vista 'layout'
			# pueda utilizar y cargar los componentes automáticamente
			$server->on('response', function ($response) use ($server, $user) {
				$apps = $response->getData('apps') ?: [];
				$server->forEachController(function ($controller) use (&$apps) {
					$assets = $controller::$assets ?? [];
					$assets['js'] = $assets['js'] ?? [];
					$assets['css'] = $assets['css'] ?? [];

					$assets['js'] = array_map(function ($asset) use ($controller) {
						if (str_starts_with($asset, 'http')) return $asset;
						if (str_starts_with($asset, '/')) return $asset;
						return "/{$controller->namespace()}/$asset";
					}, $assets['js']);
					$assets['css'] = array_map(function ($asset) use ($controller) {
						if (str_starts_with($asset, 'http')) return $asset;
						if (str_starts_with($asset, '/')) return $asset;
						return "/{$controller->namespace()}/$asset";
					}, $assets['css']);

					$apps[] = [
						'name' => $controller::$name,
						'namespace' => $controller->namespace(),
						'label' => $controller::$label ?? False,
						'assets' => $assets
					];
				});
				$response->setData('apps', $apps);
				$response->setData('user', $user);
			});
		}
	}

	/**
	 * @route /
	 * @route /irbis
	 */
	public function irbis ($request) {
		$user = $this->controller('irbis')->session();

		$set = (new RecordSet('apps'))->select();
		return ["@irbis/irbis.html", [
			'set' => $set,
		]];
	}

	private function logup ($request) {
		if ($request->path == '/irbis/login') return null;
		try {
			$user = new RecordSet('users');
			$user->select($_SESSION['user'] ?? 0);
		} catch (\PDOException $e) {
			if ($e->getCode() == 'HY000') { # no existe tabla
				redirect('/irbis/login');
			} else throw $e;
		}
		return $user->count() == 1 ? $user[0] : null;
	}
    
    public function session () {
		$request = Request::getInstance();
		if (!$this->user) {
			if ($request->is(JSON_REQUEST))
				throw new HttpException("Unauthorized", 401);
			redirect($request->base.'/irbis/login?redirect='.base64_encode($request.''));
		}
		return $this->user;
	}

	/**
	 * @route /irbis/login
	 */
	public function login ($request) {
		$installed = $this->state('installed') ?: false;
		$install_msg = 'Ingrese usuario y contraseña para la instalación';
		$data = [
			'site_title' => 'Iniciar sesión',
			'message' => $installed ? '' : $install_msg
		];

		if ($request->is('POST')) {
			$username = $request->input('username');
			$userpass = $request->input('userpass');
			$redirect = $request->query('redirect');
			
			$users = new RecordSet('users');
			$user = $users->selectByCredentials($username, $userpass);

			if ($user) {
				$_SESSION['user'] = $user->id;
				$redirect = $redirect ? base64_decode($redirect) : $this->route_logged;
				redirect($redirect);
			} else {
				$data = ['message' => '¡Datos incorrectos!'];
			}
		}

		return ['@irbis/login.html', $data];
	}

	/**
	 * @route /irbis/logout
	 */
	public function logout ($request) {
		session_destroy();
		redirect($this->route_logout);
	}

	/**
	 * @route /irbis/user-change-password
	 */
	public function userChangePassword ($request) {
		$user = $this->controller('irbis')->session();
		$new_password = $request->input('password');
		if ($new_password) $user->password = $new_password;
		return "Contraseña actualizada";
	}

	/**
	 * @route /irbis/update-list-apps
	 */
	public function updateListApps ($request) {
		$user = $this->controller('irbis')->session();
		$apps = new RecordSet('apps');
		$count = $apps->count();
		$added = 0;

		$available_apps = glob('*Apps/*/Controller.php');
		foreach ($available_apps as $app) {
			$apps->select(['file' => $app]);
			if ($count == $apps->count()) {
				$apps->insert(['file' => $app]);
				$apps[$count]->transmuteData();
				$added++;
			} $count = $apps->count();
		}
		return "Lista de aplicaciones actualizada se agregaron: $added aplicaciones";
	}

	/**
	 * @verb GET
	 * @route /record/(:any)/(:num)
	 */
	public function actionRecordSelectById ($request, $response) {
		$user 		= $this->session();
		$model 		= $request->path(0);
		$id 		= $request->path(1);
		$set 		= new RecordSet($model);

		$set->select((int) $id);
		if (!$set->count())
			throw new HttpException('Record Not Found', 404);

		$view = $set->{'@form'} ?: null;
		return $view ? [$view, $set[0]] : $set[0];
	}

	/**
	 * @verb PUT
	 * @route /record/(:any)/(:num)/(:any)
	 */
	public function actionRecordActionById ($request, $response) {
		$user 		= $this->session();
		$model 		= $request->path(0);
		$id 		= $request->path(1);
		$action 	= $request->path(2);
		$set 		= new RecordSet($model);

		$set->select((int) $id);
		if (!$set->count())
			throw new HttpException('Record Not Found', 404);
		return $set[0]->{$action}();
	}

	/**
	 * @verb GET
	 * @route /record/(:any)/select
	 * @route /record/(:any)/select/(:any)
	 */
	public function actionRecordSelect ($request, $response) {
		$user 		= $this->session();
		$model 		= $request->path(0);
		$limit 		= $request->path(1) ?: '0-50';
		$query 		= $request->query('*') ?: null;
		$order 		= [];
		$set 		= new RecordSet($model);

		$set->select($query, $order, $limit);

		$view = $set->{'@list'} ?: null;
		return $view ? [$view, $set] : $set;
	}

	/**
	 * @verb POST
	 * @route /record/(:any)/insert
	 */
	public function actionRecordInsert ($request, $response) {
		$user 		= $this->session();
		$model 		= $request->path(0);
		$inserts 	= $request->input('*');
		$set	 	= new RecordSet($model);

		$set->insert($inserts);

		$response->json = $set->debug()[$model];
	}

	/**
	 * @verb PUT
	 * @route /record/(:any)/update/(:any)
	 */
	public function actionRecordUpdate ($request, $response) {
		$user 		= $this->session();
		$model 		= $request->path(0);
		$ids 		= explode(",", $request->path(1));
		$updates 	= $request->input('*');
		$set 		= new RecordSet($model);
		
		$set->select($ids);
		$set->update($updates);
		
		$response->json = $set->debug()[$model];
	}
}