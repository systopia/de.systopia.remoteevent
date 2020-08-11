<div class="remote-registration-switch">
  <div class="crm-section">
    <div class="label">{$form.remote_registration_enabled.label}</div>
    <div class="content">{$form.remote_registration_enabled.html}</div>
    <div class="clear"></div>
  </div>
</div>

<div class="remote-registration-content">
  <div class="crm-section">
    <div class="label">{$form.remote_registration_default_profile.label}</div>
    <div class="content">{$form.remote_registration_default_profile.html}</div>
    <div class="clear"></div>
  </div>

  <div class="crm-section">
    <div class="label">{$form.remote_registration_profiles.label}</div>
    <div class="content">{$form.remote_registration_profiles.html}</div>
    <div class="clear"></div>
  </div>

  <div class="crm-section">
    <div class="label">{$form.remote_invitation_enabled.label}</div>
    <div class="content">{$form.remote_invitation_enabled.html}</div>
    <div class="clear"></div>
  </div>
</div>

<div class="crm-submit-buttons">
{include file="CRM/common/formButtons.tpl" location="bottom"}
</div>

{literal}
<script>
cj(document).ready(function() {

  /**
   * Show or hide the content based on the enabled flag
   */
  function show_hide_content() {
    if (cj("input[name=remote_registration_enabled]").prop('checked')) {
      cj("div.remote-registration-content").show(100);
    } else {
      cj("div.remote-registration-content").hide(100);
    }
  }

  // add to change event and trigger once
  cj("input[name=remote_registration_enabled]").change(show_hide_content);
  show_hide_content();
});
</script>
{/literal}