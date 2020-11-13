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

<div class="crm-section">
  <div class="label">{$form.title.label}</div>
  <div class="content">{$form.title.html}</div>
  <div class="clear"></div>
</div>

<div class="crm-section">
  <div class="label">{$form.is_active.label}</div>
  <div class="content">{$form.is_active.html}</div>
  <div class="clear"></div>
</div>

<div class="crm-section">
  <div class="label">{$form.start_date.label}</div>
  <div class="content">{$form.start_date.html}</div>
  <div class="clear"></div>
</div>

<div class="crm-section">
  <div class="label">{$form.end_date.label}</div>
  <div class="content">{$form.end_date.html}</div>
  <div class="clear"></div>
</div>

<div class="crm-section">
  <div class="label">{$form.slot_id.label}</div>
  <div class="content">{$form.slot_id.html}</div>
  <div class="clear"></div>
</div>

<div class="crm-section">
  <div class="label">{$form.category_id.label}</div>
  <div class="content">{$form.category_id.html}</div>
  <div class="clear"></div>
</div>

<div class="crm-section">
  <div class="label">{$form.type_id.label}</div>
  <div class="content">{$form.type_id.html}</div>
  <div class="clear"></div>
</div>

<div class="crm-section">
  <div class="label">{$form.description.label}</div>
  <div class="content">{$form.description.html}</div>
  <div class="clear"></div>
</div>

<div class="crm-section">
  <div class="label">{$form.max_participants.label}</div>
  <div class="content">{$form.max_participants.html}</div>
  <div class="clear"></div>
</div>

<div class="crm-section">
  <div class="label">{$form.location.label}</div>
  <div class="content">{$form.location.html}</div>
  <div class="clear"></div>
</div>

<div class="crm-section">
  <div class="label">{$form.presenter_id.label}</div>
  <div class="content">{$form.presenter_id.html}</div>
  <div class="clear"></div>
</div>

<div class="crm-section">
  <div class="label">{$form.presenter_title.label}</div>
  <div class="content">{$form.presenter_title.html}</div>
  <div class="clear"></div>
</div>

{* FOOTER *}
<div class="crm-submit-buttons">
{include file="CRM/common/formButtons.tpl" location="bottom"}
</div>
