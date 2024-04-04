## Configuration

After installing the extension, make sure your **Remote Tool settings** are
according to your needs (refer to documentation provided in
de.systopia.remotetools). Then head on to the general configuration:

* Navigate to >>Administer >>CiviEvent >>Remote Events - General Configuration
  (/civicrm/admin/remoteevent/settings).
* Define which participant statuses will block a re-registration to your
  events (e.g. rejected) and which roles you would like to assign to speakers.
* Suppress workshop/session data if you do not use this feature
* Select activity-type for changes in participation data
* Configure which default matcher profile you would like to use (XCM-Extension).
  If using registration updates, make sure the XCM profile has the *Match
  contacts by contact ID* activated, as otherwise duplicate contacts might be
  created when updating event registrations.
* Enter the urls to be used for registrations, modifications of registrations
  and cancellations which will depend on the external system you use. In case
  you use the CiviRemote Drupal module you may
  use https://yourdomain.org/civiremote/event/update/{token},
  https://yourdomain.org/civiremote/event/cancel/{token}

After general configuration visit the new tab "Remote Online Registration"
within CiviCRM's event configuration UI. It will provide you with several
options and settings, under the tabs "Online-Registration (CiviRemote)" and 
"Workshops". Most notably if you would like to use the remote event features at 
all, to disable native CiviCRM online registration, use alternative 
event-locations or external identification to your event.

Beneath you find two segments for registration profiles. One for the initial 
registration and the other for editing registrations. We think that the labels 
and help-pop-ups are telling you enough to understand your options. At least we 
hope so :)

The "Registration Restrictions" allow you configurations regarding when
registration is available, if a registration requires manual review and if
participants can cancel or modify their own registrations.

The section "Additional Participants" is a new feature, that allows you to add a
 group of people by just one single registration. Resulting in a form, where the
participant is able to multiply the desired registration-information until a 
maximum of up to 9 further participants using increase/decrease buttons.

The "Public Event Text Blocks" may be used by your external system to display
the information at the appropriate places during the registration process. How
and when this information is presented to your constituents will depend on the
design of your external system.

CiviCRM's other basic event settings still apply and may be used in your
external system, e.g. it's title, descriptions, start and end dates, max. number
of participants, waitlist settings etc...

### Alternative Event Location Settings

As the default event location feature is somewhat limited you can choose to
use a feature for alternative event locations. If you do so CiviCRM's regular
event location tab will be hidden and a custom tab will appear in which you can
choose a contact that has the subtype "event location" (the subtype is created
when installing the extension). Also, you can provide information on the event
location that applies for that particular event (such as the room number or
travel instructions).

The API will then make the chosen event locations basic data available,
including its name, address, geo data and the additional information for this
event. This information can be displayed and used in other ways by the external
system.