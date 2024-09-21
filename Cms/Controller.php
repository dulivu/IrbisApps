<?php
namespace IrbisApps\Cms;

use Irbis\Controller as iController;
use Irbis\Server;
use Irbis\Request;
use Twig\TwigFilter;
use Twig\TwigFunction;

CONST BASE_HTML_TEMPLATE = 
"<!DOCTYPE html>
<html>
	<head>
		<title>Mi sitio web</title>
	</head>

	<body>
		Coloque aqui el contenido de su página web...
	</body>
</html>";
CONST BASE_SCRIPT_TEMPLATE = "console.log('Cavia CMS')";
CONST BASE_CSS_TEMPLATE = "body { font-family: Arial; }";


/**
 * Gestor de contenido flat - CMS / sin base de datos
 */
class Controller extends iController {
	public $name 			= 'cms'; # tambien usa el espacio site
	public $has_routes 		= true;
	public $installable 	= false;
	public $depends 		= [
        'IrbisApps/AdapterTwig', 
        'IrbisApps/AdapterRest',
		'IrbisApps/Tools',
        'IrbisApps/Auth'
    ];

	public $templates = [
		"html" => BASE_HTML_TEMPLATE,
		"js" => BASE_SCRIPT_TEMPLATE,
		"css" => BASE_CSS_TEMPLATE
	];

	public function init () {
		$server = Server::getInstance();
		$request = Request::getInstance();

		$server->getController('auth')->auth_path = $request->base.'/cms';
		$server->getController('twig')->environment->addFilter(new TwigFilter('asset', [$this, 'asset']));
		$server->getController('twig')->environment->addFunction(new TwigFunction('readFiles', [$this, 'readFiles']));
		$server->getController('twig')->loader->addPath($this->file("/content/templates"), 'site');
	}

	/**
	 * twig filter
	 * Contruye una ruta en función de la ruta base agregandole un
	 * directorio determinado por la extensión del archivo, si el
	 * archivo es 'myfile.jpg' devuelve una ruta 'base/images/myfile.jpg'
	 * 
	 * @param string $file		nombre del archivo
	 * @param string $base		directorio base para devolver
	 * @return string 			la ruta modificada
	 */
	public function asset ($file, $base='/IrbisApps/Cms') {
		$extension = explode(".", basename($file));
		$extension = end($extension);

		switch ($extension) {
			case 'png':
			case 'jpg':
			case 'jpeg':
			case 'gif':
			case 'svg': $file   = $base.'/content/images/'.$file; break;
			case 'eot':
			case 'ttf':
			case 'woff':
			case 'woff2':
			case 'otf': $file	= $base.'/content/fonts/'.$file; break;
			case 'html': $file  = $base.'/content/templates/'.$file; break;
			case 'css': $file   = $base.'/content/styles/'.$file; break;
			case 'js': $file    = $base.'/content/scripts/'.$file; break;
			default: $file      = $base.'/content/others/'.$file; break;
		}
        
		return $file;
	}

	/**
     * twig function
	 * Devuelve una lista de archivos que se encuentren dentro
	 * de un directorio dentro del directorio del controlador
	 * 
	 * @param string $glob		comodin con el directorio a buscar
	 * @return array[string]	arreglo con nombres de archivos encontrados
	 */
	public function readFiles (string $glob) {
		if (str_contains($glob, '*.img'))
        	$glob = '/content/images/'.str_replace('*.img', '*.{jpg,jpeg,png,gif,svg}', $glob);
		elseif (str_contains($glob, '*.font'))
			$glob = '/content/fonts/'.str_replace('*.font', '*.{eot,ttf,woff,woff2,otf}', $glob);
		else
			$glob = $this->asset($glob, '');
        
		return array_map(function ($i) {
			return basename($i);
		}, $this->file($glob));
	}

	/**
	 * @route /
	 */
	public function webIndex ($request) {
		$template = $this->routeToTemplate('/');
		return [$template, [
			'request' => $request
		]];
	}

	/**
	 * @route /site/(:all)
	 */
	public function webSite ($request) {
		$route = $request->path(0);
		$template = $this->routeToTemplate("/$route");
		return [$template, [
			'request' => $request
		]];
	}

	private function routeToTemplate ($route) {
		$routes = $this->state('routes');
		$routes_keys = array_keys($routes);
		$routes_values = array_values($routes);

		if (in_array("$route", $routes_values)) {
			$key = array_search("$route", $routes_values);
			return "@cms/../content/templates/{$routes_keys[$key]}.html";
		} else {
			header("HTTP/1.0 404 Not Found");
			$v404 = $this->file('/content/templates/not_found.html');
			return file_exists($v404) ? 
				'@cms/../content/templates/not_found.html' :
				$this->server->view_404;
		}
	}

	/**
	 * @route /preview/(:any)
	 * entregar directamente el nombre de la plantilla
	 * ej: /preview/index.html
	 */
	public function webPreview ($request) {
		$this->controller('auth')->session();
		$fileName = $request->path(0);
		$filePath = $this->file("/content/templates/{$fileName}");
		if (file_exists($filePath)) {
			return "@cms/../content/templates/{$fileName}";
		} else {
			header("HTTP/1.0 404 Not Found");
			$v404 = $this->file('/content/templates/not_found.html');
			return file_exists($v404) ? 
				'@cms/../content/templates/not_found.html' :
				$this->server->view_404;
		}
	}

	/**
	 * @route /cms
	 */
	public function manageCms ($request) {
		$user = $this->controller('auth')->session();

		# gestionar la ruta del archivo
		if ($request->query('routefor')) {
			$fileName = basename($request->query('routefor'), ".html");
			if ($request->is(GET_REQUEST)) {
				$fileRoute = $this->state("routes.$fileName");
				return $fileRoute ?: '';
			}
			
			if ($request->is(POST_REQUEST)) {
				$fileRoute = $request->input('fileRoute');
				if (!$fileRoute) $fileRoute = REMOVE_STATE;
				$this->state("routes.$fileName", $fileRoute);
				return;
			}
		}
        
        return ['@cms/index.html', [
			'page_title' => 'Irbis CMS',
			'page_applications' => [
				$this->controller('IrbisApps/Cms'),
			],
			'user' => explode('.', $user)[1]
        ]];
	}

	/**
	 * @route /cms/file
	 */
	public function manageFile ($request) {
		$this->controller('auth')->session();
		$file_base = $this->file();
		$file_name = $request->query('name');
		$file_path = $this->asset($file_name, $file_base);

		if ($request->is(GET_REQUEST)) {
			// si el archivo no existe se crea
			if (!file_exists($file_path)) {
				$extension = file_extension($file_path);
				file_put_contents($file_path, $this->templates[$extension] ?? "");
			}
			header('Content-Type: text/plain');
			return file_get_contents($file_path);
		}

		if ($request->is(DELETE_REQUEST)) {
			$fileName = basename($file_path, ".html");
			unlink($file_path);
			$this->state("routes.$fileName", REMOVE_STATE);
		}

		if ($request->is(PUT_REQUEST)) {
			$file_content = $request->getRawContent();
			file_put_contents($file_path, $file_content);
		}

		if ($request->is(FILE_REQUEST)) {
			// gestiona subida de multiples archivos
			$request->forEachUpload('files', function ($file) use ($file_base) {
				$file_path = $this->asset($file['name'], $file_base);
				if (!move_uploaded_file($file['tmp_name'], $file_path)) {
					throw new \Exception('Hubo un error al subir un archivo');
				}
			}, true);
		}
	}
}