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
  <div class="crm-section">
    <div class="label">{$form.unregister.label}&nbsp;{help id="id-mode" title=$form.unregister.label}</div>
    <div class="content">{$form.unregister.html}</div>
    <div class="clear"></div>
  </div>

  <div class="crm-section">
    <div class="label">{$form.session_ids.label}</div>
    <div class="content">{$form.session_ids.html}</div>
    <div class="clear"></div>
  </div>

  <div class="crm-section">
    <div class="label">{$form.bypass_restriction.label}&nbsp;{help id="id-bypass" title=$form.bypass_restriction.label}</div>
    <div class="content">{$form.bypass_restriction.html}</div>
    <div class="clear"></div>
  </div>

  <br>
  <div class="crm-submit-buttons">
      {include file="CRM/common/formButtons.tpl" location="bottom"}
  </div>
{/crmScope}
