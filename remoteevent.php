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

use CRM_Remoteevent_ExtensionUtil as E;

/**
 * Implements hook_civicrm_config().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_config/
 */
function remoteevent_civicrm_config(&$config)
{
    _remoteevent_civix_civicrm_config($config);

    // add some event listeners
    \Civi::dispatcher()->addListener('civi.remoteevent.registration.getform', ['CRM_Remoteevent_RegistrationProfile', 'addProfileData']);
}

/**
 * Implements hook_civicrm_xmlMenu().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_xmlMenu
 */
function remoteevent_civicrm_xmlMenu(&$files)
{
    _remoteevent_civix_civicrm_xmlMenu($files);
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
 * Implements hook_civicrm_postInstall().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_postInstall
 */
function remoteevent_civicrm_postInstall()
{
    _remoteevent_civix_civicrm_postInstall();
}

/**
 * Implements hook_civicrm_uninstall().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_uninstall
 */
function remoteevent_civicrm_uninstall()
{
    _remoteevent_civix_civicrm_uninstall();
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
 * Implements hook_civicrm_disable().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_disable
 */
function remoteevent_civicrm_disable()
{
    _remoteevent_civix_civicrm_disable();
}

/**
 * Implements hook_civicrm_upgrade().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_upgrade
 */
function remoteevent_civicrm_upgrade($op, CRM_Queue_Queue $queue = null)
{
    return _remoteevent_civix_civicrm_upgrade($op, $queue);
}

/**
 * Implements hook_civicrm_managed().
 *
 * Generate a list of entities to create/deactivate/delete when this module
 * is installed, disabled, uninstalled.
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_managed
 */
function remoteevent_civicrm_managed(&$entities)
{
    _remoteevent_civix_civicrm_managed($entities);
}

/**
 * Implements hook_civicrm_caseTypes().
 *
 * Generate a list of case-types.
 *
 * Note: This hook only runs in CiviCRM 4.4+.
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_caseTypes
 */
function remoteevent_civicrm_caseTypes(&$caseTypes)
{
    _remoteevent_civix_civicrm_caseTypes($caseTypes);
}

/**
 * Implements hook_civicrm_angularModules().
 *
 * Generate a list of Angular modules.
 *
 * Note: This hook only runs in CiviCRM 4.5+. It may
 * use features only available in v4.6+.
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_angularModules
 */
function remoteevent_civicrm_angularModules(&$angularModules)
{
    _remoteevent_civix_civicrm_angularModules($angularModules);
}

/**
 * Implements hook_civicrm_alterSettingsFolders().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_alterSettingsFolders
 */
function remoteevent_civicrm_alterSettingsFolders(&$metaDataFolders = null)
{
    _remoteevent_civix_civicrm_alterSettingsFolders($metaDataFolders);
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
    _remoteevent_civix_civicrm_entityTypes($entityTypes);
}

/**
 * Implements hook_civicrm_thems().
 */
function remoteevent_civicrm_themes(&$themes)
{
    _remoteevent_civix_civicrm_themes($themes);
}

/**
 * Define custom (Drupal) permissions
 */
function remoteevent_civicrm_permission(&$permissions) {
    $permissions['view Remote Events'] = E::ts('RemoteEvent: list public events');
    $permissions['view all Remote Events'] = E::ts('RemoteEvent: list all events');
}


/**
 * Set permissions RemoteEvent API
 */
function remoteevent_civicrm_alterAPIPermissions($entity, $action, &$params, &$permissions) {
    $permissions['remote_event']['get'] = ['view public Remote Events', 'view all Remote Events'];
    $permissions['remote_event']['get_registration_form'] = ['view public Remote Events', 'view all Remote Events'];
}

/**
 * Add event configuration tabs
 */
function remoteevent_civicrm_tabset($tabsetName, &$tabs, $context) {
    if ($tabsetName == 'civicrm/event/manage') {
        if (!empty($context['event_id'])) {
            CRM_Remoteevent_UI::updateEventTabs($context['event_id'], $tabs);
        } else {
            CRM_Remoteevent_UI::updateEventTabs(null, $tabs);
        }
    }
}
