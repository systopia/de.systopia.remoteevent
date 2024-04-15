# SYSTOPIA's Remote Event Extension

## Scope

This extension provides a lot of additional features to CiviCRM's events. In
particular, you will be able to configure events in CiviCRM, display/use that
information in other remote systems and submit registrations for those events
back to CiviCRM.

The general idea is that your organization's staff can do all relevant event
configurations in CiviCRM. CiviCRM will then make this information available via
its REST API. The extension's API also has built in logic and actions to receive
and update registrations.

Any external system can interact with the API and display event calendars,
detailed event information, registration forms etc. (this could be a remote
system or the CMS your CiviCRM runs on) and also submit information entered by
your constituents back to CiviCRM.

All of this requires that you have or set up an external system to act as a
frontend for your event listings, registration forms etc. In case you would like
to build your system based on Drupal 8 you will most likely want to have a look
and/or use the CiviRemote Drupal module which includes a lot of pre-built
features (https://github.com/systopia/civiremote).

Note that this extension may be used alongside regular CiviCRM event
registrations - you can choose whether you would like to use the remote features
or not for each event.

### Why this Extension?

In many cases you may not want or be able to use CiviCRM's built in forms e.g.:

* For security reasons your CiviCRM runs within a VPN
* The built in forms and processing logic do not provide enough options to be
  customized to your needs
* You may already have an external system for your constituents (such as a
  member area on your website or a collaboration platform) which cannot easily
  be connected to CiviCRM

This extension is licensed
under [AGPL-3.0](https://www.gnu.org/licenses/agpl-3.0).

## Features

* Connect another (remote) system to CiviCRM that can handle highly customizable
  event listings and registration features
* Pre-defined registration profiles including a "one click registration" for
  authenticated users (additional profiles can be added fairly easy)
* Use different registration profiles withn the same event
* Allow participants to modify and/or cancel their own registrations
* Alternative approach for defining an event's location

TODO: Complete Feature List

## Requirements

* PHP v7.0+
* CiviCRM 5.61
* Dependency on CiviCRM Extension "Remote Tools" (de.systopia.remotetools)
* Dependency on CiviCRM Extension "Extended Contact Matcher" (de.systopia.xcm)
* A system that will serve as your public system such as an external website

TODO: List extensions that integrate with Remote Event Extensions.

For a comprehensive write-up in German on this, see [https://community.software-fuer-engagierte.de/t/fancy-events-mit-civicrm/250](https://community.software-fuer-engagierte.de/t/fancy-events-mit-civicrm/250).

## Configuration

After installing the extension, make sure your Remote Tool settings are
according to your needs (refer to docmunentation provided in
de.systopia.remotetools). Then head on to the general configuration:

* Navigate to >>Administer >>Remote Events - General Configuration.
* Define which participant statuses will block a re-registration to your
  events (e.g. rejected) and which roles you would like to assign to speakers.
* Configure which default matcher profile you would like to use (XCM-Extension).
  If using registration updates, make sure the XCM profile has the *Match
  contacts by contact ID* activated, as otherwise duplicate contacts might be
  created when updating event registrations.
* Enter the urls to be used for registrations, modifications of registrations
  and cancellations which will depend on the external system you use. In case
  you use the CiviRemote Drupal module you may
  use https://yourdomain.org/d8civiremote/,
  https://yourdomain.org/d8civiremote/modify/,
  https://yourdomain.org/d8civiremote/cancel/

After general configuration visit the new tab "Remote Online Registration"
within CiviCRM's event configuration UI. It will provide you with several
options and settings, most noteably if you would like to use the remote event
features, if you would like to disable native CiviCRM online registration and
which registration profiles to use.

The "Registration Restrictions" allow you configurations regarding when
registration is available, if a registration requires manual review and if
participants can cancel or modify their own registrations.

The "Public Event Text Blocks" may be used by your external system to display
the information at the appropriate places during the registration process. How
and when this information is presented to your constituents will depend on the
design of your external system.

CiviCRM's other basic event settings still apply and may be used in your
external system, e.g. it's title, descriptions, start and end dates, max. number
of participants, waitlist settings etc..

### Alternative Event Location Settings

As the the default event location feature is somewhat limited you can choose to
use a feature for alternative event locations. If you do so CiviCRM's regular
event location tab will be hidden and a custom tab will appear in which you can
choose a contact that has the subtype "event location" (the subtype is created
when installing the extension). Also, you can provide information on the event
location that applies for that particular event (such as the room number or
travel instructions).

The API will then make the choosen event locations basic data availabe,
including it's name, address, geodata and the additional information for this
event. This information can be displayed and used in other ways by the external
system.

## Remote Event API

The extension exposes a new API to remote event listings or registration
systems:

1. ``RemoteEvent.get`` allows querying for remotely available events in the same
   way ``Event.get`` would
1. ``RemoteEvent.create`` allows creating remotely available events in the same
   way ``Event.create`` would
1. ``RemoteParticipant.get_form`` returns a default specification fo the
   registration form required for signing up/cancel/update
1. ``RemoteParticipant.create`` sign somebody up for an event by providing the
   necessary form data (as requested by ``RemoteParticipant``)
1. ``RemoteRegistration.validate`` ask the system for validating the given
   signup data before the actual registration
1. ``RemoteRegistration.cancel`` cancel an existing registration, identified by
   remote_contact_id or token
1. ``RemoteRegistration.update`` update an existing registration, identified by
   remote_contact_id or token

Please note that all these API actions come with an independent set of
permissions.

## Extensibility and Interoperability

This extension uses a series of Symfony events to extend and customise the
behaviour of these calls:

1. ``civi.remoteevent.get.params`` modify / restrict query parameters to event
   information
1. ``civi.remoteevent.get.result`` modify / extend event information
1. ``civi.remoteevent.spawn.params`` modify / extend parameters for creating or
   updating a remote event
1. ``civi.remoteevent.registration.validate`` modify / extend signup data
1. ``civi.remoteevent.registration.submit``modify / extend signup process
1. ``civi.remoteevent.registration.cancel``modify / extend cancellation process
1. ``civi.remoteevent.registration.getform``modify / extend registration form
1. ``civi.remoteevent.cancellation.getform``modify / extend cancellation form
1. ``civi.remoteevent.registration_update.getform``modify / extend update form
1. ``civi.remoteevent.label`` modify or override certain labels (e.g. workshop
   groups)

## Documentation
- EN: https://docs.civicrm.org/remoteevent/en/latest (automatic publishing)

## We need your support
This CiviCRM extension is provided as Free and Open Source Software,
and we are happy if you find it useful. However, we have put a lot of work into it
(and continue to do so), much of it unpaid for. So if you benefit from our software,
please consider making a financial contribution so we can continue to maintain and develop it further.

If you are willing to support us in developing this CiviCRM extension,
please send an email to info@systopia.de to get an invoice or agree a different payment method.
Thank you!
