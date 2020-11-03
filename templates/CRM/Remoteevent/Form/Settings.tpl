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

<div class="crm-block crm-form-block crm-event-manage-eventinfo-form-block">

  <div class="crm-section">

    <div class="label">{$form.remote_registration_blocking_status_list.label}&nbsp;{help id="id-blocking-status" title=$form.remote_registration_blocking_status_list.label}</div>
    <div class="content">{$form.remote_registration_blocking_status_list.html}</div>
    <div class="clear"></div>
  </div>

  <div class="crm-section">
    <div class="label">{$form.remote_registration_speaker_roles.label}&nbsp;{help id="id-speaker-roles" title=$form.remote_registration_speaker_roles.label}</div>
    <div class="content">{$form.remote_registration_speaker_roles.html}</div>
    <div class="clear"></div>
  </div>

  <div class="crm-section">
    <div class="label">{$form.remote_registration_xcm_profile.label}</div>
    <div class="content">{$form.remote_registration_xcm_profile.html}</div>
    <div class="clear"></div>
  </div>

  <!--
  <div class="crm-section">
    <div class="label">{$form.remote_registration_link.label}</div>
    <div class="content">{$form.remote_registration_link.html}</div>
    <div class="clear"></div>
  </div>
  -->

  <div class="crm-section">
    <div class="label">{$form.remote_registration_modify_link.label}</div>
    <div class="content">{$form.remote_registration_modify_link.html}</div>
    <div class="clear"></div>
  </div>

  <div class="crm-section">
    <div class="label">{$form.remote_registration_cancel_link.label}</div>
    <div class="content">{$form.remote_registration_cancel_link.html}</div>
    <div class="clear"></div>
  </div>

  <div class="crm-submit-buttons">
    {include file="CRM/common/formButtons.tpl" location="bottom"}
  </div>

</div>