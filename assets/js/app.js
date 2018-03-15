require('../css/app.scss');

var $ = require('jquery');
var axios = require('axios');

var data = $('#app').data('app');

var app = new Vue({
  el: '#app',
  data: {
    sprints: getData(data),
    header: data.sprints.values
  }
});

function getData(sprintData) {
  console.log(sprintData);
  var output =[];
  sprintData.sprints.values.forEach(function(element) {
    axios.get(element.self)
      .then(function(response){
        console.log(response.data); // ex.: { user: 'Your User'}
        console.log(response.status); // ex.: 200
      });
  });
  return output;
}