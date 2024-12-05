## Configuration
After installing the extension, make sure your **Remote Tool settings** are 
configured according to your needs (refer to the documentation provided in 
[de.systopia.remotetools](https://github.com/systopia/de.systopia.remotetools)). 
Then proceed with the general configuration:

* Navigate to >>Administer >>CiviEvent >>Remote Events - General Configuration 
(/civicrm/admin/remoteevent/settings).
* Define which participant status will block re-registration for your events 
(e.g., rejected) and specify which roles you want to assign to speakers.
* Suppress workshop/session data if you do not use this feature.
* Select the activity type for changes in participation data.
* Configure the default matcher profile you would like to use (XCM-Extension). 
If using registration updates, ensure the XCM profile has the *Match contacts by
contact ID* option activated to prevent duplicate contacts when updating event 
registrations.
* Enter the URLs to be used for registrations, modifications, and cancellations,
depending on the external system you use. If you use the CiviRemote Drupal 
module, you may use:
  - `https://yourdomain.org/civiremote/event/update/{token}`
  - `https://yourdomain.org/civiremote/event/cancel/{token}`

After completing the general configuration, visit the new "Remote Online 
Registration" tab within CiviCRM's event configuration UI. This tab provides 
several options and settings under the "Online-Registration (CiviRemote)" and 
"Workshops" sections. Notably, you can decide whether to use the remote event 
features, disable native CiviCRM online registration, use alternative event 
locations, or use external identification for your event.

You will find two segments for registration profiles: one for initial 
registration and another for editing registrations. The labels and help pop-ups 
should provide sufficient information to understand your options.

The "Registration Restrictions" section allows you to configure when 
registration is available, whether a registration requires manual review, and if
participants can cancel or modify their own registrations.

The "Additional Participants" feature allows you to add a group of people with a
single registration. This results in a form where the participant can add up to 
9 additional participants using increase/decrease buttons. For these additional 
participants, a separate form can be selected to collect only names and email 
addresses, while the main participant provides more information. You can also 
select a specific XCM configuration for contact matching. In the participation 
overview, you can see who was added along with the main participant.

The "Public Event Text Blocks" can be used by your external system to display 
information at appropriate places during the registration process. How and when 
this information is presented to your constituents depends on the design of your
external system.

CiviCRM's other basic event settings still apply and may be used in your 
external system, including title, descriptions, start and end dates, 
maximum number of participants, waitlist settings, etc.

### Alternative Event Location Settings
If the default event location feature is insufficient, you can choose to use the
alternative event location feature. If you do, CiviCRM's regular event location 
tab will be hidden, and a custom tab will appear. In this custom tab, you can 
choose a contact with the subtype "event location" (the subtype is created when 
installing the extension). You can also provide specific information for the 
event location, such as room number or travel instructions.

The API will then make the chosen event location's basic data available, 
including its name, address, geo data, and additional information for the event.
This information can be displayed and used by the external system.