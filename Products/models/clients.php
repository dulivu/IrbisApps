<?php

return [
    'name' => ['varchar', 'required'=>true, 'label'=>'Nombre'],
    'products' => ['nm', 'target' => 'products(clients)']
];