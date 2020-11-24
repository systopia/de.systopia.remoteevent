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

  <div id="help">
    {ts}CiviCRM contacts of the type event location may be selected here. Your remote system may display their information (Display Name, Address, Geodata) as well as the Additional Information for this Event during registration process. The information may also be used in confirmation mails. How it isused may differ on your implementation.{/ts}
  </div>

  <div class="crm-section">
    <div class="label">{$form.event_alternativelocation_contact_id.label}</div>
    <div class="content">{$form.event_alternativelocation_contact_id.html}</div>
    <div class="clear"></div>
  </div>

  <div class="crm-section">
    <div class="label">{$form.event_alternativelocation_remark.label}&nbsp;{help id="id-altlocation-remark" title=$form.event_alternativelocation_remark.label}</div>
    <div class="content">{$form.event_alternativelocation_remark.html}</div>
    <div class="clear"></div>
  </div>

  <div class="crm-submit-buttons">
    {include file="CRM/common/formButtons.tpl" location="bottom"}
  </div>

</div>
{/crmScope}