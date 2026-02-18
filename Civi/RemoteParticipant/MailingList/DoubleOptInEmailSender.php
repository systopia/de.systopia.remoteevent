<?php
/*
 * Copyright (C) 2025 SYSTOPIA GmbH
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

namespace Civi\RemoteParticipant\MailingList;

use Civi\Api4\Email;
use Civi\Token\TokenProcessor;

class DoubleOptInEmailSender {

  /**
   * @throws \CRM_Core_Exception
   */
  public function sendEmail(int $contactId, int $groupId, string $token, string $subject, string $text): void {
    if ($subject === '' || $text === '') {
      return;
    }

    /** @var string $url */
    $url = \Civi::settings()->get('remote_event_mailing_list_subscription_confirm_link') ?? '';
    if ($url === '') {
      return;
    }

    $toEmail = Email::get(FALSE)
      ->addSelect('email')
      ->addWhere('contact_id', '=', $contactId)
      ->addOrderBy('is_primary', 'DESC')
      ->setLimit(1)
      ->execute()
      ->first()['email'] ?? NULL;
    if ($toEmail === NULL) {
      return;
    }

    /** @var string $domainEmailName */
    /** @var string $domainEmailAddress */
    [$domainEmailName, $domainEmailAddress] = \CRM_Core_BAO_Domain::getNameAndEmail();

    $params = [
      'from' => "\"{$domainEmailName}\" <{$domainEmailAddress}>",
      'toEmail' => $toEmail,
      'replyTo' => \CRM_Core_BAO_Domain::getNoReplyEmailAddress(),
    ];

    $url = str_replace('{token}', $token, $url);
    $text = str_replace('{subscribe.url}', $url, $text);

    $tokenProcessor = new TokenProcessor(\Civi::dispatcher(), [
      'controller' => __CLASS__,
      'schema' => ['contactId', 'groupId'],
    ]);

    $tokenProcessor->addMessage('body', $text, 'text/html');
    $tokenProcessor->addMessage('subject', $subject, 'text/plain');
    $tokenProcessor->addRow([
      'contactId' => $contactId,
      'groupId' => $groupId,
    ]);
    $tokenProcessor->evaluate();

    $params['html'] = $tokenProcessor->getRow(0)->render('body');
    $params['subject'] = $tokenProcessor->getRow(0)->render('subject');
    \CRM_Utils_Mail::send($params);
  }

}
