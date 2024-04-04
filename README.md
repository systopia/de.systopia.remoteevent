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
to build your system based on Drupal 10 you will most likely want to have a look
and/or use the **CiviRemote Drupal module** which includes a lot of pre-built
features (https://github.com/systopia/civiremote).

Note that this extension may be used alongside regular CiviCRM event
registrations - you can choose whether you would like to use the remote features
or not for each event.

### Why this Extension?

In many cases you may not want or be able to use CiviCRM's built in forms e.g.:

* For security reasons your CiviCRM runs within a VPN
* The builtin forms and processing logic do not provide enough options to be
  customized to your needs
* You may already have an external system for your constituents (such as a
  member area on your website or a collaboration platform) which cannot easily
  be connected to CiviCRM

This extension is licensed under 
[AGPL-3.0](https://www.gnu.org/licenses/agpl-3.0).

## Features

* Connect another (remote) system to CiviCRM that can handle highly customizable
  event listings and registration features
* Pre-defined registration profiles including a "one click registration" for
  authenticated users (additional profiles can be added fairly easy)
* Use different registration profiles within the same event
* Allow participants to modify and/or cancel their own registrations
* Alternative approach for defining an event's location
* Event registration/update profiles be configured using a UI with the 
[Remote Event Form Editor](https://github.com/systopia/remoteeventformeditor) 
extension
* Have Sessions/Workshops within your event
* Registering additional participants
* Localizing event registration profiles with a locale given in the get_form 
request (e.g. the current language of the frontend)

TODO: Complete Feature List

## Requirements

* PHP v7.0+
* CiviCRM 5.61
* Dependency on CiviCRM Extension 
**[Remote Tools](https://github.com/systopia/de.systopia.remotetools)**
* Dependency on CiviCRM Extension 
**[Extended Contact Matcher](https://github.com/systopia/de.systopia.xcm)**
* A system that will serve as your public system such as an external website

## extensions that integrate with the Remote Event Extensions
* [Remote Event Form Editor](https://github.com/systopia/remoteeventformeditor)  
A drag&drop form editor for event-registration
* [Event Invitations](https://github.com/systopia/de.systopia.eventinvitation)
* [Custom Event Communication](https://github.com/systopia/de.systopia.eventmessages)
* [Event-Checkin](https://github.com/systopia/de.systopia.eventcheckin)

## Documentation
- EN: https://docs.civicrm.org/remoteevent/en/latest (automatic publishing)

## We need your support
This CiviCRM extension is provided as Free and Open Source Software, and we are 
happy if you find it useful. However, we have put a lot of work into it (and 
continue to do so), much of it unpaid for. So if you benefit from our software,
please consider making a financial contribution, so we can continue to maintain 
and develop it further.

If you are willing to support us in developing this CiviCRM extension, please 
email to [info\@systopia.de](mailto:info@systopia.de?subject=supportSYSTOPIA) 
to get an invoice or agree a different payment method.
Thank you!