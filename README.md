# SYSTOPIA's Remote Event Extension
## Scope

This extension adds numerous features to CiviCRM's events. Specifically, it 
allows you to:
+ Configure events in CiviCRM
+ Display and use that information in other remote systems
+ Submit registrations for those events back to CiviCRM

The main idea is that your organization's staff can handle all relevant event 
configurations in CiviCRM. CiviCRM then makes this information available via its
REST API. The extension's API also includes logic and actions to receive and 
update registrations.

Any external system can interact with the API to display event calendars, 
detailed event information, registration forms, etc. This can be a remote system
or the CMS your CiviCRM runs on. It can also submit information entered by your 
constituents back to CiviCRM.

To use these features, you must set up an external system to act as a frontend 
for your event listings, registration forms, etc. If you plan to build your 
system on Drupal 10, consider using the 
[CiviRemote Drupal module](https://github.com/systopia/civiremote) which 
includes many pre-built features.

Note that this extension can be used alongside regular CiviCRM event 
registrations. You can choose for each event whether to use the remote features 
or not.

## Why this Extension?
There are several scenarios where you might not want or be able to use CiviCRM's
built-in forms, such as:
+ Your CiviCRM runs within a VPN for security reasons
+ The built-in forms and processing logic do not offer enough customization 
options
+ You already have an external system for your constituents (e.g., a member area
on your website or a collaboration platform) that cannot be easily connected to 
CiviCRM

## Features
+ Connect a remote system to CiviCRM to handle highly customizable event 
listings and registration features
+ Pre-defined registration profiles, including a "one-click registration" for 
authenticated users
+ Use different registration profiles within the same event
+ Allow participants to modify or cancel their registrations
+ Alternative approach for defining an event's location
+ Event registration/update profiles configurable using a UI with the [Remote 
Event Form Editor](https://github.com/systopia/remoteeventformeditor) extension
+ Manage sessions/workshops within your event
+ Register additional participants
+ Localize event registration profiles with a locale specified in the get_form 
request (e.g., the current language of the frontend)

## Requirements
+ PHP v7.0+
+ CiviCRM 5.61
+ Dependency on the CiviCRM Extension Remote Tools
+ Dependency on the CiviCRM Extension Extended Contact Matcher
+ A system to serve as your public interface, such as an external website

## Extensions that Integrate with the Remote Event Extension
+ [Remote
  Event Form Editor](https://github.com/systopia/remoteeventformeditor): 
A drag-and-drop form editor for event registration
+ [Event Invitations](https://github.com/systopia/de.systopia.eventinvitation)
+ [Custom Event Communication](https://github.com/systopia/de.systopia.eventmessages)
+ [Event-Checkin](https://github.com/systopia/de.systopia.eventcheckin)

## Documentation
EN: https://docs.civicrm.org/remoteevent/en/latest (automatic publishing)

## We Need Your Support
This CiviCRM extension is provided as Free and Open Source Software, and we are 
happy if you find it useful. However, we have invested a lot of work into it 
(and continue to do so), much of it unpaid. If you benefit from our software, 
please consider making a financial contribution, so we can continue to maintain 
and develop it further.

If you are willing to support us in developing this CiviCRM extension, please 
email [info@systopia.de](mailto:info@systopia.de) to get an invoice or agree on 
a different payment method.
Thank you!

This extension is licensed under 
[AGPL-3.0](https://www.gnu.org/licenses/agpl-3.0).