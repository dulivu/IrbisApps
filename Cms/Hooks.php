<?php
namespace IrbisApps\Cms;

use Irbis\Interfaces\ComponentInterface;
use Irbis\Interfaces\HooksInterface;
use Irbis\Controller;
use Irbis\Server;
use Irbis\Orm\RecordSet;
use Irbis\Orm\Record;
use Irbis\Traits\Component;


class Hooks implements ComponentInterface, HooksInterface {
    use Component;

    public function install () {
        RecordSet::reset('directories');
        Server::getInstance()->setState('server.backoffice', '/cms');

        $namespace = $this->controller->namespace();
        $directories = RecordSet::bind('directories')->select();

        if (!$directories->count()) {
            $directories->insert([
                'summary' => 'Páginas',
                'namespace' => $namespace,
                'name' => 'content/sites',
                'extensions' => 'html,htm,xhtml',
                'asset' => 'html',
                'icon' => 'fa-file-code-o',
                'opened' => true
            ], [
                'summary' => 'Estilos',
                'namespace' => $namespace,
                'name' => 'content/styles',
                'extensions' => 'css',
                'asset' => 'css',
                'icon' => 'fa-file-text-o',
                'color' => 'green'
            ], [
                'summary' => 'Scripts',
                'namespace' => $namespace,
                'name' => 'content/scripts',
                'extensions' => 'js',
                'asset' => 'js',
                'icon' => 'fa-file-archive-o',
                'color' => 'orange'
            ], [
                'summary' => 'Imágenes',
                'namespace' => $namespace,
                'name' => 'content/images',
                'extensions' => 'png,jpg,jpeg,gif,svg,webp',
                'asset' => 'img',
                'icon' => 'fa-file-image-o',
                'color' => 'purple'
            ], [
                'summary' => 'Fuentes',
                'namespace' => $namespace,
                'name' => 'content/fonts',
                'extensions' => 'woff,woff2,ttf,otf,eot',
                'asset' => 'font',
                'icon' => 'fa-font',
                'color' => 'black'
            ], [
                'summary' => 'Otros',
                'namespace' => $namespace,
                'name' => 'content/others',
                'extensions' => '*',
                'asset' => 'other',
                'color' => 'gray',
            ]);

            $html = $directories[0];
            $html->filePush('index.html');
        }

        $routes = RecordSet::bind('routes');
        $routes->select('/');
        
        if (!$routes->count()) {
            $routes->insert([
                'directory' => $directories[0],
                'name' => '/',
                'file' => 'index.html'
            ]);
        }
    }

    public function uninstall () {
        // acciones de desinstalación
    }
}