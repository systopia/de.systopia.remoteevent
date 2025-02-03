## CiviRemote Event API

The extension exposes a new API to remote event listings or registration
systems:

+ ``RemoteEvent.get`` allows querying for remotely available events in the same
   way ``Event.get`` would
+ ``RemoteEvent.create`` allows creating remotely available events in the same
   way ``Event.create`` would
+ ``RemoteParticipant.get_form`` returns a default specification fo the
   registration form required for signing up/cancel/update
+ ``RemoteParticipant.create`` sign somebody up for an event by providing the
   necessary form data (as requested by ``RemoteParticipant``)
+ ``RemoteRegistration.validate`` ask the system for validating the given
   signup data before the actual registration
+ ``RemoteRegistration.cancel`` cancel an existing registration, identified by
   remote_contact_id or token
+ ``RemoteRegistration.update`` update an existing registration, identified by
   remote_contact_id or token

Please note that all these API actions come with an independent set of
permissions.
