<?php
namespace IrbisApps\Cms;

use Irbis\Orm\RecordSet;
use Irbis\Orm\Record;
use Irbis\Exceptions\HttpException;
use Irbis\Controller as iController;
use Irbis\Server;
use Irbis\Request;


class Controller extends iController {
    # alias para plantillas	= site
    public static $name         = 'cms';
    public static $routable     = true;
    public static $depends      = ['IrbisApps/Base'];

    public static $label        = 'Cms';
    public static $version      = '1.0';
    public static $description  = 'Gestor de contenido para sitios web';
    public static $author       = 'jorge.quico@cavia.io';

    /**
     * @verb GET
     * @route /
     * @route ?/(:all)
     */
    final public function webSite ($request) {
        # TODO: revisar porque se ejecuta varias veces
        $path = "/" . ($request->path(0) ?: '');
        $route = Record::find('routes', $path);

        if (!$route) {
            throw new HttpException(404, 'La página solicitada no existe');
        }

        return "@site/{$route->file}";
    }

    /**
     * @auth user
     * @verb GET
     * @route /cms
     */
    final public function webCms ($request) {
        return ['@cms/cms.html', [
            'routes' => RecordSet::find('routes'),
            'directories' => RecordSet::find('directories')
        ]];
    }

    /**
     * @auth user
     * @verb POST
     * @route /cms/upload
     */
    final public function fileUpload ($request) {
        $request->manageUploadedFiles('files', function ($file) {
            $directories = new RecordSet('directories');
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);

            $directory = $directories->findByExtension($extension);
            $filepath = $directory->getFullpath($file['name']);

            if (!move_uploaded_file($file['tmp_name'], $filepath)) {
                throw new \Exception('Hubo un error al subir un archivo');
            }
        });
    }
}