<?php

namespace IrbisApps\Project;

use Irbis\Controller as iController;
use Irbis\RecordSet;
use Irbis\Request;
use Irbis\DataBase;


class Controller extends iController {
    public static $name = 'project';
    public static $use_routes = true;
    public static $depends = ['IrbisApps/Base'];

    public function init () {
        $this
            ->controller('irbis')
            ->assemble($this, function () {
                $project = new RecordSet('project');
                $project_stage = new RecordSet('project_stage');
                $project_task = new RecordSet('project_task');

                $project->bind();
                $project_stage->bind();
                $project_task->bind();

                $project->insert([
                    'name' => 'Mi proyecto',
                    'stages' => [
                        ['name' => 'Planeamiento', 'tasks' => [
                            ['name' => 'Tarea en planeación']
                        ]],
                        ['name' => 'Progeso', 'tasks' => [
                            ['name' => 'Tarea en progreso']
                        ]],
                        ['name' => 'Finalizado']
                    ]
                ]);
            });
    }

    /**
     * @route /project
     */
    public function project ($request) {
        return '@project/index.html';
    }
}