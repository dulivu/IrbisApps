<?php

namespace IrbisApps\Base;

use Irbis\Controller as iController;
use Irbis\RecordSet;
use Irbis\Request;


class Controller extends iController {
    public $name = 'irbis';
    public $has_routes = true;
    public $installable = false;
    public $depends = [
        'IrbisApps/AdapterTwig', 
        'IrbisApps/AdapterRest',
        'IrbisApps/Tools',
    ];

	public $logged_path = '/';
    public $user = null;

	public function init () {
		session_start();
		if (isset($_SESSION['user'])) {
			$users = new RecordSet('users');
			$users->select($_SESSION['user']);
			if ($users->count() == 1)
				$this->user = $users[0];
		}

		$irbis = $this;
		$this->server->on('response', function ($response) use ($irbis) {
			if ($response->view) {
				if (is_null($response->data))
					$response->data = ['user' => $irbis->user];
				elseif (is_array($response->data) and !isset($response->data['user'])) {
					$response->data['user'] = $irbis->user;
				}
			}
		});
	}
    
    public function session () {
		$request = Request::getInstance();
		if (!$this->user) {
			if ($request->is(JSON_REQUEST))
				die("");
			redirect($request->base.'/irbis/login?redirect='.base64_encode($request.''));
		}
		return $this->user;
	}

	private function bindUsersModel ($username, $userpass) {
		$users = new RecordSet('users');
		$users->bind();
		$users->insert([
			"name" => "Administrador",
			"email" => $username,
			"password" => $userpass
		]);
		
		return $users[0];
	}

	/**
	 * @route /irbis/login
	 */
	public function login ($request) {
		if ($request->is('POST')) {
			$username = $request->input('username');
			$userpass = $request->input('userpass');
			$redirect = $request->query('redirect');
			
			try {
				$users = new RecordSet('users');
				$user = $users->selectByCredentials($username, $userpass);
			} catch (\PDOException $e) {
				if ($e->getCode() == 'HY000') {
					$user = $this->bindUsersModel($username, $userpass);
				} else throw $e;
			}

			if ($user) {
				$_SESSION['user'] = $user->id;
				$redirect = $redirect ? base64_decode($redirect) : $this->logged_path;
				redirect($redirect);
			} else {
				$data = ['message' => '¡Datos incorrectos!'];
			}
		}

		return ['@irbis/login.html', $data ?? []];
	}

	/**
	 * @route /irbis/logout
	 */
	public function logout ($request) {
		session_destroy();
		redirect($this->logged_path);
	}

	/**
	 * @route /
	 */
	public function index () {
		return ['@irbis/index.html', ['user' => $this->user]];
	}
}