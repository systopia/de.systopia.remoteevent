{*-------------------------------------------------------+
| SYSTOPIA Remote Tools                                  |
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
+--------------------------------------------------------+
*}{crmScope extensionKey='de.systopia.remoteevent'}{capture assign=time_format}{ts}%H:%M{/ts}{/capture}{foreach from=$sessions item=session}
{capture assign=start_time}{$session.start_date|date_format:$time_format}{/capture}{capture assign=end_time}{$session.end_date|date_format:$time_format}{/capture}* {ts 1=$start_time 2=$end_time}[%1h - %2h]{/ts} {$session.title} ({$session.category}){if $session.presenter_txt}

   {$session.presenter_txt}{/if}{if $session.location_txt}

   {$session.location_txt}{/if}{/foreach}{/crmScope}