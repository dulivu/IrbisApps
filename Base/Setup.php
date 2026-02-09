<?php
namespace IrbisApps\Base;

use Irbis\Server;
use Irbis\Request;
use Irbis\Action;
use Irbis\Orm\RecordSet;
use Irbis\Orm\Record;
use Irbis\Exceptions\HttpException;
use Irbis\Tools\Json;
use Twig\Loader\FilesystemLoader;
use Twig\Environment;
use Twig\Extension\DebugExtension;
use Irbis\Controller;
use Irbis\Interfaces\ComponentInterface;
use Irbis\Interfaces\SetupInterface;
use Irbis\Traits\Component;


class Setup implements ComponentInterface, SetupInterface {
    use Component;

    public ?FilesystemLoader $loader;
    public ?Environment $environment;
    public ?Record $user= null;

    public function setup () {
        $server = Server::getInstance();
        $request = Request::getInstance();

        $this->setupTwig($server);
        $this->setupJsonResponse($server, $request);
        $this->setupDecorators($this->controller, $request);
        $this->setupHtmlContentData($server, $request);
    }

    private function setupTwig ($server) {
        $loader = $this->loader = new FilesystemLoader(BASE_PATH);
        $environment = $this->environment = new Environment($loader, ['debug' => DEBUG_MODE]);

        $environment->addGlobal('DEBUG_MODE', DEBUG_MODE ? 1 : 0);
        $environment->addExtension(new DebugExtension());

        // establecemos la función de renderización
        $server->setup('renderEnvironment', function ($view, $data) use ($environment) {
            die($environment->render($view, $data));
        });

        // por cada controlador agregado registrar en twig una ruta
        // así se puede usar rutas de vista e: @twig/views/index.html
        $server->walkControllers(function ($controller, $name) use ($server, $loader) {
            $view_path = $controller->namespace('dir').'views';
            $loader->addPath($view_path, $controller::$name);
        });

        // cambiar las vistas de error por plantilla twig
        $server->setup('errorView', 404, '@irbis/error-404.html');
        $server->setup('errorView', 500, '@irbis/error-500.html');
    }

    private function setupJsonResponse ($server, $request) {
        if ($request->isJson()) {
            // Procesa solicitudes JSON y prepara las cabeceras de respuesta
            // de existir una vista la quita, se debe forzar el envio de data json
            // si hay un error, acorta el objeto json ya que suele ser muy largo
            $input = Json::decode($request->rawContent() ?: '{}');
            $_POST = array_merge($_POST, $input);

            $server->on('response', function ($response) {
                $response->header("Access-Control-Allow-Origin: *");
                $response->header("Content-Type: application/json; charset=UTF-8");
                $response->view(null);
            });
        }
    }

    private function setupDecorators ($controller, $request) {
        $request->session = $controller->component('Session');

        Action::setValidator('auth', function ($mode) use ($request) {
            if ($mode == 'user' && !$request->user) {
                if ($request->isJson()) throw new HttpException(401, "Unauthorized");
                redirect('/login?redirect='.base64_encode($request.''));
            }
        });

        Action::setValidator('content', function ($type) use ($request) {
            $is_json = $request->isJson();
            if ($type == 'JSON' && !$is_json) {
                throw new HttpException(406, "Content type must be application/json");
            }
            if ($type == 'HTML' && $is_json) {
                throw new HttpException(406, "Content type must be text/html");
            }
        });
    }

    private function setupHtmlContentData ($server, $request) {
        $server->on('response', function ($response) use ($server, $request) {
            if ($response->hasView()) {
                $apps = [];
                $server->walkControllers(function ($controller) use (&$apps) {
                    $apps[] = [
                        'name' => $controller::$name,
                        'namespace' => $controller->namespace()
                    ];
                });
                $response->append('apps', $apps);
                $response->append('user', $request->user);
            }
        });
    }
}