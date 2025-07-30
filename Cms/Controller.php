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


/**
 * Gestor de contenido flat - CMS / sin base de datos
 */
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
		$server = Server::getInstance();
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
		$irbis_controller = $server->getController('irbis');
		$irbis_controller->environment->addFilter(new TwigFilter('asset', [$this, 'asset']));
		$irbis_controller->environment->addFunction(new TwigFunction('readFiles', [$this, 'readFiles']));
		$irbis_controller->loader->addPath($this->filePath("/content/templates"), 'site');
	}

	public function assemble ($only_binds=false) {
		$routes = new RecordSet('routes');
		$routes->bind();

		if (!$only_binds) {
			$routes->insert([
				'name' => '/',
				'file' => 'index.html'
			]);
		}
	}

	/**
	 * twig filter
	 * Contruye una ruta para un archivo en función de su extensión, si el
	 * archivo es 'myfile.jpg' devuelve una ruta 'contet/images/myfile.jpg'
	 * 
	 * @param string $file		nombre del archivo
	 * @param string $base		directorio base para devolver
	 * @return string 			la ruta modificada
	 */
	public function asset ($file, $base='/IrbisApps/Cms') {
		$extension = explode(".", basename($file ?? ''));
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
		}, $this->filePath($glob));
	}

	/**
	 * @route /
	 * @route /site/(:all)
	 */
	public function webSite ($request) {
		$path = $request->path(0);
		$path = $path ? "/$path" : "/";
		$tmpl = null;

		$route = new RecordSet("routes");
		$route->select(['name' => $path]);
		if ($route->count()!== 1)
			throw new HttpException("Not Found", 404);
		
		$tmpl = "@site/{$route[0]->file}";
		return [$tmpl, [
			'request' => $request
		]];
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