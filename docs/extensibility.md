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