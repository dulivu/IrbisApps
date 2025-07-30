<?php

return [
    '__label' => 'Categorias de producto',

    'name' => ['varchar', 'required'=>True],
    'parent' => ['n1', 'target'=>'product_categories'],
    'products' => ['1n', 'target'=>'products(category)'],
];