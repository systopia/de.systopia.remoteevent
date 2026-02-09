<?php
/*
 * Copyright (C) 2024 SYSTOPIA GmbH
 *
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU Affero General Public License as published by
 *  the Free Software Foundation in version 3.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU Affero General Public License for more details.
 *
 *  You should have received a copy of the GNU Affero General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types = 1);

namespace Civi\RemoteParticipant;

use Civi\Api4\CustomField;
use Civi\Api4\CustomGroup;
use Civi\Api4\File;
use Civi\Api4\Participant;
use Civi\RemoteEvent\AbstractRemoteEventHeadlessTestCase;
use Civi\RemoteEvent\Fixtures\ContactFixture;
use Civi\RemoteEvent\Fixtures\RemoteEventFixture;

/**
 * @group headless
 * @coversNothing
 *   TODO: Document actual coverage.
 */
final class FileUploadTest extends AbstractRemoteEventHeadlessTestCase {

  public function test(): void {
    // Creating custom groups and custom fields changes the DB schema and thus flushes the transaction.
    \Civi\Core\Transaction\Manager::singleton()->forceRollback();

    $customGroup = CustomGroup::create(FALSE)
      ->setValues([
        'name' => 'test_participant_custom_group',
        'title' => 'Participant Custom Group',
        'extends' => 'Participant',
      ])->execute()->single();

    try {
      $customField = CustomField::create(FALSE)->setValues([
          'custom_group_id.name' => 'test_participant_custom_group',
          'name' => 'file',
          'label' => 'File Test',
          'data_type' => 'File',
          'html_type' => 'File',
        ])->execute()->single();

      \Civi\Core\Transaction\Manager::singleton()->inc();
      \Civi\Core\Transaction\Manager::singleton()->getFrame()->setRollbackOnly();

      \CRM_Remoteevent_RegistrationProfile_Mock::register();
      \CRM_Remoteevent_RegistrationProfile_Mock::$fields = [
        'file' => [
          'name' => 'file',
          'entity_name' => 'Participant',
          'entity_field_name' => 'test_participant_custom_group.file',
          'type' => 'File',
          'required' => 1,
          'label' => 'File',
        ],
      ];

      $contact = ContactFixture::addIndividual();
      $event = RemoteEventFixture::addFixture();
      \CRM_Remotetools_Contact::storeRemoteKey('remoteId', $contact['id']);

      $e = NULL;
      try {
        civicrm_api3('RemoteParticipant', 'create', [
          'event_id' => $event['id'],
          'remote_contact_id' => 'remoteId',
          'file' => NULL,
        ]);
      } catch (\CRM_Core_Exception $e) {
        static::assertSame('Required', $e->getMessage());
      }
      static::assertNotNull($e);

      $remoteParticipant = civicrm_api3('RemoteParticipant', 'create', [
        'event_id' => $event['id'],
        'remote_contact_id' => 'remoteId',
        'file' => [
          'filename' => 'test.txt',
          'content' => base64_encode('test'),
        ],
      ]);
      static::assertEmpty($remoteParticipant['is_error']);

      $participant = Participant::get(FALSE)
        ->addSelect('*', 'test_participant_custom_group.file')
        ->execute()
        ->single();

      static::assertSame($contact['id'], $participant['contact_id']);
      static::assertSame((int) $remoteParticipant['participant_id'], $participant['id']);
      static::assertIsInt($participant['test_participant_custom_group.file']);
      static::assertCiviFileContentEquals('test', $participant['test_participant_custom_group.file']);

      $fileId = $participant['test_participant_custom_group.file'];
      // File is not required on update, previous file is kept.
      civicrm_api3('RemoteParticipant', 'update', [
        'event_id' => $event['id'],
        'remote_contact_id' => 'remoteId',
        'file' => NULL,
      ]);

      $participant = Participant::get(FALSE)
        ->addSelect('*', 'test_participant_custom_group.file')
        ->execute()
        ->single();
      static::assertSame($fileId, $participant['test_participant_custom_group.file']);

      // File should be changed, previous file is not removed from file system.
      civicrm_api3('RemoteParticipant', 'update', [
        'event_id' => $event['id'],
        'remote_contact_id' => 'remoteId',
        'file' => [
          'filename' => 'test2.txt',
          'content' => base64_encode('test2'),
        ],
      ]);

      $participant = Participant::get(FALSE)
        ->addSelect('*', 'test_participant_custom_group.file')
        ->execute()
        ->single();
      static::assertNotSame($fileId, $participant['test_participant_custom_group.file']);
      static::assertCiviFileContentEquals('test2', $participant['test_participant_custom_group.file']);
    }
    finally {
      \Civi\Core\Transaction\Manager::singleton()->forceRollback();

      if (isset($customField)) {
        CustomField::delete(FALSE)->addWhere('id', '=', $customField['id'])->execute();
      }

      CustomGroup::delete(FALSE)->addWhere('id', '=', $customGroup['id'])->execute();

      // There needs to be an open transaction to prevent an error when the CiviCRM test listener tries to rollback a
      // transaction.
      \Civi\Core\Transaction\Manager::singleton()->inc();
      \Civi\Core\Transaction\Manager::singleton()->getFrame()->setRollbackOnly();
    }
  }

