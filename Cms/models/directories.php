<?php
use Irbis\Orm\RecordSet;
use Irbis\Orm\Record;

// model: directories

return [
    '@form' => '@cms/form-directories.html',
    '@templates' => [
        'html' => "<!DOCTYPE html>
<html>
    <head>
        <title>Mi sitio web</title>
    </head>
    
    <body>
        Coloque aqui el contenido de su página web...
    </body>
</html>",
        'js' => "console.log('Cavia CMS')",
        'css' => "body { font-family: Arial; }"
    ],

    // properties
    'summary' => ['varchar'],
    'icon' => ['varchar', 'default'=>'fa-file-o'],
    'color' => ['varchar', 'default'=>'blue'],
    'opened' => ['boolean', 'default'=>false],
    'extensions' => ['varchar'],
    'asset' => ['varchar'],

    // methods
    '@findByExtension' => function ($extension) {
        $directories = new RecordSet('directories');
        $directories->select(['extensions:like' => '%'.$extension.'%']);
        if ($directories->count() == 0)
            $directories->select(['extensions' => '*']);
        return $directories[0] ?? null;
    },

    'filePush' => function ($filename) {
        $files = $this->fileList();

        if (in_array($filename, $files)) {
            throw new \Exception("El archivo '$filename' ya existe en el directorio");
        }
        
        $extension = pathinfo($filename, PATHINFO_EXTENSION);
        $template = $this->{'@templates'}[$extension] ?? '';
        $this->fileSaveContent($filename, $template);
    },

    'filePut' => function ($filename, $filecontent) {
        $this->fileSaveContent($filename, $filecontent);
    },

    'fileRoute' => function ($filename, $fileroute) {
        $route = Record::find('routes', '/');
        if (!$route) {
            $route = Record::add('routes', [
                'directory' => $this,
                'name' => $fileroute,
                'file' => $filename
            ]);
        } else throw new \Exception("La ruta '$fileroute' ya está registrada");
    }
];