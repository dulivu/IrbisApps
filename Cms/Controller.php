<?php
namespace IrbisApps\Cms;

use Irbis\Controller as iController;
use Irbis\RecordSet;
use Irbis\Exceptions\HttpException;
use Irbis\Server;
use Irbis\Request;
use Twig\TwigFilter;
use Twig\TwigFunction;

CONST HTML_TEMPLATE = 
"<!DOCTYPE html>
<html>
	<head>
		<title>Mi sitio web</title>
	</head>

	<body>
		Coloque aqui el contenido de su página web...
	</body>
</html>";
CONST SCRIPT_TEMPLATE = "console.log('Cavia CMS')";
CONST CSS_TEMPLATE = "body { font-family: Arial; }";


class Controller extends iController {
	# alias para plantillas	= site
	public static $name			= 'cms';
	public static $use_routes	= true;
	public static $depends		= ['IrbisApps/Base'];
	public static $label 		= 'Cms';
	public static $version 		= '1.0';
	public static $description 	= 'Gestor de contenido para sitios web';
	public static $author 		= 'jorge.quico@cavia.io';

	public $templates = [
		"html" => HTML_TEMPLATE,
		"js" => SCRIPT_TEMPLATE,
		"css" => CSS_TEMPLATE
	];

	public function init () {
		$request = Request::getInstance();

		# crear el directorio de contenido de no existir
		if (!is_dir($this->filePath('content'))) {
			mkdir($this->filePath('content'), 0777, true);
			mkdir($this->filePath('content/images'), 0777, true);
			mkdir($this->filePath('content/fonts'), 0777, true);
			mkdir($this->filePath('content/styles'), 0777, true);
			mkdir($this->filePath('content/scripts'), 0777, true);
			mkdir($this->filePath('content/others'), 0777, true);
			mkdir($this->filePath('content/templates'), 0777, true);
		}

		# registrar funciones y alias en twig
		$irbis = $this->controller('irbis');
		$irbis->environment->addFilter(new TwigFilter('asset', [$this, 'asset']));
		$irbis->environment->addFunction(new TwigFunction('readFiles', [$this, 'readFiles']));
		$irbis->loader->addPath($this->filePath("/content/templates"), 'site');
	}

	public function asset ($file, $base='/IrbisApps/Cms') {
		# obtiene la extensión del archivo
		$extension = explode(".", basename($file));
		$extension = end($extension);
		# construye la ruta según la extensión
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
		# devuelve la ruta completa del archivo
		return $file;
	}

	public function readFiles (string $glob) {
		# el glob siempre tiene que ser un comodín, *.img, *.html, *.other
		if (!str_contains($glob, '*'))
			throw new \Exception("El glob debe contener un comodín, ej: *.img");
		# modifica el glob para el contenido del directorio
		if (str_contains($glob, '*.img'))
        	$glob = '/content/images/'.str_replace('*.img', '*.{jpg,jpeg,png,gif,svg}', $glob);
		elseif (str_contains($glob, '*.font'))
			$glob = '/content/fonts/'.str_replace('*.font', '*.{eot,ttf,woff,woff2,otf}', $glob);
		else
			$glob = $this->asset($glob, '');
		# devuelve los archivos de un tipo de contenido
		return array_map(function ($i) {
			return basename($i);
		}, $this->filePath($glob));
	}

	public function assemble ($only_binds=false) {
		$routes = new RecordSet('routes');
		$routes->bind();

		if (!$only_binds) {
			$routes->insert(['name' => '/', 'file' => 'index.html']);
		}
	}

	/**
	 * @route /
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
		$routes = $this->state('routes') ?: [];
		$routes_keys = array_keys($routes);
		$routes_values = array_values($routes);

		if (in_array("$route", $routes_values)) {
			$key = array_search("$route", $routes_values);
			return "@site/{$routes_keys[$key]}.html";
		} else {
			header("HTTP/1.0 404 Not Found");
			$v404 = $this->file('/content/templates/not_found.html');
			return file_exists($v404) ? 
				'@site/not_found.html' :
				$this->server->view_404;
		}
	}

	/**
	 * @route /cms
	 */
	public function manageCms ($request) {
		$user = $this->controller('irbis')->session();

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
        
        return ['@cms/cms.html', [
			'page_title' => 'Irbis CMS',
        ]];
	}

	/**
	 * @route /cms/file
	 */
	public function manageFile ($request) {
		$this->controller('irbis')->session();
		$file_base = $this->filePath();
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