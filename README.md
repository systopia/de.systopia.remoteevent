# SYSTOPIA's Remote Event Extension

This extension will provide a lot of additional features to CiviCRM's events.
In particular, it provides an integration with calendars and signup forms outside CiviCRM.

This extension is licensed under [AGPL-3.0](LICENSE.txt).

## Features

TODO

## Requirements

* PHP v7.0+
* CiviCRM 5.0


## Remote Event API

The extension exposes a new API to remote event listings or registration systems:

1. ``RemoteEvent.get`` allows querying for remotely available events in the same way ``Event.get`` would
1. ``RemoteParticipant.get_form`` returns a default specification fo the registration form required for signing up/cancel/update
1. ``RemoteParticipant.create`` sign somebody up for an event by providing the necessary form data (as requested by ``RemoteParticipant``)
1. ``RemoteRegistration.validate`` ask the system for validating the given signup data before the actual registration
1. ``RemoteRegistration.update`` get the data of an already existing registration, used for upgrades.

Please note that all these API actions come with an independent set of permissions.

## Extensibility and Interoperability

This extension uses a series of Symfony events to extend and customise the behaviour of these calls:

1. ``civi.remoteevent.get.parameters`` modify / restrict query parameters to event information
1. ``civi.remoteevent.get.result`` modify / extend event information
1. ``civi.remoteevent.registration.validate`` modify / extend signup data
1. ``civi.remoteevent.registration.submit``modify / extend signup process


