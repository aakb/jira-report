(function () {
    'use strict';

    var app = new Vue({
        el: '#app',
        data: {
            loadedProject: null,
            loadedVersion: null,
            epics: null,
            projects: null,
            selectedProject: null,
            selectedVersion: null,
            selectedEpic: null,
            selectedUser: null,
            issues: null,
            version: null,
            epic: null,
            loadingIssues: false,
            issue: {
                title: '',
                estimate: null
            },
            users: null
        },
        computed: {
            sortedIssues: function () {
                return this.issues.sort(function (a,b) {
                    return (a.fields.summary.toLocaleLowerCase() > b.fields.summary.toLocaleLowerCase()) ? 1 : -1;
                });
            }
        },
        created: function () {
            axios.get('/api/project')
                .then(function (response) {
                    this.projects = response.data.projects;
                }.bind(this))
                .catch(function (error) {
                    console.log(error);
                });
        },
        methods: {
            createIssues: function () {
                axios.post('/api/issue', {
                    project: this.selectedProject,
                    version: this.selectedVersion,
                    epic: this.selectedEpic,
                    issue: this.issue
                });
            },
            selectProject: function () {
                axios.get('/api/project/' + this.selectedProject.id)
                    .then(function (response) {
                        this.loadedProject = response.data.project;
                    }.bind(this))
                    .catch(function (error) {
                        console.log(error);
                    });

                axios.get('/api/project/' + this.selectedProject.id + '/epic')
                    .then(function (response) {
                        this.epics = response.data.epics;
                    }.bind(this))
                    .catch(function (error) {
                        console.log(error);
                    });

                axios.get('/api/project/' + this.selectedProject.key + '/user')
                    .then(function (response) {
                        this.users = response.data.users;
                    }.bind(this))
                    .catch(function (error) {
                        console.log(error);
                    });
            },
            selectVersion: function () {
                this.loadingIssues = true;

                axios.get('/api/issues/' + this.selectedProject.id + '/' + this.selectedVersion.id)
                    .then(function (response) {
                        this.issues = response.data.issues;
                        console.log(this.issues);
                    }.bind(this))
                    .catch(function (error) {
                        console.log(error);
                    })
                    .then(function () {
                        app.loadingIssues = false;
                    });
            }
        }
    });
})();
