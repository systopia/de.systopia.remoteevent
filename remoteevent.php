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

require_once 'remoteevent.civix.php';

use Civi\RemoteEvent\Event\GetFieldsEvent;
use Civi\RemoteEvent\Event\GetParamsEvent;
use Civi\RemoteEvent\Event\GetResultEvent;
use Civi\RemoteEvent\Event\RegistrationProfileListEvent;
use Civi\RemoteParticipant\Event\CancelEvent;
use Civi\RemoteParticipant\Event\GetCancelParticipantFormEvent;
use Civi\RemoteParticipant\Event\GetCreateParticipantFormEvent;
use Civi\RemoteParticipant\Event\GetUpdateParticipantFormEvent;
use Civi\RemoteParticipant\Event\RegistrationEvent;
use Civi\RemoteParticipant\Event\UpdateEvent;
use Civi\RemoteParticipant\Event\ValidateEvent;
use Civi\RemoteParticipant\EventSubscriber\MailingListSubscriptionSubscriber;
use Civi\RemoteParticipant\MailingList\DoubleOptInEmailSender;
use Civi\RemoteParticipant\MailingList\MailingListSubscriptionManager;
use Civi\RemoteParticipant\MailingList\DoubleOptInTokenGenerator;
use Civi\RemoteParticipant\RegistrationEventFactory;
use Civi\RemoteTools\Helper\FilePersisterInterface;
use CRM_Remoteevent_ExtensionUtil as E;
use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Implements hook_civicrm_config().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_config/
 */
