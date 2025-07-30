<?php

use Irbis\Server;

return [
    '@list' => '@irbis/irbis.html',

    'file' => ['varchar', 'required'=>true],
    'name' => ['varchar'],
    'package' => ['varchar'],
    'namespace' => ['varchar'],
    'description' => ['varchar'],
    'version' => ['varchar'],
    'author' => ['varchar'],
    'assembled' => ['boolean', 'default'=>false],
    'active' => ['boolean', 'default'=>false],

    'transmuteData' => function () {
        # a partir del nombre del archivo empieza
        # a generar todos los datos de la aplicación
        $search = ['/Controller', '/', '.php'];
        $replace = ['', '\\', ''];
        $name = str_replace($search, $replace, $this->file);
        $name = explode('\\', $name);

        $search = ['/', '.php'];
        $replace = ['\\', ''];
        $namespace = "\\".str_replace($search, $replace, $this->file);

        $this->name = $name[1] ?? "Desconocido";
        $this->package = $name[0] ?? "Desconocido";
        $this->namespace = $namespace;
        $this->description = $namespace::$description ?? "";
        $this->version = $namespace::$version ?? "1.0";
        $this->author = $namespace::$author ?? "Desconocido";
    },

    'activeToogle' => function () {
        if ($this->active) {
            if ($this->name == 'Base' && $this->package == 'IrbisApps')
                throw new \Exception("No se puede desinstalar la aplicación Base");
            $this->active = false;
        } else {
            if (!$this->assembled) {
                $server = Server::getInstance();
                $namespace = $this->namespace;
                $ctrl = new $namespace;
                $server->addController($ctrl);
                $ctrl->assemble();
                $this->assembled = true;
            }
            $this->active = true;
        }
    },

    'actionUpdate' => function () {
        $namespace = $this->namespace;
        $ctrl = new $namespace;
        $ctrl->assemble(true);
    },

    'actionDelete' => function () {
        $this->delete();
    }
];