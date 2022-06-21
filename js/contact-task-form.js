CRM.$(function($) {
  $('#exporter_class').on('change', function() {
    $('#qr-code-parameters').toggle($(this).val() === 'CRM_Esr_GeneratorQR');
  });
});