function remoteevent_civicrm_config(&$config)
{
    _remoteevent_civix_civicrm_config($config);

    // register events (with our own wrapper to avoid duplicate registrations)
    $dispatcher = new \Civi\RemoteDispatcher();

    // EVENT GETFIELDS
    $dispatcher->addUniqueListener(
          GetFieldsEvent::NAME,
        ['CRM_Remoteevent_EventLocation', 'addFieldSpecs']);
    $dispatcher->addUniqueListener(
        GetFieldsEvent::NAME,
        ['CRM_Remoteevent_EventSpeaker', 'addFieldSpecs']);
    $dispatcher->addUniqueListener(
        GetFieldsEvent::NAME,
        ['CRM_Remoteevent_EventSessions', 'addFieldSpecs']);

    // EVENT GET PARAMETERS
    $dispatcher->addUniqueListener(
        GetParamsEvent::NAME,
        ['CRM_Remoteevent_RemoteEvent', 'deriveEventID']);
    $dispatcher->addUniqueListener(
        GetParamsEvent::NAME,
        ['CRM_Remoteevent_EventFlags', 'processFlagFilters']);
    $dispatcher->addUniqueListener(
        GetParamsEvent::NAME,
        ['CRM_Remoteevent_EventCustomFields', 'processCustomFieldFilters']);



    // EVENT GET
    $dispatcher->addUniqueListener(
        GetResultEvent::NAME,
        ['CRM_Remoteevent_EventFlags', 'calculateFlags'], 1000 /* at the very beginning */);
    $dispatcher->addUniqueListener(
        GetResultEvent::NAME,
        ['CRM_Remoteevent_EventFlags', 'applyFlagFilters'], -100 /** will trim entries */);
    $dispatcher->addUniqueListener(
        GetResultEvent::NAME,
        ['CRM_Remoteevent_EventLocation', 'addLocationData'], -1000 /** late */);
    $dispatcher->addUniqueListener(
        GetResultEvent::NAME,
        ['CRM_Remoteevent_EventSpeaker', 'addSpeakerData'], -1000 /** late */);
    $dispatcher->addUniqueListener(
        GetResultEvent::NAME,
        ['CRM_Remoteevent_EventSessions', 'addSessionData'], -1000 /** late */);

    // EVENT REGISTRATION.GETFORM
    $dispatcher->addUniqueListener(
        GetCreateParticipantFormEvent::NAME,
            ['CRM_Remoteevent_RegistrationProfile', 'addProfileData']);
    $dispatcher->addUniqueListener(
       GetCreateParticipantFormEvent::NAME,
        ['CRM_Remoteevent_Registration', 'addGtacField']);
    $dispatcher->addUniqueListener(
       GetCreateParticipantFormEvent::NAME,
        ['CRM_Remoteevent_EventSessions', 'addSessionFields']);
    $dispatcher->addUniqueListener(
       GetCreateParticipantFormEvent::NAME,
        ['CRM_Remoteevent_EventSessions', 'addRegisteredSessions']);

    // EVENT REGISTRATION_UPDATE.GETFORM
    $dispatcher->addUniqueListener(
        GetUpdateParticipantFormEvent::NAME,
        ['CRM_Remoteevent_RegistrationProfile', 'addProfileData']);
    $dispatcher->addUniqueListener(
        GetUpdateParticipantFormEvent::NAME,
        ['CRM_Remoteevent_EventSessions', 'addSessionFields']);
    $dispatcher->addUniqueListener(
        GetUpdateParticipantFormEvent::NAME,
        ['CRM_Remoteevent_EventSessions', 'addRegisteredSessions']);
    $dispatcher->addUniqueListener(
        GetUpdateParticipantFormEvent::NAME,
        [CRM_Remoteevent_RegistrationUpdate::class, 'addAdditionalParticipantInfo']);

    // EVENT CANCELLATION.GETFORM
    $dispatcher->addUniqueListener(
        GetCancelParticipantFormEvent::NAME,
        [CRM_Remoteevent_RegistrationCancel::class, 'addAdditionalParticipantInfo']
    );



    // EVENT REGISTRATION.VALIDATE
    $dispatcher->addUniqueListener(
        ValidateEvent::NAME,
            ['CRM_Remoteevent_RegistrationProfile', 'validateProfileData']);
    $dispatcher->addUniqueListener(
        ValidateEvent::NAME,
        ['CRM_Remoteevent_EventSessions', 'validateSessionSubmission']);

    // EVENT REGISTRATION.SUBMIT
    $dispatcher->addUniqueListener(
        RegistrationEvent::NAME,
        ['CRM_Remoteevent_EventSessions', 'extractSessions'], CRM_Remoteevent_Registration::BEFORE_CONTACT_IDENTIFICATION);
    $dispatcher->addUniqueListener(
        RegistrationEvent::NAME,
        ['CRM_Remoteevent_Registration', 'identifyRemoteContact'], CRM_Remoteevent_Registration::STAGE1_CONTACT_IDENTIFICATION);
    $dispatcher->addUniqueListener(
        RegistrationEvent::NAME,
        ['CRM_Remoteevent_Registration', 'createContactXCM'], CRM_Remoteevent_Registration::STAGE1_CONTACT_IDENTIFICATION);
    $dispatcher->addUniqueListener(
        RegistrationEvent::NAME,
        ['CRM_Remoteevent_Registration', 'verifyContactNotRegistered'], CRM_Remoteevent_Registration::AFTER_CONTACT_IDENTIFICATION);
    $dispatcher->addUniqueListener(
        RegistrationEvent::NAME,
        ['CRM_Remoteevent_Registration', 'confirmExistingParticipant'], CRM_Remoteevent_Registration::BEFORE_PARTICIPANT_CREATION + 40);
    $dispatcher->addUniqueListener(
        RegistrationEvent::NAME,
        ['CRM_Remoteevent_Registration', 'determineParticipantStatus'], CRM_Remoteevent_Registration::BEFORE_PARTICIPANT_CREATION + 20);
    $dispatcher->addUniqueListener(
        RegistrationEvent::NAME,
        ['CRM_Remoteevent_Registration', 'createParticipant'], CRM_Remoteevent_Registration::STAGE2_PARTICIPANT_CREATION);
    $dispatcher->addUniqueListener(
        RegistrationEvent::NAME,
        ['CRM_Remoteevent_Registration', 'registerAdditionalParticipants'], CRM_Remoteevent_Registration::STAGE2_PARTICIPANT_CREATION);
    $dispatcher->addUniqueListener(
        RegistrationEvent::NAME,
        ['CRM_Remoteevent_EventSessions', 'synchroniseSessions'], CRM_Remoteevent_Registration::AFTER_PARTICIPANT_CREATION);

    // EVENT REGISTRATION.UPDATE
    $dispatcher->addUniqueListener(
        UpdateEvent::NAME,
        ['CRM_Remoteevent_EventSessions', 'extractSessions'], CRM_Remoteevent_RegistrationUpdate::STAGE1_PARTICIPANT_IDENTIFICATION);
    $dispatcher->addUniqueListener(
        UpdateEvent::NAME,
        ['CRM_Remoteevent_RegistrationUpdate', 'loadParticipant'], CRM_Remoteevent_RegistrationUpdate::STAGE1_PARTICIPANT_IDENTIFICATION);
    $dispatcher->addUniqueListener(
        UpdateEvent::NAME,
        ['CRM_Remoteevent_RegistrationUpdate', 'addProfileData'], CRM_Remoteevent_RegistrationUpdate::BEFORE_APPLY_CONTACT_CHANGES + 100);
    $dispatcher->addUniqueListener(
        UpdateEvent::NAME,
        ['CRM_Remoteevent_RegistrationUpdate', 'updateContact'], CRM_Remoteevent_RegistrationUpdate::STAGE2_APPLY_CONTACT_CHANGES);
    $dispatcher->addUniqueListener(
        UpdateEvent::NAME,
        ['CRM_Remoteevent_Registration', 'confirmExistingParticipant'], CRM_Remoteevent_RegistrationUpdate::STAGE3_APPLY_PARTICIPANT_CHANGES + 20);
    $dispatcher->addUniqueListener(
        UpdateEvent::NAME,
        ['CRM_Remoteevent_RegistrationUpdate', 'updateParticipant'], CRM_Remoteevent_RegistrationUpdate::STAGE3_APPLY_PARTICIPANT_CHANGES);
    $dispatcher->addUniqueListener(
        UpdateEvent::NAME,
        ['CRM_Remoteevent_EventSessions', 'synchroniseSessions'], CRM_Remoteevent_RegistrationUpdate::AFTER_APPLY_PARTICIPANT_CHANGES);

    // EVENT REGISTRATION.CANCEL
    $dispatcher->addUniqueListener(
        CancelEvent::NAME,
        [CRM_Remoteevent_RegistrationCancel::class, 'cancelAdditionalParticipants']
    );

    // EVENTMESSAGES.TOKENS
    // 1) REMOTE EVENT TOKENS
    $dispatcher->addUniqueListener(
        'civi.eventmessages.tokens',
        ['CRM_Remoteevent_RemoteEvent', 'addTokens']
    );
    $dispatcher->addUniqueListener(
        'civi.eventmessages.tokenlist',
        ['CRM_Remoteevent_RemoteEvent', 'listTokens']
    );

    // 2) SESSION TOKENS
    $dispatcher->addUniqueListener(
        'civi.eventmessages.tokens',
        ['CRM_Remoteevent_EventSessions', 'addTokens']
    );
    $dispatcher->addUniqueListener(
        'civi.eventmessages.tokenlist',
        ['CRM_Remoteevent_EventSessions', 'listTokens']
    );

    // 2) EVENT LOCATION TOKENS
    $dispatcher->addUniqueListener(
        'civi.eventmessages.tokens',
        ['CRM_Remoteevent_EventLocation', 'addTokens']
    );
    $dispatcher->addUniqueListener(
        'civi.eventmessages.tokenlist',
        ['CRM_Remoteevent_EventLocation', 'listTokens']
    );

    // TODO hier andere Profile hinzufügen
    $dispatcher->addUniqueListener(
        RegistrationProfileListEvent::NAME,
        ['CRM_Remoteevent_RegistrationProfile','addOptionValueProfiles']
    );

}

