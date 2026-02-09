<?php 

// model: routes
return [
    '@list' => '@cms/list-routes.html',

    'directory' => ['n1', 'target'=>'directories', 'default'=>1],
    'name' => ['varchar', 'required'=>true, 'store'=>'$ensureRouteName'],
    'file' => ['varchar', 'required'=>true],

    '$ensureRouteName' => function ($name) {
        return "/".trim($name, '/');
    }
];