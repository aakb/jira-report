(function () {
    'use strict';

    var app = new Vue({
        el: '#app',
        data: {
            hideUsers: {},
            toggle: {},
            sprints: [],
            users: {},
            projects: {},
            numberLoaded: 0,
            projectFilter: '',
            userFilter: ''
        },
        computed: {
            sortedUsers: function () {
                if (this.users === {}) {
                    return [];
                }

                var arr = Object.keys(this.users).map(function (i) {
                    return this.users[i];
                }.bind(this));

                arr = arr.filter(function (item) {
                    return item.displayName.toLowerCase().indexOf(this.userFilter) !== -1;
                }.bind(this));

                arr = arr.sort(function (a,b) {
                    if (a.key === 'unassigned') {
                        return 1;
                    }
                    return (a.displayName.toLocaleLowerCase() > b.displayName.toLocaleLowerCase()) ? 1 : -1;
                });

                return arr;
            },
            sortedProjects: function () {
                if (this.projects === {}) {
                    return [];
                }

                var arr = Object.keys(this.projects).map(function (i) {
                    return this.projects[i];
                }.bind(this));

                arr = arr.filter(function (item) {
                    return item.name.toLowerCase().indexOf(this.projectFilter) !== -1;
                }.bind(this));

                arr = arr.sort(function (a,b) {
                    return (a.name.toLocaleLowerCase() > b.name.toLocaleLowerCase()) ? 1 : -1;
                });

                return arr;
            }
        },
        created: function () {
            var hideUsers = localStorage.getItem('hideUsers');

            if (hideUsers !== null) {
                this.hideUsers = JSON.parse(hideUsers);
            }

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
            toggleUser: function (key) {
                var newValue = !this.hideUsers[key];
                Vue.set(this.hideUsers, key, newValue);
                localStorage.setItem('hideUsers', JSON.stringify(this.hideUsers));
            },
            getToggle: function (key) {
                var toggled = this.toggle.hasOwnProperty(key) && this.toggle[key];

                if (toggled) {
                    return '<i class="fas fa-angle-up"></i>';
                }
                else {
                    return '<i class="fas fa-angle-down"></i>';
                }
            },
            keyToggled: function (key) {
                return this.toggle.hasOwnProperty(key) && this.toggle[key];
            },
            toggleKey: function (key) {
                Vue.set(this.toggle, key, !this.toggle[key]);
            },
            getSprintRemainingTotal: function (sprint) {
                var total = 0;

                for (var i in sprint.issues) {
                    var issue = sprint.issues[i];

                    if (issue.timeRemaining &&
                        issue.fields.assignee &&
                        (!this.hideUsers.hasOwnProperty(issue.fields.assignee.key) || this.hideUsers[issue.fields.assignee.key] === false)) {
                        total += issue.timeRemaining;
                    }
                }

                if (total === 0) {
                    return '';
                }

                return total / 3600;
            },
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
            getRemainingEstimatUserProjectSprint: function (user, project, sprint) {
                var sum = 0;

                if (user.projects.hasOwnProperty(project.id)) {
                    var issues = user.projects[project.id].issues;
                    for (var issue in issues) {
                        issue = user.projects[project.id].issues[issue];

                        if (issue.sprintId === sprint.id &&
                            (issue.fields.hasOwnProperty('assignee') &&
                             issue.fields.assignee &&
                             user.key ===
                                issue.fields.assignee.key) &&
                            !issue.done &&
                            issue.hasOwnProperty('timeRemaining') &&
                            issue.timeRemaining > 0) {
                            sum += issue.timeRemaining;
                        }
                    }
                }

                if (sum === 0) {
                    return '';
                }

                return (sum / 3600).toFixed(2);
            },
            getRemainingEstimatUser: function (user, sprint) {
                if (user.hasOwnProperty('timeRemaining') && user.timeRemaining.hasOwnProperty(sprint.id)) {
                    return (user.timeRemaining[sprint.id] / 3600).toFixed(2);
                }
                else {
                    return '';
                }
            },
            getRemainingEstimat: function (project, sprint) {
                if (project.hasOwnProperty('timeRemaining') && project.timeRemaining.hasOwnProperty(sprint.id)) {
                    return (project.timeRemaining[sprint.id] / 3600).toFixed(2);
                }
                else {
                    return '';
                }
            },
            updateGlobalTable: function (sprint) {
                for (var issue in sprint.issues) {
                    issue = sprint.issues[issue];

                    var assigned = issue.fields.assignee;
                    var project = issue.fields.project;
                    var timeRemaining = issue.fields.timetracking.remainingEstimateSeconds;
                    var issueDone = issue.fields.hasOwnProperty('status') && issue.fields.status.name === 'Done';
                    var saveProject = null;

                    issue.done = issueDone;
                    issue.sprintId = sprint.id;
                    issue.timeRemaining = timeRemaining;

                    // Projects

                    if (this.projects.hasOwnProperty(project.id)) {
                        saveProject = this.projects[project.id];
                    }
                    else {
                        saveProject = project;
                    }

                    if (!saveProject.hasOwnProperty('issues')) {
                        saveProject.issues = [];
                    }

                    saveProject.issues.push(issue);

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
