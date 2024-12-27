<?php

namespace IrbisApps\Authorization;

use Irbis\Controller as iController;
use Irbis\Request;


class Controller extends iController {
    public $name            = 'auth';
    public $installable     = false;
    public $has_routes      = true;
    public $depends 		= ['IrbisApps/AdapterTwig'];

    public $session_user    = null;
    public $authorized_path = '/';

    public function init () {
        session_start();
        if (!$this->state('users.admin')) {
            $pass = password_hash('admin', PASSWORD_DEFAULT);
            $this->state('users.admin', $pass);
        }
    }

    public function register_user ($username, $password) {
        if ($this->state('users.'.$username))
            throw new \Exception("El usuario ya existe!");
        $password = password_hash($password, PASSWORD_DEFAULT);
        $this->state('users.'.$username, $password);
    }

    public function change_session_password ($password) {
        $user = $this->session();
        $password = password_hash($password, PASSWORD_DEFAULT);
        $this->state($user, $password);
    }

    /**
     * @route /
     */
    public function index () {
        return ['@auth/index.html', [
            'user' => $_SESSION['user'] ?? null
        ]];
    }

    /**
     * @route /authorization/login
     */
    public function login ($request) {
        $data = [];

        if ($request->is(POST_REQUEST)) {
            $username       = 'users.'.$request->input('username');
            $password       = $request->input('password');
            $redirect       = $request->query('redirect');
            $userpswd       = $this->state($username);

            # verificación de usuario
            # -----------------------------
            if (!$userpswd)
                $data['message'] = 'Usuario no encontrado!';
            if (!password_verify($password, $userpswd))
                $data['message'] = 'Contraseña incorrecta!';
            # -----------------------------

            if (!($data['message'] ?? false)) {
                $_SESSION['user'] = $username;
                $redirect = $redirect ? base64_decode($redirect) : $this->authorized_path;
                redirect($redirect);
            }
        }

        return ['@auth/login.html', $data];
    }

    /**
     * @route /authorization/logout
     */
    public function logout () {
		session_destroy();
		redirect($this->authorized_path);
    }

    public function session () {
        $request = Request::getInstance();
        if (!isset($_SESSION['user'])) {
            header("HTTP/1.1 401 Unauthorized");
            if ($request->is(JSON_REQUEST))
                die("");
            redirect($request->base.'/authorization/login?redirect='.base64_encode($request.''));
        } elseif ($this->session_user === null) {
            $this->session_user = $_SESSION['user'];
        }
        return $this->session_user;
    }
}