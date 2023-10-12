{*-------------------------------------------------------+
| SYSTOPIA Remote Event Extension                        |
| Copyright (C) 2020 SYSTOPIA                            |
| Author: B. Endres (endres@systopia.de)                 |
+--------------------------------------------------------+
| This program is released as free software under the    |
| Affero GPL license. You can redistribute it and/or     |
| modify it under the terms of this license which you    |
| can read by viewing the included agpl.txt or online    |
| at www.gnu.org/licenses/agpl.html. Removal of this     |
| copyright header is strictly prohibited without        |
| written permission from the original author(s).        |
+-------------------------------------------------------*}

{crmScope extensionKey='de.systopia.remoteevent'}
<div class="crm-block crm-form-block crm-event-manage-eventinfo-form-block">
  <div class="crm-submit-buttons">
    {include file="CRM/common/formButtons.tpl" location="bottom"}
  </div>

  <div class="remote-registration-switch">
    <div class="crm-section">
      <div class="label">{$form.remote_registration_enabled.label}</div>
      <div class="content">{$form.remote_registration_enabled.html}</div>
      <div class="clear"></div>
    </div>
  </div>

  <div class="remote-registration-content">
    <fieldset id="remote-settings" class="crm-collapsible">
        {capture assign=title_text}{ts}CiviRemote Event settings{/ts}{/capture}
      <legend class="collapsible-title">{$title_text}</legend>
      <div class="crm-section">
        <div class="label">{$form.remote_invitation_enabled.label}</div>
        <div class="content">{$form.remote_invitation_enabled.html}</div>
        <div class="clear"></div>
      </div>
      <div class="crm-section">
        <div class="label">{$form.remote_disable_civicrm_registration.label}</div>
        <div class="content">{$form.remote_disable_civicrm_registration.html}</div>
        <div class="clear"></div>
      </div>
      <div class="crm-section">
        <div class="label">{$form.remote_use_custom_event_location.label}&nbsp;{help id="id-use-custom-event-location" title=$form.remote_use_custom_event_location.label}</div>
        <div class="content">{$form.remote_use_custom_event_location.html}</div>
        <div class="clear"></div>
      </div>
      <div class="crm-section">
        <div class="label">{$form.remote_registration_external_identifier.label}&nbsp;{help id="id-external-identifier" title=$form.remote_registration_external_identifier.label}</div>
        <div class="content">{$form.remote_registration_external_identifier.html}</div>
        <div class="clear"></div>
      </div>
    </fieldset>

    <fieldset id="registration" class="crm-collapsible">
      <legend class="collapsible-title">{ts}Registration Restrictions{/ts}</legend>
      <div class="remote-registration-restrictions">
        <div class="crm-section">
          <div class="label">{$form.remote_registration_default_profile.label}&nbsp;<span class="crm-marker">*</span>&nbsp;{help id="id-remote-registration-default-profile" title=$form.remote_registration_default_profile.label}</div>
          <div class="content">{$form.remote_registration_default_profile.html}</div>
          <div class="clear"></div>
        </div>
        <div class="crm-section">
          <div class="label">{$form.remote_registration_profiles.label}&nbsp;<span class="crm-marker">*</span>&nbsp;{help id="id-remote-registration-profiles" title=$form.remote_registration_profiles.label}</div>
          <div class="content">{$form.remote_registration_profiles.html}</div>
          <div class="clear"></div>
        </div>
        <div class="crm-section">
          <div class="label">{$form.remote_registration_xcm_profile.label}&nbsp;<span class="crm-marker">*</span>&nbsp;{help id="id-remote-registration-xcm-profile" title=$form.remote_registration_xcm_profile.label}</div>
          <div class="content">{$form.remote_registration_xcm_profile.html}</div>
          <div class="clear"></div>
        </div>
        <div class="crm-section crm-event-manage-registration-form-block-registration_start_date">
          <div class="label">{$form.registration_start_date.label}</div>
          <div class="content">{$form.registration_start_date.html}</div>
          <div class="clear"></div>
        </div>
        <div class="crm-section crm-event-manage-registration-form-block-registration_end_date">
          <div class="label">{$form.registration_end_date.label}</div>
          <div class="content">{$form.registration_end_date.html}</div>
          <div class="clear"></div>
        </div>
        <div class="crm-section">
          <div class="label">{$form.remote_registration_suspended.label}&nbsp;{help id="id-registration-suspended" title=$form.remote_registration_suspended.label}</div>
          <div class="content">{$form.remote_registration_suspended.html}</div>
          <div class="clear"></div>
        </div>
        <div class="crm-section crm-event-manage-registration-form-block-requires_approval">
          <div class="label">{$form.requires_approval.label}&nbsp;{help id="id-requires-approval" title=$form.requires_approval.label}</div>
          <div class="content">{$form.requires_approval.html}</div>
          <div class="clear"></div>
        </div>
    </fieldset>

    <fieldset id="registration-update" class="crm-collapsible">
      <legend class="collapsible-title">{ts}Registration Updates{/ts}</legend>
      <div>
        <div class="crm-section crm-event-manage-registration-form-block-allow_selfcancelxfer">
          <div class="label">{$form.allow_selfcancelxfer.label}&nbsp;{help id="id-allow-selfcancelxfer" title=$form.allow_selfcancelxfer.label}</div>
          <div class="content">{$form.allow_selfcancelxfer.html}</div>
          <div class="clear"></div>
        </div>
        <div class="crm-section">
          <div class="label">{$form.remote_registration_default_update_profile.label}&nbsp;<span class="crm-marker">*</span>&nbsp;{help id="id-remote-registration-default-update-profile" title=$form.remote_registration_default_update_profile.label}</div>
          <div class="content">{$form.remote_registration_default_update_profile.html}</div>
          <div class="clear"></div>
        </div>
        <div class="crm-section">
          <div class="label">{$form.remote_registration_update_profiles.label}&nbsp;<span class="crm-marker">*</span>&nbsp;{help id="id-remote-registration-update-profiles" title=$form.remote_registration_update_profiles.label}</div>
          <div class="content">{$form.remote_registration_update_profiles.html}</div>
          <div class="clear"></div>
        </div>
        <div class="crm-section">
          <div class="label">{$form.remote_registration_update_xcm_profile.label}&nbsp;<span class="crm-marker">*</span>&nbsp;{help id="id-remote-update-xcm-profile" title=$form.remote_registration_update_xcm_profile.label}</div>
          <div class="content">{$form.remote_registration_update_xcm_profile.html}</div>
          <div class="clear"></div>
        </div>
        <div class="crm-section crm-event-manage-registration-form-block-registration_end_date">
          <div class="label">{$form.selfcancelxfer_time.label}&nbsp;<span class="crm-marker">*</span>&nbsp;{help id="id-allow-selfcancelxfer-time" title=$form.selfcancelxfer_time.label}</div>
          <div class="content">{$form.selfcancelxfer_time.html}</div>
          <div class="clear"></div>
        </div>
    </fieldset>

    <fieldset id="additional-participants" class="crm-collapsible">
      <legend class="collapsible-title">{ts}Additional Participants{/ts}</legend>
      <div class="crm-section crm-event-manage-registration-form-block-is_multiple_registrations">
        <div class="label">{$form.is_multiple_registrations.label}&nbsp;{help id="id-is_multiple_registrations" title=$form.is_multiple_registrations.label}</div>
        <div class="content">{$form.is_multiple_registrations.html}</div>
        <div class="clear"></div>
      </div>
      <div class="crm-section crm-event-manage-registration-form-block-is_multiple_registrations">
        <div class="label">{$form.max_additional_participants.label}&nbsp;{help id="id-max_additional_participants" title=$form.max_additional_participants.label}</div>
        <div class="content">{$form.max_additional_participants.html}</div>
        <div class="clear"></div>
      </div>
      <div class="crm-section crm-event-manage-registration-form-block-remote_registration_additional_participants_waitlist">
        <div class="label">{$form.remote_registration_additional_participants_waitlist.label}&nbsp;{help id="id-remote_registration_additional_participants_waitlist" title=$form.remote_registration_additional_participants_waitlist.label}</div>
        <div class="content">{$form.remote_registration_additional_participants_waitlist.html}</div>
        <div class="clear"></div>
      </div>
      <div class="crm-section">
        <div class="label">{$form.remote_registration_additional_participants_profile.label}&nbsp;<span class="crm-marker">*</span>&nbsp;{help id="id-remote_registration_additional_participants_profile" title=$form.remote_registration_additional_participants_profile.label}</div>
        <div class="content">{$form.remote_registration_additional_participants_profile.html}</div>
        <div class="clear"></div>
      </div>
      <div class="crm-section">
        <div class="label">{$form.remote_registration_additional_participants_xcm_profile.label}&nbsp;<span class="crm-marker">*</span>&nbsp;{help id="id-remote_registration_additional_participants_xcm_profile" title=$form.remote_registration_additional_participants_xcm_profile.label}</div>
        <div class="content">{$form.remote_registration_additional_participants_xcm_profile.html}</div>
        <div class="clear"></div>
      </div>
    </fieldset>

    <fieldset id="registration-texts" class="crm-collapsible collapsed">
        {capture assign=title_text}{ts}Public Event Text Blocks{/ts}{/capture}
      <legend class="collapsible-title">{$title_text}&nbsp;{help id="id-remote-registration-texts" title=$title_text}</legend>
      <div class="remote-registration-texts">
        <div class="crm-section crm-event-manage-registration-intro_text">
          <div class="label">{$form.remote_registration_gtac.label}&nbsp;{help id="id-remote-gtac" title=$form.remote_registration_gtac.label}</div>
          <div class="content">{$form.remote_registration_gtac.html}</div>
          <div class="clear"></div>
        </div>
        <div class="crm-section crm-event-manage-registration-intro_text">
          <div class="label">{$form.intro_text.label}&nbsp;{help id="id-intro-text" title=$form.intro_text.label}</div>
          <div class="content">{$form.intro_text.html}</div>
          <div class="clear"></div>
        </div>
        <div class="crm-section crm-event-manage-registration-footer_text">
          <div class="label">{$form.footer_text.label}&nbsp;{help id="id-footer-text" title=$form.footer_text.label}</div>
          <div class="content">{$form.footer_text.html}</div>
          <div class="clear"></div>
        </div>
        <div class="crm-section crm-event-manage-registration-confirm_title">
          <div class="label">{$form.confirm_title.label}&nbsp;{help id="id-confirm-title" title=$form.confirm_title.label}</div>
          <div class="content">{$form.confirm_title.html}</div>
          <div class="clear"></div>
        </div>
        <div class="crm-section crm-event-manage-registration-confirm_text">
          <div class="label">{$form.confirm_text.label}&nbsp;{help id="id-confirm-text" title=$form.confirm_text.label}</div>
          <div class="content">{$form.confirm_text.html}</div>
          <div class="clear"></div>
        </div>
        <div class="crm-section crm-event-manage-registration-confirm_footer_text">
          <div class="label">{$form.confirm_footer_text.label}&nbsp;{help id="id-confirm-footer" title=$form.confirm_footer_text.label}</div>
          <div class="content">{$form.confirm_footer_text.html}</div>
          <div class="clear"></div>
        </div>
        <div class="crm-section crm-event-manage-registration-thankyou_title">
          <div class="label">{$form.thankyou_title.label}&nbsp;{help id="id-thankyou-title" title=$form.thankyou_title.label}</div>
          <div class="content">{$form.thankyou_title.html}</div>
          <div class="clear"></div>
        </div>
        <div class="crm-section crm-event-manage-registration-thankyou_text">
          <div class="label">{$form.thankyou_text.label}&nbsp;{help id="id-thankyou-text" title=$form.thankyou_text.label}</div>
          <div class="content">{$form.thankyou_text.html}</div>
          <div class="clear"></div>
        </div>
        <div class="crm-section crm-event-manage-registration-thankyou_footer_text">
          <div class="label">{$form.thankyou_footer_text.label}&nbsp;{help id="id-thankyou-footer" title=$form.thankyou_footer_text.label}</div>
          <div class="content">{$form.thankyou_footer_text.html}</div>
          <div class="clear"></div>
        </div>
      </div>
    </fieldset>
  </div>
  <div class="crm-submit-buttons">
    {include file="CRM/common/formButtons.tpl" location="bottom"}
  </div>
</div>
{/crmScope}