/**
 * Implements hook_civicrm_container
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_container/
 *
 * @param \Symfony\Component\DependencyInjection\ContainerBuilder $container
 */
function remoteevent_civicrm_container(ContainerBuilder $container) {
    $container->addResource(new FileResource(__FILE__));
    $container->addCompilerPass(new Civi\RemoteEvent\CompilerPass());
    $container->autowire(RegistrationEventFactory::class)->setPublic(TRUE);
    $container->setAlias('remoteevent.file_persister', FilePersisterInterface::class)->setPublic(TRUE);

    $container->autowire(MailingListSubscriptionSubscriber::class)
      ->addTag('kernel.event_subscriber');
    $container->autowire(MailingListSubscriptionManager::class)
      // Used in ConfirmSubscriptionAction
      ->setPublic(TRUE);
    $container->autowire(DoubleOptInTokenGenerator::class);
    $container->autowire(DoubleOptInEmailSender::class);
}

/**
 * Implements hook_civicrm_install().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_install
 */
function remoteevent_civicrm_install()
{
    _remoteevent_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_enable
 */
function remoteevent_civicrm_enable()
{
    _remoteevent_civix_civicrm_enable();
}

/**
 * Implements hook_civicrm_entityTypes().
 *
 * Declare entity types provided by this module.
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_entityTypes
 */
function remoteevent_civicrm_entityTypes(&$entityTypes)
{
    $entityTypes['CRM_Remoteevent_DAO_Session'] = [
        'name' => 'Session',
        'class' => 'CRM_Remoteevent_DAO_Session',
        'table' => 'civicrm_session'
    ];
    $entityTypes['CRM_Remoteevent_DAO_ParticipantSession'] = [
        'name' => 'ParticipantSession',
        'class' => 'CRM_Remoteevent_DAO_ParticipantSession',
        'table' => 'civicrm_participant_session'
    ];
}

/**
 * Define custom (Drupal) permissions
 */
function remoteevent_civicrm_permission(&$permissions)
{
  $permissions['view public Remote Events'] = [
    'label' => E::ts('CiviRemote Event: List public events'),
    'description' => E::ts('Allows listing remote events marked as public via the CiviRemote Event API.'),
  ];
  $permissions['view all Remote Events'] = [
    'label' => E::ts('CiviRemote Event: List all events'),
    'description' => E::ts('Allows listing all remote events via the CiviRemote Event API.'),
  ];
  $permissions['spawn Remote Events'] = [
    'label' => E::ts('CiviRemote Event: Spawn (create) events'),
    'description' => E::ts('Allows creating remote events via the CiviRemote Event API for further editing.'),
  ];
  $permissions['register to Remote Events'] = [
    'label' => E::ts('CiviRemote Event: Register'),
    'description' => E::ts('Allows registering for remote events via the CiviRemote Event API.'),
  ];
  $permissions['edit Remote Event registrations'] = [
    'label' => E::ts('CiviRemote Event: Edit registration'),
    'description' => E::ts('Allows updating registrations for remote events via the CiviRemote Event API.'),
  ];
  $permissions['cancel Remote Events registrations'] = [
    'label' => E::ts('CiviRemote Event: Cancel registration'),
    'description' => E::ts('Allows cancelling registrations for remote events via the CiviRemote Event API.'),
  ];
}

/**
 * Set permissions RemoteEvent API
 */
function remoteevent_civicrm_alterAPIPermissions($entity, $action, &$params, &$permissions)
{
    // RemoteEvent entity
    $permissions['remote_event']['meta']      = ['access CiviEvent'];
    $permissions['remote_event']['get']       = ['view public Remote Events', 'view all Remote Events'];
    $permissions['remote_event']['spawn']     = ['spawn Remote Events'];
    $permissions['remote_event']['getcount']  = ['view public Remote Events', 'view all Remote Events'];
    $permissions['remote_event']['getfields'] = ['view public Remote Events', 'view all Remote Events'];

    // Session entity
    $permissions['session']['meta']      = ['access CiviEvent'];
    $permissions['session']['get']       = ['access CiviEvent'];
    $permissions['session']['create']    = ['access CiviEvent', 'edit all events'];
    $permissions['session']['update']    = ['access CiviEvent', 'edit all events'];
    $permissions['session']['getcount']  = ['access CiviEvent'];
    $permissions['session']['getfields'] = ['access CiviEvent'];

    // ParticipantSession entity
    $permissions['participant_session']['meta']      = ['access CiviEvent'];
    $permissions['participant_session']['get']       = ['access CiviEvent'];
    $permissions['participant_session']['create']    = ['access CiviEvent', 'edit all events'];
    $permissions['participant_session']['update']    = ['access CiviEvent', 'edit all events'];
    $permissions['participant_session']['delete']    = ['access CiviEvent', 'edit all events'];
    $permissions['participant_session']['getcount']  = ['access CiviEvent'];
    $permissions['participant_session']['getfields'] = ['access CiviEvent'];

    // RemoteParticipant entity
    $permissions['remote_participant']['meta']      = ['access CiviEvent'];
    $permissions['remote_participant']['get_form'] = ['view public Remote Events', 'view all Remote Events'];
    $permissions['remote_participant']['get']      = ['edit Remote Event registrations'];
    $permissions['remote_participant']['create']   = ['register to Remote Events'];
    $permissions['remote_participant']['validate'] = ['register to Remote Events'];
    $permissions['remote_participant']['cancel']   = ['cancel Remote Events registrations'];
    $permissions['remote_participant']['update']   = ['edit Remote Event registrations'];
}

/**
 * Add event configuration tabs
 */
function remoteevent_civicrm_tabset($tabsetName, &$tabs, $context)
{
    if ($tabsetName == 'civicrm/event/manage') {
        if (!empty($context['event_id'])) {
            CRM_Remoteevent_UI::updateEventTabs($context['event_id'], $tabs);
        } else {
            CRM_Remoteevent_UI::updateEventTabs(null, $tabs);
        }
    }
}

/**
 * Implementation of hook_civicrm_copy
 */
function remoteevent_civicrm_copy($objectName, &$object)
{
    if ($objectName == 'Event') {
        // we have the new event ID...
        $new_event_id = (int) $object->id;
        $original_event_id = null;

        // ...unfortunately, we have to dig up the original event ID:
        $callstack = debug_backtrace();
        foreach ($callstack as $call) {
            if (isset($call['class']) && isset($call['function'])) {
                if ($call['class'] == 'CRM_Event_BAO_Event' && $call['function'] == 'copy') {
                    // this should be it:
                    $original_event_id = (int) $call['args'][0];
                    CRM_Remoteevent_BAO_Session::copySessions($original_event_id, $new_event_id);
                    break;
                }
            }
        }

        // mitigation for RE-28, where the remote event data is not copied
        // @see https://github.com/systopia/de.systopia.remoteevent/issues/28
        if ($original_event_id && $new_event_id) {
            CRM_Remoteevent_Tools::cloneEventCustomDataTables($original_event_id, $new_event_id);
        }
    }
}

/**
 * Monitor Participant objects
 */
function remoteevent_civicrm_pre($op, $objectName, $id, &$params)
{
    if (($op == 'edit' || $op == 'create') && $objectName == 'Participant') {
        CRM_Remoteevent_ChangeActivity::recordPre($id, $params);
    }
}

function remoteevent_civicrm_custom($op, $groupID, $entityID, &$params): void
{
    foreach ($params as $param) {
        if (($param['entity_table'] ?? null) === 'civicrm_participant') {
            CRM_Remoteevent_ChangeActivity::recordPost($entityID, true);
            break;
        }
    }
}

/**
 * Monitor Participant objects
 */
function remoteevent_civicrm_post($op, $objectName, $objectId, &$objectRef)
{
    if (($op == 'edit' || $op == 'create') && $objectName == 'Participant') {
        CRM_Remoteevent_ChangeActivity::recordPost($objectId);
    }
}

/**
 * Inject session information
 */
function remoteevent_civicrm_pageRun(&$page) {
    $pageName = $page->getVar('_name');
    if ($pageName == 'CRM_Event_Page_Tab') {
        CRM_Remoteevent_Form_ParticipantSessions::injectSessionsInfo($page);
    }
}

function remoteevent_civicrm_links($op, $objectName, $objectId, &$links, &$mask, &$values) {
    if ($objectName == 'Participant' && $op == 'participant.selector.row') {
        $links[] = [
            'name'  => E::ts('Sessions'),
            'url'   =>  CRM_Utils_System::url('civicrm/event/participant/sessions', "participant_id={$objectId}&reset=1"),
            'title' => E::ts('Sessions'),
            'class' => '',
        ];
    }
}

/**
 * Implementation of hook_civicrm_searchTasks,
 *  to inject our 'Session Registration' task
 */
function remoteevent_civicrm_searchTasks($objectType, &$tasks)
{
    // add "Session Registration" task to participant list
    if ($objectType == 'event') {
        $tasks[] = [
            'title' => E::ts('Session Registration'),
            'class' => 'CRM_Remoteevent_Form_Task_ParticipantSession',
            'result' => false
        ];
    }
}
