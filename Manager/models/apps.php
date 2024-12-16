<?php

use Irbis\Server;

return [
    'name' => ['varchar', 'required' => true],
    'description' => ['varchar'],
    'namespace' => ['varchar'],
    'installed' => ['boolean', 'default' => false],

    // se requiere un método que entregue las vistas necesarias
    '@views' => function () {
        return [
            'list' => '@irbis/views/apps_list.html',
            'list_actions' => '@irbis/views/apps_list_actions.html',
        ];
    },

    'make_install' => function () {
        $server = Server::getInstance();
        # TODO: se quito este metodo del servidor
        $server->addApplication($this->key());

        $controller = $server->getController($this->key());
        $controller->install();
        
        $this->installed = true;
    },

    'make_uninstall' => function () {
        $this->installed = false;
    },
];