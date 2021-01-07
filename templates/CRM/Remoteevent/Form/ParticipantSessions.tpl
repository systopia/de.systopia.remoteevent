{*-------------------------------------------------------+
| SYSTOPIA Remote Event Extension                        |
| Copyright (C) 2021 SYSTOPIA                            |
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
  <div class="remote-session remote-session-main-container">

  {foreach from=$session_fields item=session_field}
    <div class="crm-section">
      <div class="label">{$form.$session_field.label}</div>
      <div class="content">{$form.$session_field.html}</div>
      <div class="clear"></div>
    </div>
  {/foreach}

    <div class="crm-section">
      <div class="label">{$form.bypass_restriction.label}&nbsp;{help id="id-bypass" title=$form.bypass_restriction.label}</div>
      <div class="content">{$form.bypass_restriction.html}</div>
      <div class="clear"></div>
    </div>

  </div>

  <div class="crm-submit-buttons">
    {include file="CRM/common/formButtons.tpl" location="bottom"}
  </div>
{/crmScope}