<?php

namespace IrbisApps\AdapterRest;

use Irbis\Controller as iController;
use Irbis\Request;
use Irbis\Json;
use Irbis\Server;


class Controller extends iController {
	public $name 			= 'rest';
	public $has_routes      = false;
	public $installable 	= false;
	public $depends 		= [];
	public $views 			= false;

	public function init () {
		$server = Server::getInstance();
		$request = Request::getInstance();

		// Procesa solicitudes JSON y prepara las cabeceras de respuesta
		if ($request->is(JSON_REQUEST)) {
			$_POST += Json::decode($request->getRawContent('{}'));
			header("Access-Control-Allow-Origin: *");
			header("Content-Type: application/json; charset=UTF-8");

			$server->on('response', function ($request, $response) {
				$response->view = False;
				// esta sección transforma el trace del error para no generar error
				// al convertirlo en un json por recursividad
				if (
					is_array($response->data) && 
					isset($response->data['error'])
				) {
					if (isset($response->data['error']['trace'])) {
						$new_trace = [];
						foreach ($response->data['error']['trace'] as $trace) {
							$new_trace[] = ($trace['file'] ?? $trace['function'])." (".($trace['line'] ?? 0).")";
						}
						$response->data['error']['trace'] = $new_trace;
					}
				}
			});
		}
	}
}