<?php

return [
    'name' => ['varchar', 'required' => true, 'label' => 'Nombre'],
    'stages' => ['1n', 'target' => 'project_stage(project)', 'label' => 'Etapas'],
];