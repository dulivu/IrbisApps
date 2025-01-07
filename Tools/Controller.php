<?php
namespace IrbisApps\Tools;

use Irbis\Controller as iController;

/**
 * Herramientos de frontend para el desarrollo de aplicaciones
 * - Implementa una plantilla base para las vistas de aplicación
 * - Implementa utilidades javascript para el cliente
 * - Implementa la clase IrbisElement en javascript para gestionar componentes
 * - Los componentes se gestionan por medio del directorio 'components' en cada aplicación
 */
class Controller extends iController {
	public $name 			= 'tools';
	public $has_routes 		= false;
	public $installable 	= false;
	public $depends 		= ['IrbisApps/AdapterTwig'];

	public function init () {
		# agrega la ruta de los componentes que tenga cada controlador registrado
		# en el servidor en una variable 'applications_with_components' para que la vista 'layout'
		# pueda utilizar y cargar los componentes automáticamente
		$server = $this->server;
		$this->server->on('response', function ($response) use ($server) {
			if ($response->view) {
				if (is_null($response->data))
					$response->data = ['applications_with_components' => []];
				if (is_array($response->data) and !isset($response->data['applications_with_components'])) {
					$response->data['applications_with_components'] = [];
					$server->forEachController(function ($controller) use ($response) {
						if (is_dir($controller->file('/components'))) {
							$response->data['applications_with_components'][] = $controller;
						}
					});
				}
			}
		});
	}
}