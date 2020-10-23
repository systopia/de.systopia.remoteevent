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

CREATE TABLE IF NOT EXISTS `civicrm_session` (
     `id`               int unsigned NOT NULL AUTO_INCREMENT  COMMENT 'Unique Session ID',
     `event_id`         int unsigned                          COMMENT 'FK to Event',
     `title`            varchar(255)                          COMMENT 'Session Title',
     `is_active`        tinyint        DEFAULT 0              COMMENT 'Is this Session enabled or disabled/cancelled?',
     `start_date`       datetime                              COMMENT 'Date and time that sessions starts.',
     `end_date`         datetime                              COMMENT 'Date and time that session ends.',
     `slot_id`          int unsigned   DEFAULT NULL           COMMENT 'All sessions _can_ be assigned to slots. A participant can only register for one session per slot',
     `category_id`      int unsigned   DEFAULT 0              COMMENT 'Session category',
     `type_id`          int unsigned   DEFAULT 0              COMMENT 'Session type',
     `description`      text                                  COMMENT 'Full description of the session. Text and html allowed. Displayed on built-in Event Information screens.',
     `max_participants` int unsigned   DEFAULT NULL           COMMENT 'Maximum number of registered participants to allow.',
     `location`         text                                  COMMENT 'Location information for this session',
     `presenter_id`     int unsigned                          COMMENT 'FK to Contact ID',
     `presenter_title`  varchar(127)                          COMMENT 'Presenter Title',
     PRIMARY KEY (`id`),
     INDEX `UI_session_is_active` (is_active),
     CONSTRAINT FK_civicrm_session_event_id     FOREIGN KEY (`event_id`)     REFERENCES `civicrm_event`(`id`)   ON DELETE CASCADE,
     CONSTRAINT FK_civicrm_session_presenter_id FOREIGN KEY (`presenter_id`) REFERENCES `civicrm_contact`(`id`) ON DELETE SET NULL
);
