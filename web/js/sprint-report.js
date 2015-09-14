$(document).ready(
  function documentReady() {
    $('.js-project').hide();

    $('#projectSelect').on('change', function() {
      $('.js-project').hide();
      $('.js-project-' + this.value).show();
    });
  }
);