  public function testMaxFilesize(): void {
    // Creating custom groups and custom fields changes the DB schema and thus flushes the transaction.
    \Civi\Core\Transaction\Manager::singleton()->forceRollback();

    $customGroup = CustomGroup::create(FALSE)
      ->setValues([
        'name' => 'test_participant_custom_group2',
        'title' => 'Participant Custom Group',
        'extends' => 'Participant',
      ])->execute()->single();

    try {
      $customField = CustomField::create(FALSE)->setValues([
        'custom_group_id.name' => 'test_participant_custom_group2',
        'name' => 'file2',
        'label' => 'File Test',
        'data_type' => 'File',
        'html_type' => 'File',
      ])->execute()->single();

      \Civi\Core\Transaction\Manager::singleton()->inc();
      \Civi\Core\Transaction\Manager::singleton()->getFrame()->setRollbackOnly();

      \CRM_Remoteevent_RegistrationProfile_Mock::register();
      \CRM_Remoteevent_RegistrationProfile_Mock::$fields = [
        'file' => [
          'name' => 'file',
          'entity_name' => 'Participant',
          'entity_field_name' => 'test_participant_custom_group2.file2',
          'type' => 'File',
          'required' => 1,
          'label' => 'File',
          'max_filesize' => 6,
        ],
      ];

      $contact = ContactFixture::addIndividual();
      $event = RemoteEventFixture::addFixture();
      \CRM_Remotetools_Contact::storeRemoteKey('remoteId2', $contact['id']);

      $e = NULL;
      try {
        civicrm_api3('RemoteParticipant', 'create', [
          'event_id' => $event['id'],
          'remote_contact_id' => 'remoteId2',
          'file' => [
            'filename' => 'test.txt',
            'content' => base64_encode('this is too long'),
          ],
        ]);
      } catch (\CRM_Core_Exception $e) {
        static::assertSame('File too large', $e->getMessage());
      }
      static::assertNotNull($e);

      $remoteParticipant = civicrm_api3('RemoteParticipant', 'create', [
        'event_id' => $event['id'],
        'remote_contact_id' => 'remoteId2',
        'file' => [
          'filename' => 'test.txt',
          'content' => base64_encode('test'),
        ],
      ]);
      static::assertEmpty($remoteParticipant['is_error']);

      $participant = Participant::get(FALSE)
        ->addSelect('*', 'test_participant_custom_group2.file2')
        ->execute()
        ->single();
      static::assertIsInt($participant['test_participant_custom_group2.file2']);
    }
    finally {
      \Civi\Core\Transaction\Manager::singleton()->forceRollback();

      if (isset($customField)) {
        CustomField::delete(FALSE)->addWhere('id', '=', $customField['id'])->execute();
      }

      CustomGroup::delete(FALSE)->addWhere('id', '=', $customGroup['id'])->execute();

      // There needs to be an open transaction to prevent an error when the CiviCRM test listener tries to rollback a
      // transaction.
      \Civi\Core\Transaction\Manager::singleton()->inc();
      \Civi\Core\Transaction\Manager::singleton()->getFrame()->setRollbackOnly();
    }
  }

  private static function assertCiviFileContentEquals(string $expected, int $fileId): void {
    $file = File::get(FALSE)
      ->addWhere('id', '=', $fileId)
      ->execute()
      ->single();

    $filePath = \CRM_Core_Config::singleton()->customFileUploadDir . $file['uri'];
    static::assertFileExists($filePath);
    static::assertSame($expected, file_get_contents($filePath));
  }

}
