<?php

namespace IrbisApps\Auth;

use Irbis\Controller as iController;
use Irbis\Request;


class Controller extends iController {
    public $name            = 'auth';
    public $installable     = false;
    public $has_routes      = true;
    public $depends 		= ['IrbisApps/AdapterTwig'];

    public $session_user    = null;
    public $auth_path       = '/';

    public function init () {
        session_start();
        if (!$this->state('users.admin')) {
            $pass = password_hash('admin', PASSWORD_DEFAULT);
            $this->state('users.admin', $pass);
        }
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
            $pass           = $this->state($username);

            # verificación de usuario
            # -----------------------------
            if (!$pass)
                $data['message'] = 'Usuario no encontrado!';
            if (!password_verify($password, $pass))
                $data['message'] = 'Contraseña incorrecta!';
            # -----------------------------

            if (!($data['message'] ?? false)) {
                $_SESSION['user'] = $username;
                $redirect = $redirect ? base64_decode($redirect) : $this->auth_path;
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
		redirect($this->auth_path);
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

    /**
     * @route /authorization/password
     */
    public function change_password ($request) {
        $user = $this->session();
        $password = $request->input('password');
        $password = password_hash($password, PASSWORD_DEFAULT);
        $this->state($user, $password);
    }
}