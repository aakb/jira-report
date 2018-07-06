(function () {
    'use strict';

    var app = new Vue({
        el: '#app',
        data: {
            loadedProject: null,
            loadedVersion: null,
            projects: null,
            selectedProject: null,
            selectedVersion: null,
            issues: null,
            version: null,
            epic: null,
            loadingIssues: false
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
            selectProject: function () {
                console.log(this.selectedProject);
                axios.get('/api/project/' + this.selectedProject.id)
                    .then(function (response) {
                        this.loadedProject = response.data.project;
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
