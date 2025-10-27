<?php
/*-------------------------------------------------------+
| SYSTOPIA Remote Event Extension                        |
| Copyright (C) 2020 SYSTOPIA                            |
| Author: B. Endres (endres@systopia.de)                 |
+--------------------------------------------------------+
| This program is released as free software under the    |
| Affero GPL license. You can redistribute it and/or     |
| modify it under the terms of this license which you    |
| can read by viewing the included agpl.txt or online    |
| at www.gnu.org/licenses/agpl.html. Removal of this     |
| copyright header is strictly prohibited without        |
| written permission from the original author(s).        |
+--------------------------------------------------------*/

use Civi\Test\Api3TestTrait;
use Civi\Test\HeadlessInterface;
use Civi\Test\HookInterface;
use Civi\Test\TransactionalInterface;

use CRM_Remoteevent_ExtensionUtil as E;

/**
 * This is the test base class with lots of utility functions
 *
 * @group headless
 */
abstract class CRM_Remoteevent_TestBase extends \PHPUnit\Framework\TestCase implements HeadlessInterface, HookInterface,
                                                                            TransactionalInterface
{
    use Api3TestTrait {
        callAPISuccess as protected traitCallAPISuccess;
    }

    /** @var CRM_Core_Transaction current transaction */
    protected $transaction = null;

    public function setUpHeadless()
    {
        // Civi\Test has many helpers, like install(), uninstall(), sql(), and sqlFile().
        // See: https://docs.civicrm.org/dev/en/latest/testing/phpunit/#civitest
        return \Civi\Test::headless()
            ->install(['de.systopia.xcm'])
            ->install(['de.systopia.identitytracker'])
            ->install(['de.systopia.remotetools'])
            ->installMe(__DIR__)
            ->apply();
    }

    public function setUp(): void
    {
        parent::setUp();
        CRM_Xcm_Configuration::flushProfileCache();
        $this->transaction = new CRM_Core_Transaction();
        $this->setUpXCMProfile('default');

        $profile = CRM_Xcm_Configuration::getConfigProfile('default');

        // jumble the participant status labels so we're sure we only using the names
        CRM_Core_DAO::executeQuery("UPDATE civicrm_participant_status_type SET label = MD5(name);");

        //Civi::settings()->set('remote_event_get_performance_enhancement', true);
    }

    public function tearDown(): void
    {
        $this->transaction->rollback();
        $this->transaction = null;
        CRM_Remoteevent_CustomData::flushCashes();
        parent::tearDown();
    }

    /**
     * Create a new remote event. All of the
     *  vital fields will have default values, that can be overwritten by
     *  the array passed
     *
     * @param array $event_details
     *   list of event parameters
     *
     * @param boolean $use_remote_api
     *   use the exposed RemoteEvent.create API instead of the internal (Event.create)
     */
    public function createRemoteEvent($event_details = [], $use_remote_api = false)
    {
        // prepare event
        $event_data = [
            'title'                                                                => "Event " . microtime(),
            'event_type_id'                                                        => 1,
            'start_date'                                                           => date('Y-m-d', strtotime('tomorrow')),
            'default_role_id'                                                      => 1,
            'is_active'                                                            => 1,
            'event_remote_registration.remote_registration_enabled'                => 1,
            'event_remote_registration.remote_disable_civicrm_registration'        => 1,
            'event_remote_registration.remote_registration_default_profile'        => 'Standard1',
            'event_remote_registration.remote_registration_profiles'               => ['Standard1'],
            'event_remote_registration.remote_registration_default_update_profile' => 'Standard1',
            'event_remote_registration.remote_registration_update_profiles'        => ['Standard1'],
        ];
        foreach ($event_details as $key => $value) {
            if ($value === null) {
                unset($event_data[$key]);
            } else {
                $event_data[$key] = $value;
            }
        }
        // sanity check: default profile should be enabled
        $default_profile = $event_data['event_remote_registration.remote_registration_default_profile'];
        if (!in_array($default_profile, $event_data['event_remote_registration.remote_registration_profiles'])) {
            $event_data['event_remote_registration.remote_registration_profiles'][] = $default_profile;
        }
        // resolve custom fields
        CRM_Remoteevent_CustomData::resolveCustomFields($event_data);

        // create event and reload
        if ($use_remote_api) {
            $result = $this->traitCallAPISuccess('RemoteEvent', 'spawn', $event_data);
            $event = $this->traitCallAPISuccess('RemoteEvent', 'getsingle', ['id' => $result['id']]);
        } else {
            $result = $this->traitCallAPISuccess('Event', 'create', $event_data);
            $event = $this->traitCallAPISuccess('RemoteEvent', 'getsingle', ['id' => $result['id']]);
            CRM_Remoteevent_CustomData::labelCustomFields($event);
        }

        return $event;
    }

    /**
     * Create a new remote event TEMPLATE
     *
     * @param array $event_details
     *   list of event parameters
     *
     * @param boolean $use_remote_api
     *   use the exposed RemoteEvent.create API instead of the internal (Event.create)
     */
    public function createRemoteEventTemplate($event_details = [], $use_remote_api = false)
    {
        // create a standard event
        $event_template = $this->createRemoteEvent($event_details, $use_remote_api);

        // turn it into a template
        $this->traitCallAPISuccess('Event', 'create',[
            'id'             => $event_template['id'],
            'is_template'    => 1,
            'template_title' => 'Template ' . $event_template['title'],
        ]);

        // reload the event
        $event_template = $this->traitCallAPISuccess('Event', 'getsingle', ['id' => $event_template['id']]);
        CRM_Remoteevent_CustomData::labelCustomFields($event_template);
        return $event_template;
    }

    /**
     * Create a new session
     *
     * @params
     * @param array $session_details
     *   overrides the default values
     *
     * @return array
     *  contact data
     */
    public function createEventSession($event_id, $session_details = [])
    {
        // prepare event
        $session_data = [
            'event_id'         => $event_id,
            'title'            => $this->randomString(50),
            'is_active'        => 1,
            'start_date'       => $this->getUniqueDateTime(),
            'end_date'         => $this->getUniqueDateTime(),
            //'slot_id'        => '',
            'category_id'      => 1,
            'type_id'          => 1,
            'description'      => $this->randomString(50),
            'max_participants' => null,
        ];
        foreach ($session_details as $key => $value) {
            $session_data[$key] = $value;
        }

        // create contact
        $result = $this->traitCallAPISuccess('Session', 'create', $session_data);
        $session = $this->traitCallAPISuccess('Session', 'getsingle', ['id' => $result['id']]);
        return $session;
    }

    /**
     * Create a number of events with the same setup,
     *   using the createRemoteEvent function
     *
     * @param integer $count
     * @param array $event_details
     *
     * @return array [event_id => $event_data]
     */
    public function createRemoteEvents($count, $event_details = [])
    {
        $result = [];
        for ($i = 0; $i < $count; $i++) {
            $event = $this->createRemoteEvent($event_details);
            $result[$event['id']] = $event;
        }
        return $result;
    }

    /**
     * Register the given contact to the given event
     *
     * @param integer $contact_id
     * @param integer $event_id
     * @param array $participant_details
     */
    public function registerRemote($event_id, $participant_details = [])
    {
        $participant_data = [
            'event_id'   => $event_id,
        ];
        foreach ($participant_details as $key => $value) {
            $participant_data[$key] = $value;
        }

        return $this->callRemoteEventAPI('RemoteParticipant', 'create', $participant_data);
    }

    /**
     * Register the given contact to the given event
     *
     * @param array $submission
     */
    public function updateRegistration($submission)
    {
        if (empty($submission['token']) && !empty($submission['participant_id'])) {
            $token = CRM_Remotetools_SecureToken::generateEntityToken(
                'Participant', $submission['participant_id'], null, 'update');
            $submission['token'] = $token;
        }
        return $this->callRemoteEventAPI('RemoteParticipant', 'update', $submission);
    }

    /**
     * Cancel the given registration
     *
     * @param array $submission
     */
    public function cancelRegistration($submission)
    {
        if (empty($submission['token']) && !empty($submission['participant_id'])) {
            $token = CRM_Remotetools_SecureToken::generateEntityToken(
                'Participant', $submission['participant_id'], null, 'cancel');
            $submission['token'] = $token;
        }
        return $this->callRemoteEventAPI('RemoteParticipant', 'cancel', $submission);
    }

    /**
     * Wrap an API call to fit the status message system
     *
     * @param $entity
     * @param $action
     * @param $data
     */
    protected function callRemoteEventAPI($entity, $action, $data)
    {
        // first run
        try {
            $result = civicrm_api3($entity, $action, $data);
            $result['is_error'] = 0;
            $this->assertArrayHasKey('status_messages', $result, "API Call {$entity}.{$action} doesn't return 'status_messages'");
            $status_messages = $result['status_messages'];
        } catch (CRM_Core_Exception $ex) {
            $result = [
                'is_error'      => 1,
                'error_message' => $ex->getMessage()
            ];
            if (isset($ex->getExtraParams()['status_messages'])) {
                $status_messages = $ex->getExtraParams()['status_messages'];
            } else {
                $status_messages = [];
            }
        }

        // extract messages
        $severity2list = [
            'error'   => 'errors',
            'warning' => 'warnings',
            'status'  => 'status'
        ];
        foreach ($status_messages as $message) {
            $result[$severity2list[$message['severity']]][] = $message['message'];
        }

        return $result;
    }

    /**
     * Create a new contact
     *
     * @param array $contact_details
     *   overrides the default values
     *
     * @return array
     *  contact data
     */
    public function createContact($contact_details = [])
    {
        // prepare event
        $contact_data = [
            'contact_type' => 'Individual',
            'first_name'   => $this->randomString(10),
            'last_name'    => $this->randomString(10),
            'email'        => $this->randomString(10) . '@' . $this->randomString(10) . '.org',
            'prefix_id'    => 1,
        ];
        foreach ($contact_details as $key => $value) {
            $contact_data[$key] = $value;
        }
        CRM_Remoteevent_CustomData::resolveCustomFields($contact_data);

        // create contact
        $result = $this->traitCallAPISuccess('Contact', 'create', $contact_data);
        $contact = $this->traitCallAPISuccess('Contact', 'getsingle', ['id' => $result['id']]);
        CRM_Remoteevent_CustomData::labelCustomFields($contact);
        return $contact;
    }

    /**
     * Create a number of new contacts
     *  using the createContact function above
     *
     * @param integer $count
     * @param array $contact_details
     *
     * @return array [event_id => $event_data]
     */
    public function createContacts($count, $contact_details = [])
    {
        $result = [];
        for ($i = 0; $i < $count; $i++) {
            $contact = $this->createContact($contact_details);
            $result[$contact['id']] = $contact;
        }
        return $result;

    }

    /**
     * Generate a random string, and make sure we don't collide
     *
     * @param int $length
     *   length of the string
     *
     * @return string
     *   random string
     */
    public function randomString($length = 32)
    {
        static $generated_strings = [];
        $candidate = substr(sha1(random_bytes(32)), 0, $length);
        if (isset($generated_strings[$candidate])) {
            // simply try again (recursively). Is this dangerous? Yes, but veeeery unlikely... :)
            return $this->randomString($length);
        }
        // mark as 'generated':
        $generated_strings[$candidate] = 1;
        return $candidate;
    }

    /**
     * Make sure the given profile exists, and has
     *   a basic amount of matching options
     *
     * @param string $profile_name
     *   name of the profile
     * @param array $profile_data_override
     *   XCM profile spec that differs from the default
     */
    public function setUpXCMProfile($profile_name, $profile_data_override = null)
    {
        // set profile
        $profiles = Civi::settings()->get('xcm_config_profiles');
        if ($profile_data_override) {
            $profiles[$profile_name] = $profile_data_override;
        } else {
            $profiles[$profile_name] = json_decode(file_get_contents(E::path('tests/resources/xcm_profile_testing.json')), 1);
        }
        Civi::settings()->set('xcm_config_profiles', $profiles);
    }

    /**
     * Set a value for the speaker roles
     *
     * @param array $value
     *   a list of role_ids or empty for disabled
     */
    public function setSpeakerRoles($value)
    {
        Civi::settings()->set('remote_registration_speaker_roles', $value);
    }

    /**
     * Use the RemoteEvent.get API to event information
     *
     * @phpstan-param list<int> $event_ids
     *   list of event_ids
     *
     * @phpstan-return list<array<string, mixed>>
     */
    public function getRemoteEvents(array $event_ids): array
    {
        return array_map([$this, 'getRemoteEvent'], $event_ids);
    }

    /**
     * Use the RemoteEvent.get API to event information
     *
     * @param integer $event_id
     *   event id
     *
     * @param array $params
     *   additional parameters
     */
    public function getRemoteEvent($event_id, $params = [])
    {
        $params['id'] = $event_id;
        return $this->traitCallAPISuccess('RemoteEvent', 'getsingle', $params);
    }

    /**
     * Use the RemoteEvent.get API to find events
     *
     * @param array $params
     *   search parameters
     */
    public function findRemoteEvents($params = [])
    {
        return $this->traitCallAPISuccess('RemoteEvent', 'get', $params);
    }

    /**
     * Get a random value subset of the array
     *
     * @param array $array
     *   the array to pick the values
     *
     * @param integer $count
     *   number of elements to pick, will be randomised if not given
     *
     * @return array
     *   subset (keys not retained)
     */
    public function randomSubset($array, $count = null)
    {
        if ($count === null) {
            $count = mt_rand(1, count($array) - 1);
        }

        $random_keys = array_rand($array, $count);
        if (!is_array($random_keys)) {
            $random_keys = [$random_keys];
        }

        // create result array
        $result = [];
        foreach ($random_keys as $random_key) {
            $result[] = $array[$random_key];
        }
        return $result;
    }

    /**
     * Get a remote contact key for the given contact.
     *  if no such key exists, create one
     *
     * @param integer $contact_id
     *
     * @return string
     *   contact key
     */
    public function getRemoteContactKey($contact_id)
    {
        $contact_id = (int) $contact_id;
        $key = CRM_Core_DAO::singleValueQuery("
            SELECT identifier
            FROM civicrm_value_contact_id_history
            WHERE identifier_type = 'remote_contact'
              AND entity_id = {$contact_id}
            LIMIT 1
        ");
        if (!$key) {
            $key = $this->randomString();
            CRM_Core_DAO::executeQuery("
                INSERT INTO civicrm_value_contact_id_history (entity_id, identifier, identifier_type, used_since)
                VALUES ({$contact_id}, '{$key}', 'remote_contact', NOW())
            ");
        }

        $verify_contact_id = CRM_Remotetools_Contact::getByKey($key);
        $this->assertEquals($contact_id, $verify_contact_id, "Couldn't generate remote contact key.");
        return $key;
    }

    /**
     * Get the ID of the 'Invited' participant status, as used by the eventinvitation extension
     *
     * @return integer participant status ID
     */
    public function getParticipantInvitedStatus()
    {
        // code copied from de.systopia.eventinvitation/CRM/Eventinvitation/Upgrader.php
        $apiResult = civicrm_api3(
            'ParticipantStatusType',
            'get',
            [
                'name' => 'Invited'
            ]
        );

        if ($apiResult['count'] === 0) {
            $max_weight = (int) CRM_Core_DAO::singleValueQuery("SELECT MAX(weight) FROM civicrm_participant_status_type");
            $apiResult = civicrm_api3(
                'ParticipantStatusType',
                'create',
                [
                    'name' => 'Invited',
                    'label' => 'Invited in your language',
                    'visibility_id' => 'public',
                    'class' => 'Waiting',
                    'is_active' => 1,
                    'weight' => $max_weight + 1,
                    'is_reserved' => 1,
                    'is_counted' => 0,
                ]
            );
        }
        return $apiResult['id'];
    }

    /**
     * Get the ID of the given participant status
     *
     * @param string $status_name
     *   name of the status
     * @param boolean $reset_cache
     *   reset the internal cache
     */
    public function getParticipantStatusId($status_name, $reset_cache = false)
    {
        static $participant_statuses = null;
        if ($reset_cache) {
            $participant_statuses = null;
        }
        if ($participant_statuses === null) {
            $participant_statuses = [];
            $query = civicrm_api3('ParticipantStatusType', 'get', [
                'option.limit' => 0,
            ]);
            foreach ($query['values'] as $status) {
                $participant_statuses[$status['name']] = $status['id'];
            }
        }

        $this->assertArrayHasKey($status_name, $participant_statuses, "Participant status '{$status_name} doesn't exist.");
        return $participant_statuses[$status_name];
    }

    /**
     * Verify that the participant object has the right status
     *
     * @param integer $participant_id
     *   the participant ID
     * @param integer|string $participant_status
     *   the expected participant status
     * @param string $failure_msg
     *   message to log in case of failure
     */
    public function assertParticipantStatus($participant_id, $participant_status, $failure_msg)
    {
        $participant = $this->traitCallAPISuccess('Participant', 'get', ['id' => $participant_id]);
        $this->assertGreaterThan(0, $participant['count'], $failure_msg . " (doesn't exist)");
        $this->assertLessThan(2, $participant['count'], $failure_msg . " (ambiguous)");
        $participant = reset($participant['values']);

        $this->assertEquals($this->getParticipantStatusId($participant_status, true), $participant['participant_status_id'], $failure_msg);
    }

    /**
     * Verify that the RemoteContact.get_form standard 'fields' are there
     *
     * @param array $fields
     *   the fields reported by the get_form
     * @param boolean $strip_fields
     *   if true, the standard fields are removed from the $fields array
     */
    public function assertGetFormStandardFields(&$fields, $strip_fields = false)
    {
        // todo: check more?
        $this->assertArrayHasKey('event_id', $fields, "RemoteContact.get_form should contain 'event_id' field");
        $field_spec = $fields['event_id'];
        $this->assertArrayHasKey('profile', $fields, "RemoteContact.get_form should contain 'profile' field");
        $field_spec = $fields['profile'];
        $this->assertArrayHasKey('remote_contact_id', $fields, "RemoteContact.get_form should contain 'remote_contact_id' field");
        $field_spec = $fields['remote_contact_id'];

        if ($strip_fields) {
            unset($fields['event_id']);
            unset($fields['profile']);
            unset($fields['remote_contact_id']);
        }
    }

    /**
     * Create a new, unique campaign
     */
    public function getCampaign() {
        $campaign_name = $this->randomString();
        $campaign = $this->traitCallAPISuccess('Campaign', 'create', [
            'name' => $campaign_name,
            'title' => $campaign_name,
            'campaign_type_id' => 1,
            'status_id' => 1,
        ]);
        return $this->traitCallAPISuccess('Campaign', 'getsingle', ['id' => $campaign['id']]);
    }

    /**
     * Get a (within this test) unique
     *  timestamp. It starts with now+1h and
     *  increments in 5 minute interval
     *
     * @return string timestamp
     */
    public function getUniqueDateTime()
    {
        static $last_timestamp = null;
        if ($last_timestamp === null) {
            $last_timestamp = strtotime('now + 1 hour');
        } else {
            $last_timestamp = strtotime('+5 minutes', $last_timestamp);
        }
        return date('YmdHis', $last_timestamp);
    }


    /**
     * Create an associative array from a list of fields specs in the form of
     * [
     *   [
     *      'name'        => 'some field name',
     *      'type'        => CRM_Utils_Type::T_STRING,
     *      'value'       => 'some value',
     *      'title'       => 'some title',
     *      'localizable' => 0,
     *   ],
     *    ...
     * ]
     *
     * @param array $field_array
     *    the outer array
     * @param string $key_field
     *    the inner field to be used as key
     * @param string $value_field
     *    the inner field to be used as value
     *
     * @return array
     *    the extracted associative array
     */
    public function mapFieldArray($field_array, $key_field = 'name', $value_field = 'value')
    {
        $result = [];
        foreach ($field_array as $field_spec) {
            if (!isset($field_spec[$key_field])) {
                $this->fail("Field array doesn't have key field '{$key_field}'");
            }
            if (!isset($field_spec[$value_field])) {
                $this->fail("Field array doesn't have value field '{$value_field}'");
            }
            $result[$field_spec[$key_field]] = $field_spec[$value_field];
        }
        return $result;
    }
}
