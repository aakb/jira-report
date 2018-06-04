<template>
  <table style="width:100%">
    <tr>
      <th>Name</th><th v-for="sprint in sprints">{{ sprint.name }}</th>
    </tr>
    <tr v-for="issue in issues"> {{ issue.key }}</tr>
  </table>
</template>

<script>
  import axios from "axios";
  export default {
    name: 'Sprints',
    data () {
      return {
        sprints: [],
        issues: []
      }
    },
    created() {
      var self = this;
      axios.get('/jira/board/65/sprint', {
      })
      .then(function (response) {
          self.sprints = response.data.values;
          Object.keys(response.data.values).forEach(function(key) {
            var id = response.data.values[key].id;
            axios.get('/jira/sprint/' + id + '/issue', {
            })
            .then(function (response) {
              Object.keys(response.data.issues).forEach(function(key, value) {
                self.issues = response.data.issues[key];
              });
            })
            .catch(function (error) {
              console.log(error);
            });
          });
      })
      .catch(function (error) {
          console.log(error);
      });
    }
  }
</script>
