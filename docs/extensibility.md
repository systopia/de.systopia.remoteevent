## Extensibility and Interoperability

This extension uses a series of Symfony events to extend and customise the
behaviour of these calls:

+ ``civi.remoteevent.get.params`` modify / restrict query parameters to event
   information
+ ``civi.remoteevent.get.result`` modify / extend event information
+ ``civi.remoteevent.spawn.params`` modify / extend parameters for creating or
   updating a remote event
+ ``civi.remoteevent.registration.validate`` modify / extend signup data
+ ``civi.remoteevent.registration.submit``modify / extend signup process
+ ``civi.remoteevent.registration.cancel``modify / extend cancellation process
+ ``civi.remoteevent.registration.getform``modify / extend registration form
+ ``civi.remoteevent.cancellation.getform``modify / extend cancellation form
+ ``civi.remoteevent.registration_update.getform``modify / extend update form
+ ``civi.remoteevent.label`` modify or override certain labels (e.g. workshop
   groups)