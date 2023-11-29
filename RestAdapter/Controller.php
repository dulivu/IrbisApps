<?php

namespace IrbisApps\RestAdapter;

use Irbis\Controller as iController;
use Irbis\Request;
use Irbis\Json;
use Irbis\Server;


class Controller extends iController {
	public $name = 'rest_adapter';
	public $router = True;

	public function init () {
		$server = Server::getInstance();
		$request = Request::getInstance();

		// Procesa solicitudes JSON y prepara las cabeceras de respuesta
		if ($request->hasContent('application/json')) {
			$_POST += Json::decode(file_get_contents("php://input") ?: '{}', true);
			header("Access-Control-Allow-Origin: *");
			header("Content-Type: application/json; charset=UTF-8");

			$server->on('response', function ($request, $response) {
				$response->view = False;
				// esta sección transforma el trace del error para no generar error
				// al convertirlo en un json por recursividad
				if (
					is_array($response->data) && 
					isset($response->data['status']) && 
					$response->data['status'] == 'error'
				) {
					if (isset($response->data['error']['trace'])) {
						$new_trace = [];
						foreach ($response->data['error']['trace'] as $trace) {
							$new_trace[] = $trace['file']." (".$trace['line'].")";
						}
						$response->data['error']['trace'] = $new_trace;
					}
				}
			});
		}
	}

	/**
	 * @route /rest_test
	 */
	public function rest_test ($request, $response) {
		return [
			'test_string' => 'one',
			'test_num' => 5,
			'test_bool' => true,
			'test_list' => [1,'one',true]
		];
	}
}