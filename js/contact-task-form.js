CRM.$(function($) {
  let $exporter_class = $('#exporter_class');

  $('#qr-code-parameters').toggle($exporter_class.val() === 'CRM_Esr_GeneratorQR');

  $exporter_class.on('change', function() {
    $('#qr-code-parameters').toggle($(this).val() === 'CRM_Esr_GeneratorQR');
  });
});
