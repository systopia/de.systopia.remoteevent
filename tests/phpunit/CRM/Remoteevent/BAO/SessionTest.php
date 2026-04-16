<?php
/*
 * Copyright (C) 2026 SYSTOPIA GmbH
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

use Civi\RemoteEvent\AbstractRemoteEventHeadlessTestCase;
use Civi\RemoteEvent\Fixtures\RemoteEventFixture;
use Civi\RemoteEvent\Fixtures\SessionFixture;

/**
 * @covers \CRM_Remoteevent_BAO_Session
 *
 * @group headless
 */
final class CRM_Remoteevent_BAO_SessionTest extends AbstractRemoteEventHeadlessTestCase {

  protected function setUp(): void {
    parent::setUp();
  }

  public function testGetSessions(): void {
    $event1 = RemoteEventFixture::addFixture(['start_date' => '2026-04-16']);
    $event2 = RemoteEventFixture::addFixture(['start_date' => '2026-04-17']);

    $session1_1 = SessionFixture::addFixture($event1['id'], [
      'title' => 'Event 1 Session 1',
      'start_date' => '2026-04-18 12:00:00',
      'end_date' => '2026-04-18 14:00:00',
    ]);
    static::assertSame([
      $session1_1['id'] => [
        'id' => $session1_1['id'],
        'event_id' => $event1['id'],
        'title' => 'Event 1 Session 1',
        'is_active' => TRUE,
        'start_date' => '2026-04-18 12:00:00',
        'end_date' => '2026-04-18 14:00:00',
        'slot_id' => NULL,
        'category_id' => NULL,
        'type_id' => NULL,
        'description' => NULL,
        'max_participants' => 0,
        'location' => NULL,
        'presenter_id' => NULL,
        'presenter_title' => NULL,
        'day' => 3,
      ],
    ], CRM_Remoteevent_BAO_Session::getSessions($event1['id']));

    $session1_2 = SessionFixture::addFixture($event1['id'], [
      'title' => 'Event 1 Session 2',
      'start_date' => '2026-04-16 10:00:00',
      'end_date' => '2026-04-18 14:00:00',
    ]);

    // Cached result is returned.
    static::assertSame([
      $session1_1['id'] => [
        'id' => $session1_1['id'],
        'event_id' => $event1['id'],
        'title' => 'Event 1 Session 1',
        'is_active' => TRUE,
        'start_date' => '2026-04-18 12:00:00',
        'end_date' => '2026-04-18 14:00:00',
        'slot_id' => NULL,
        'category_id' => NULL,
        'type_id' => NULL,
        'description' => NULL,
        'max_participants' => 0,
        'location' => NULL,
        'presenter_id' => NULL,
        'presenter_title' => NULL,
        'day' => 3,
      ],
    ], CRM_Remoteevent_BAO_Session::getSessions($event1['id']));

    static::assertSame([
      $session1_2['id'] => [
        'id' => $session1_2['id'],
        'event_id' => $event1['id'],
        'title' => 'Event 1 Session 2',
        'is_active' => TRUE,
        'start_date' => '2026-04-16 10:00:00',
        'end_date' => '2026-04-18 14:00:00',
        'slot_id' => NULL,
        'category_id' => NULL,
        'type_id' => NULL,
        'description' => NULL,
        'max_participants' => 0,
        'location' => NULL,
        'presenter_id' => NULL,
        'presenter_title' => NULL,
        'day' => 1,
      ],
      $session1_1['id'] => [
        'id' => $session1_1['id'],
        'event_id' => $event1['id'],
        'title' => 'Event 1 Session 1',
        'is_active' => TRUE,
        'start_date' => '2026-04-18 12:00:00',
        'end_date' => '2026-04-18 14:00:00',
        'slot_id' => NULL,
        'category_id' => NULL,
        'type_id' => NULL,
        'description' => NULL,
        'max_participants' => 0,
        'location' => NULL,
        'presenter_id' => NULL,
        'presenter_title' => NULL,
        'day' => 3,
      ],
    ], CRM_Remoteevent_BAO_Session::getSessions($event1['id'], FALSE));

    static::assertSame([], CRM_Remoteevent_BAO_Session::getSessions($event2['id']));
    $session2_1 = SessionFixture::addFixture($event2['id'], [
      'title' => 'Event 2 Session 1',
      'start_date' => '2026-04-18 12:00:00',
      'end_date' => '2026-04-18 14:00:00',
    ]);
    CRM_Remoteevent_BAO_Session::cacheSessions([$event2['id']], []);
    static::assertSame([
      $session2_1['id'] => [
        'id' => $session2_1['id'],
        'event_id' => $event2['id'],
        'title' => 'Event 2 Session 1',
        'is_active' => TRUE,
        'start_date' => '2026-04-18 12:00:00',
        'end_date' => '2026-04-18 14:00:00',
        'slot_id' => NULL,
        'category_id' => NULL,
        'type_id' => NULL,
        'description' => NULL,
        'max_participants' => 0,
        'location' => NULL,
        'presenter_id' => NULL,
        'presenter_title' => NULL,
        'day' => 2,
      ],
    ], CRM_Remoteevent_BAO_Session::getSessions($event2['id']));
  }

}
