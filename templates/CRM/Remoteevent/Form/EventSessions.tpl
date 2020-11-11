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


{foreach from=$sessions key=day item=day_sessions}
<h1>Day {$day}</h1>
  {foreach from=$day_sessions key=slot item=slot_sessions}
  <h2>{$slots.$slot}</h2>
    {if $slot_sessions}
      {foreach from=$slot_sessions item=session}
        {$session.title}
      {/foreach}
    {else}
      {ts}No Sessions{/ts}
    {/if}
  {/foreach}
{/foreach}

