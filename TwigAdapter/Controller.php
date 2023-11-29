<?php

namespace IrbisApps\TwigAdapter;

use Irbis\Controller as iController;
use Irbis\Server;
use Twig\Loader\FilesystemLoader;
use Twig\Environment;
use Twig\Extension\DebugExtension;

/**
 * este modulo requiere twig instalado por composer
 * composer require "twig/twig:^3.0"
 * 
 * order: debe ser el primer módulo en ser llamado
 */
class Controller extends iController {
    public $name = 'twig';
    public $loader;
    public $environment;

    public function __construct () {
        parent::__construct();

        $server = Server::getInstance();
        $loader = $this->loader = new FilesystemLoader(BASE_PATH);
        $environment = $this->environment = new Environment($loader, ['debug' => DEBUG_MODE]);
        // agregamos la extensión para debug
        $environment->addExtension(new DebugExtension());
        // establecemos la función de renderización
        $server->render = function ($view, $data) use ($environment) {
			die($environment->render($view, $data));
		};
        // por cada controlador agregado registrar en twig una ruta
        // así se puede usar rutas de vista e: @twig/views/index.html
        $server->on('addController', function ($controller, $name) use ($loader) {
            if ($controller->router) {
                if (!$name) throw new \Exception("Twig: el controlador para '{$controller->module}' requiere un nombre o alias");
                $loader->addPath($controller->directory(), $name);
            }
		});
        // cambiar las vistas de error por plantilla twig
        $server->view_404 = 'IrbisApps/TwigAdapter/views/404.html';
        $server->view_500 = 'IrbisApps/TwigAdapter/views/500.html';
    }
}