export class ProjectManager extends IrbisElement {
    container = null;
    model = null;

    constructor (element) {
        super(element);
        this.container = element.querySelector('.project-container');
        this.model = new RecordSet('project');
        this.model.select().then((records) => {
            let select = element.querySelector('.project-selector');
            records.forEach(async (record) => {
                select.append(Element.create('option', {
                    attributes: { value: record.id },
                    textContent: record.name
                }));
            });
        }).then(() => {
            this.changeProjectAction();
        });
    }

    /*** HELPERS ***/

    createProjectElement (project) {
        let projectContent = Element.create('ul');
        let projectElement = Element.create('li', {
            classList: 'project',
            attributes: { 'project-id': project.id },
            children: [Element.create('strong', { textContent: project.name }), projectContent]
        });
        return [projectElement, projectContent];
    }

    createStageElement (project, stage) {
        let stageContent = Element.create('ul');
        let stageElement = Element.create('li', {
            classList: 'stage',
            attributes: {
                'project-id': project.id,
                'stage-id': stage.id
            },
            children: [Element.create('details', {
                attributes: { open: stage.is_open },
                children: [Element.create('summary', { textContent: stage.name }), stageContent]
            })],
            events: {
                drop: async (ev) => {
                    console.log(ev);
                    let data = JSON.parse(ev.dataTransfer.getData('text/plain'));
                    let taskSet = new RecordSet('project_task');
                    await taskSet.update({ stage: stage.id }, [data.taskId]);
                    this.changeProjectAction();
                },

                dragover: (ev) => {
                    ev.preventDefault();
                    ev.dataTransfer.dropEffect = "move";
                }
            }
        });
        return [stageElement, stageContent];
    }

    getSelectedProjectId () {
        return parseInt(this.element.querySelector('.project-selector').value);
    }

    /*** ACTIONS ***/

    newProjectAction (ev) {
        
    }

    async changeProjectAction (ev) {
        let project = this.model.byId(this.getSelectedProjectId());

        let [projectElement, projectContent] = this.createProjectElement(project);

        let stages = new RecordSet('project_stage', ['is_open']);
        await stages.select({ project: project.id });
        // crear los items o tareas
        stages.forEach(async (stage) => {
            let [stageElement, stageContent] = this.createStageElement(project, stage);
            projectContent.append(stageElement);

            let tasks = new RecordSet('project_task');
            await tasks.select({ stage: stage.id });
            tasks.forEach((task) => {
                stageContent.append(Element.create('li', { 
                    classList: 'task',
                    attributes: { 
                        'project-id': project.id,
                        'stage-id': stage.id,
                        'task-id': task.id ,
                        'draggable': 'true'
                    },
                    textContent: task.name,
                    events: {
                        click: this.selectTaskAction.bind(this),
                        dragstart: (ev) => {
                            ev.dataTransfer.setData('text/plain', JSON.stringify({
                                taskId: ev.target.getAttribute('task-id'),
                                stageId: ev.target.getAttribute('stage-id'),
                                projectId: ev.target.getAttribute('project-id')
                            }));
                            ev.dataTransfer.dropEffect = 'move';
                        }
                    }
                }));
            });
        });

        this.container.replaceChildren(projectElement);
    }

    selectTaskAction (ev) {
        let selects = this.element.querySelectorAll('.task.selected');
        selects.forEach((select) => select.classList.remove('selected'));
        ev.target.classList.toggle('selected');
        this.element.querySelector('.edit-task').disabled = false;
    }

    async editTaskAction (ev) {
        let task = this.element.querySelector('.task.selected');
        if (task) {
            task = task.getAttribute('task-id');
            let set = new RecordSet('project_task');
            await set.select({ id: task });
            task = set[0];
            
            let win = document.getElementById('edit-task-window');
            console.log(task);
            win.querySelector('.title-bar-text').textContent = task.name;
            location.href = '#edit-task-window';
        }
    }

    newTaskAction (ev) {
        let projectID = this.getSelectedProjectId();
        let taskName = prompt('Ingrese nombre de la tarea');
        if (taskName) {
            let firstStage = this.element.querySelector('li.stage');
            let taskSet = new RecordSet('project_task');
            taskSet.insert({
                name: taskName, 
                stage: firstStage.getAttribute('stage-id') 
            }).then((task) => {
                this.changeProjectAction();
            });
        }
    }

    /*** INTERNAL ***/

    
}