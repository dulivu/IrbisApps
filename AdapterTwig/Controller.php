<?php

namespace IrbisApps\AdapterTwig;

use Irbis\Controller as iController;
use Irbis\Server;
use Twig\Loader\FilesystemLoader;
use Twig\Environment;
use Twig\Extension\DebugExtension;

/**
 * este modulo requiere twig instalado por composer
 * composer require "twig/twig:^3.0"
 */
class Controller extends iController {
    public $name 			= 'twig';
	public $has_routes      = false;
	public $installable 	= false;
	public $depends 		= [];
    public $views 			= false;

    public $loader;
    public $environment;

    public function __construct () {
        parent::__construct();

        $server = Server::getInstance();
        # NOTE: se puede usar esta condición para evitar que se
        # vuelva a ejecutar un bloque de código al crear el objeto
        if (!$server->getController('IrbisApps/AdapterTwig')) {
            $loader = $this->loader = new FilesystemLoader(BASE_PATH);
            $environment = $this->environment = new Environment($loader, ['debug' => DEBUG_MODE]);
            $environment->addGlobal('DEBUG_MODE', DEBUG_MODE ? 1 : 0);
            // agregamos la extensión para debug
            $environment->addExtension(new DebugExtension());
            // establecemos la función de renderización
            $server->render = function ($view, $data) use ($environment) {
                die($environment->render($view, $data));
            };
            // por cada controlador agregado registrar en twig una ruta
            // así se puede usar rutas de vista e: @twig/index.html
            $server->on('addController', function ($controller, $name) use ($loader) {
                if (!$name) throw new \Exception("Twig: el controlador '{$controller->key()}' requiere un nombre alias");
                if ($controller->views) $loader->addPath($controller->file("/{$controller->views}"), $name);
            });
            // cambiar las vistas de error por plantilla twig
            $server->view_404 = 'IrbisApps/AdapterTwig/views/404.html';
            $server->view_500 = 'IrbisApps/AdapterTwig/views/500.html';
        }
    }
}