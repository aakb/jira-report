require('../css/app.scss');
var $ = require('jquery');
var axios = require('axios');

import Vue from 'vue';
import Planning from './components/Planning'
import Project from './components/Project'

/*
/**
 * Create a fresh Vue Application instance
 */

new Vue({
  el: '#app',
  components: {Planning, Project}
});
