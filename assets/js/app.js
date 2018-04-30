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
  var sessions = this;
  var output =[];
  console.log(sessions);
  var headers = {
    headers: {'Access-Control-Allow-Origin': '*'}
  };

  sprintData.sprints.values.forEach(function(element) {
    console.log(element);
    /*
    axios.get(element.self, headers)
      .then(function(response){
        //console.log(response.data); // ex.: { user: 'Your User'}
        //console.log(response.status); // ex.: 200
      })
      .catch(function (error) {
        if (error.response) {
          console.log(error.response.headers);
        }
        else if (error.request) {
          console.log(error.request);
        }
        else {
          console.log(error.message);
        }
        console.log(error.config);
      });
      */
  });
  return output;
}