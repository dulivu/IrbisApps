<?php

namespace IrbisApps\TwigAdapter;

use Irbis\Controller as iController;
use Irbis\Server;
use Twig\Loader\FilesystemLoader;
use Twig\Environment;
use Twig\Extension\DebugExtension;

/**
 * this module require twig, install by composer
 * 
 * composer require "twig/twig:^3.0"
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

        $environment->addExtension(new DebugExtension());

        $server->render = function ($view, $data) use ($environment) {
			die($environment->render($view, $data));
		};

        $server->on('addController', function ($controller) use ($loader) {
			$vf = $controller->directory(DIRECTORY_SEPARATOR.($controller->views ?? 'views'));
			if (isset($controller->namespace) && file_exists($vf))
				$loader->addPath($vf, $controller->namespace);
		});

        $server->view_404 = 'Apps/TwigAdapter/views/404.html';
        $server->view_500 = 'Apps/TwigAdapter/views/500.html';
    }
}