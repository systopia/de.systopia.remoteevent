(function ($) {
  $(document).ready(function () {

    /**
     * Show or hide the content based on the enabled flag
     */
    function show_hide_content() {
      $('.remote-registration-content').toggle($('input[name=remote_registration_enabled]').prop('checked'), 100);
    }

    // Add to change event and trigger once.
    $('input[name=remote_registration_enabled]').change(show_hide_content);
    show_hide_content();

    $('[name=is_multiple_registrations]').change(function () {
      $('[name=remote_registration_additional_participants_profile], [name=max_additional_participants], [name=remote_registration_additional_participants_xcm_profile], [name=remote_registration_additional_participants_waitlist]').closest('.crm-section')
        .toggle($(this).prop('checked'));
    });
    $('[name=is_multiple_registrations]').change();
  });
})(CRM.$ || cj);
