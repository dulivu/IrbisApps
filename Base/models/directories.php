<?php

use Irbis\Server;

// model: directories

return [
    // -= properties =-

    'namespace' => ['varchar', 
        'required'=>true,
    ],
    'name' => ['varchar', 
        'required'=>true,
        'store'=>'$renameDirectory'
    ],

    '$renameDirectory' => function ($value) {
        if ($value == $this->{'-name'}) 
            return $value;

        $old = $this->{'-name'} ?: $value;
        $server = Server::getInstance();
        $controller = $server->getController($this->namespace);
        $base_dir = $controller->namespace('dir');
        $old_dir = $base_dir . DIRECTORY_SEPARATOR . $old . DIRECTORY_SEPARATOR;
        $new_dir = $base_dir . DIRECTORY_SEPARATOR . $value . DIRECTORY_SEPARATOR;

        if (is_dir($old_dir) && $old_dir != $new_dir && !is_dir($new_dir)) {
            rename($old_dir, $new_dir);
        } elseif (!is_dir($new_dir)) {
            mkdir($new_dir, 0777, true);
        }

        return $value;
    },
    
    // -= methods =-

    'getFullpath' => function ($filename = '') {
        $server = Server::getInstance();
        $controller = $server->getController($this->namespace);
        $basepath = $controller->namespace('dir');
        return $basepath . 
            DIRECTORY_SEPARATOR . $this->name . 
            DIRECTORY_SEPARATOR . $filename;
    },

    'fileList' => function () {
        $path = $this->getFullpath();

        if (!is_dir($path)) {
            throw new \Exception("El directorio '$path' no existe");
        }

        $filelist = glob($path . '*', GLOB_NOSORT|GLOB_BRACE);
        $filelist = array_map('basename', $filelist);
        return $filelist;
    },

    'fileSaveContent' => function ($filename, $filecontent) {
        $file_path = $this->getFullpath($filename);
        file_put_contents($file_path, $filecontent);
    },

    'fileUnlink' => function ($filename) {
        $file_path = $this->getFullpath($filename);
        if (is_file($file_path))
            unlink($file_path);
    }
];