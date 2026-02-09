<?php
namespace IrbisApps\Base;

use Irbis\Server;
use Irbis\Controller as iController;
use Irbis\Orm\RecordSet;
use Irbis\Orm\Record;
use Irbis\Exceptions\HttpException;
use Irbis\Request;


class Controller extends iController {
    public static $name 		= 'irbis';
    public static $routable 	= true;

    public static $label 		= 'Irbis';
    public static $version 		= '1.0';
    public static $description 	= 'Aplicación base';
    public static $author 		= 'jorge.quico@cavia.io';

    /**
     * @route ?/
     */
    final public function index ($request) {
        $path = Server::getInstance()->getState('server.backoffice');
        if ($path)
            redirect($path);
        return 'Welcome to irbis base application';
    }

    /**
     * @route /login
     */
    final public function login ($request) {
        $data = ['message' => 'Presione enviar para iniciar sesión.'];

        if ($request->method == 'POST') {
            $username = $request->input('username');
            $userpass = $request->input('userpass') ?: '';
            $redirect = $request->query('redirect');
            
            $user = (new RecordSet('users'))->findByCredentials($username, $userpass);

            if ($user) {
                session_start();
                $_SESSION['user'] = $user->id;
                $path = Server::getInstance()->getState('server.backoffice') ?: '/';
                redirect($redirect ? base64_decode($redirect) : $path);
            } else {
                $data['message'] = '¡Datos incorrectos!';
            }
        }

        return ['@irbis/form-login.html', $data];
    }

    /**
     * @route /logout
     */
    final public function logout ($request) {
        session_start();
        session_destroy();
        redirect('/login');
    }

    /**
     * @content HTML
     * @verb GET,POST
     * @auth user
     * @route /record/(:any)/(:num)
     */
    final public function actionRecord ($request, $response) {
        // método para ser usado como un formulario de edición de registro
        // GET: devuelve el formulario configurado para visualizacion
        // POST: actualiza el registro y vuelve a mostrar el formulario
        $view       = $request->query('view');
        $model      = $request->path(0);
        $id         = (int) $request->path(1);

        // TODO: si id = 0, crear un nuevo registro

        $record     = Record::find($model, $id);
        if (!$record)
            throw new HttpException(404, "Record '$model:$id' Not Found");

        if ($request->method == Request::POST) {
            $record->update($request->input('*'));
        }

        // TODO: 
        // - manejo de archivos, ó campos que guarden archivos
        // - manejo de relaciones N-1, 1-N, N-N

        $response->view($view ?: $record->{'@form'} ?: "@irbis/form-record.html");
        $response->append('record', $record);
    }

    /**
     * @content JSON
     * @auth user
     * @route /record/(:any)/(:num)/(:any)
     */
    final public function actionRecordCall ($request, $response) {
        // método para ser usado para llamar métodos de un registro
        // pensado para componentes complejos javascript 
        $model 		= $request->path(0);
        $id 		= (int) $request->path(1);
        $action 	= $request->path(2);
        $record 	= Record::find($model, $id);
        $params     = $request->input('*') ?: [];

        if (!$record)
            throw new HttpException(404, "Record '$model:$id' Not Found");
        
        $response->body( $record->{$action}(...$params) );
    }

    /**
     * @content HTML
     * @verb GET
     * @auth user
     * @route /recordset/(:any)
     */
    final public function actionRecordSet ($request, $response) {
        // método para ser usado como un listado de registros
        // sólo recibe peticiones GET, ya que su propósito es mostrar datos
        // la manipulación de datos se realiza mediante otros métodos
        $model      = $request->path(0);
        $query      = $request->query('*') ?: null;
        // TODO: manejar paginación, ordenamiento, filtros complejos
        $set        = RecordSet::find($model, $query);

        $response->view($set->{'@list'} ?: "@irbis/list-recordset.html");
        $response->append('recordset', $set);
    }

    /**
     * @content JSON
     * @auth user
     * @route /recordset/(:any)/(:any)
     */
    final public function actionRecordSetCall ($request, $response) {
        $model 		= $request->path(0);
        $action 	= $request->path(1);
        $query      = $request->query('*') ?: null;
        $params     = $request->input('*') ?: [];
        $set        = (new RecordSet($model))->select($query);
        $body       = in_array($action, ['insert', 'update']) ? 
                        $set->{$action}($params) :
                        $set->{$action}(...$params);

        $response->body( $body );
    }
}