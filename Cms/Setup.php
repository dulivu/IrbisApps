<?php
namespace IrbisApps\Cms;

use Irbis\Controller;
use Irbis\Orm\Record;
use Irbis\Orm\RecordSet;
use Irbis\Interfaces\ComponentInterface;
use Irbis\Interfaces\SetupInterface;
use Irbis\Traits\Component;
use Twig\TwigFilter;
use Twig\TwigFunction;

class Setup implements ComponentInterface, SetupInterface {
    use Component;

    public function setup () {
        $this->setupTwig();
    }

    private function setupTwig () {
        $cms = $this->controller->application('IrbisApps/Cms');
        $html = Record::find('directories', ['asset'=>'html']);
        if (!$html) {
            throw new \Exception("El directorio de 'html' no está definido");
        }
        $path = $cms->namespace('dir').$html->name;

        $base_setup = $this->controller->application('IrbisApps/Base')->component('Setup');
        $base_setup->environment->addFilter(new TwigFilter('asset', [$this, 'asset']));
        $base_setup->loader->addPath($path, 'site');
    }

    public function asset ($file) {
        $directories = new RecordSet('directories');
        $extension = pathinfo($file, PATHINFO_EXTENSION);

        $directory = $directories->findByExtension($extension);
        return "/IrbisApps/Cms/{$directory->name}/{$file}";
    }
}