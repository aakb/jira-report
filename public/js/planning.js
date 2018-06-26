(function () {
    'use strict';

    var app = new Vue({
        el: '#app',
        data: {
            sprints: [],
            users: {},
            projects: {},
            loading: true,
            numberLoaded: 0
        },
        created: function () {
            axios.get('/future_sprints')
                .then(function (response) {
                    this.sprints = response.data.sprints;

                    for (var i = 0; i < this.sprints.length; i++) {
                        this.getSprint(this.sprints[i].id, i);
                    }
                }.bind(this))
                .catch(function (error) {
                    console.log(error);
                });
        },
        methods: {
            getRemainingEstimatIssue: function (sprint, issue) {
                if (sprint.hasOwnProperty('issuesById') && sprint.issuesById.hasOwnProperty(issue.id)) {
                    var sprintIssue = sprint.issuesById[issue.id];

                    if (sprintIssue.done) {
                        return 'Done';
                    }

                    if (isNaN(sprintIssue.fields.timetracking.remainingEstimateSeconds)) {
                        return 'UE';
                    }

                    return sprintIssue.fields.timetracking.remainingEstimateSeconds / 3600;
                }
                else {
                    return '';
                }
            },
            getRemainingEstimatUser: function (user, sprint) {
                if (user.timeRemaining.hasOwnProperty(sprint.id)) {
                    return (user.timeRemaining[sprint.id] / 3600).toFixed(2);
                }
                else {
                    return '';
                }
            },
            getRemainingEstimat: function (project, sprint) {
                if (project.timeRemaining.hasOwnProperty(sprint.id)) {
                    return (project.timeRemaining[sprint.id] / 3600).toFixed(2);
                }
                else {
                    return '';
                }
            },
            updateGlobalTable: function (sprint) {
                for (var issue in sprint.issues) {
                    var issue = sprint.issues[issue];
                    var assigned = issue.fields.assignee;
                    var project = issue.fields.project;
                    var timeRemaining = issue.fields.timetracking.remainingEstimateSeconds;
                    var issueDone = issue.fields.hasOwnProperty('status') && issue.fields.status.name === 'Done';
                    var saveProject = null;

                    issue.done = issueDone;

                    // Projects

                    if (this.projects.hasOwnProperty(project.id)) {
                        saveProject = this.projects[project.id];
                    }
                    else {
                        saveProject = project;
                    }

                    saveProject.open = false;

                    if (!saveProject.hasOwnProperty('timeRemaining')) {
                        saveProject.timeRemaining = {};
                    }

                    if (timeRemaining && !issueDone) {
                        saveProject.timeRemaining[sprint.id] = (saveProject.timeRemaining.hasOwnProperty(sprint.id) ? saveProject.timeRemaining[sprint.id] : 0) + timeRemaining;
                    }

                    if (!saveProject.hasOwnProperty('users')) {
                        saveProject.users = {};
                    }

                    if (!assigned) {
                        saveProject.users['unassigned'] = {
                            displayName: 'Unassigned',
                            key: 'unassigned'
                        };
                        assigned = saveProject.users['unassigned'];
                    }
                    else {
                        if (!saveProject.users.hasOwnProperty(assigned.key)) {
                            saveProject.users[assigned.key] = assigned;
                        }
                    }

                    if (!saveProject.users[assigned.key].hasOwnProperty('issues')) {
                        saveProject.users[assigned.key].issues = {};
                    }

                    saveProject.users[assigned.key].issues[issue.id] = issue;

                    Vue.set(this.projects, saveProject.id, saveProject);

                    // Users

                    var saveUser = null;

                    if (this.users.hasOwnProperty(assigned.key)) {
                        saveUser = this.users[assigned.key];
                    }
                    else {
                        saveUser = assigned;
                    }

                    if (!saveUser.hasOwnProperty('projects')) {
                        saveUser.projects = {};
                    }

                    if (!saveUser.projects.hasOwnProperty(saveProject.id)) {
                        saveUser.projects[saveProject.id] = saveProject;
                    }

                    if (!saveUser.hasOwnProperty('timeRemaining')) {
                        saveUser.timeRemaining = {};
                    }

                    if (timeRemaining && !issueDone) {
                        saveUser.timeRemaining[sprint.id] = (saveUser.timeRemaining.hasOwnProperty(sprint.id) ? saveUser.timeRemaining[sprint.id] : 0) + timeRemaining;
                    }

                    Vue.set(this.users, saveUser.key, saveUser);
                }

                this.numberLoaded = this.numberLoaded + 1;

                if (this.numberLoaded == this.sprints.length) {
                    this.loading = false;
                }
            },
            getSprint: function (id, index) {
                axios.get('/issues/' + id)
                    .then(function (response) {
                        var sprint = this.sprints[index];
                        sprint.issues = response.data.issues;
                        sprint.issuesById = {};

                        for (var issue in sprint.issues) {
                            issue = sprint.issues[issue];

                            sprint.issuesById[issue.id] = issue;
                        }

                        Vue.set(this.sprints, index, sprint);

                        this.updateGlobalTable(sprint);
                    }.bind(this))
                    .catch(function (error) {
                        console.log(error);
                    });
            }
        }
    });
})();
