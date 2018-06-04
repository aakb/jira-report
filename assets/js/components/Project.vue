<template>
  <div>
    <h2 v-if="project">{{ project.name }}</h2>
    <select v-model="versionSelected">
      <option v-for="version in versions.values" :value="version.id">
        {{ version.name }}
      </option>
    </select>
    <div v-for="issue in tasks.issues">
      <h3><a href="">{{ issue.key }}</a></h3>
      <div v-if="issue.fields.epic"><h5>{{ issue.fields.epic.name}}</h5></div>
      <div>
        <span v-if="issue.fields.aggregatetimeoriginalestimate">Original: ({{ issue.fields.aggregatetimeoriginalestimate }}) </span>
        <span v-if="issue.fields.aggregatetimespent">Spent: ({{ issue.fields.aggregatetimespent }}) </span>
      </div>
      <div v-if="issue.fields.status">Status: {{ issue.fields.status.name }} </div>
      <br>
      <br>
    </div>
  </div>
</template>

<script>
  import axios from "axios";
  export default {
    name: 'Project',
    data () {
      return {
        project: {},
        versions: {},
        tasks: {},
        versionSelected: {}
      }
    },
    created() {
      var pathArray = window.location.pathname.split( '/' );
      var self = this;

      axios.get('/jira/board/' + pathArray[2], {
      })
      .then(function (response) {
        console.log(response.data);
        self.project = response.data;
      })
      .catch(function (error) {
        console.log(error);
      });

      axios.get('/jira/board/' + pathArray[2] + '/version', {
      })
      .then(function (response) {
        console.log(response.data);
        self.versions = response.data;
      })
      .catch(function (error) {
        console.log(error);
      });
    },
    watch:{
      'versionSelected' : function (val) {
        var issues = this;
        var pathArray = window.location.pathname.split( '/' );
        axios.get('/jira/board/' + pathArray[2] + '/issue?jql=fixVersion=' + val, {
        })
        .then(function (response) {
          console.log(response.data);
          issues.tasks = response.data;
        })
        .catch(function (error) {
          console.log(error);
        });
      }
    }
  }
</script>
