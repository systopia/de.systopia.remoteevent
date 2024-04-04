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