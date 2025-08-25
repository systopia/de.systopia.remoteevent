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

namespace Civi\RemoteParticipant\Event\Util;

use CRM_Remoteevent_Localisation;

class PaymentMethodUtil {

  /**
   * @phpstan-param array{
   *   is_pay_later: bool|int,
   * } $event
   *
   * @phpstan-return array<string, string>
   */
  public static function getPaymentMethodOptions(array $event, ?string $locale = NULL): array {
    $l10n = CRM_Remoteevent_Localisation::getLocalisation($locale);
    $paymentMethods = [];

    if ((bool) $event['is_pay_later']) {
      $paymentMethods['pay_later'] = $event['pay_later_text'] ?? $l10n->ts('Pay Later');
    }

    if (self::civiSepaEnabled()) {
      $paymentMethods['sepa'] = $l10n->ts('SEPA Direct Debit');
    }

    // TODO: Allow more custom payment methods that require sending back data
    //       (instead of processing the payment on the remote environment).

    return $paymentMethods;
  }

  public static function getPaymentMethodFields(array $event, string $paymentMethod, ?string $locale = NULL): array {
    $l10n = CRM_Remoteevent_Localisation::getLocalisation($locale);
    $fields = [];

    switch ($paymentMethod) {
      case 'sepa':
        $fields['payment_method_sepa_account_holder'] = [
          'type' => 'Text',
          'name' => 'payment_method_sepa_account_holder',
          'label' => $l10n->ts('Account Holder'),
          'required' => FALSE,
          'maxlength' => 34,
          'parent' => 'payment_method',
          'weight' => 1,
          'dependencies' => [
            [
              'command' => 'hide',
              'dependee_field' => 'payment_method',
              'dependee_value' => 'sepa',
            ],
          ],
        ];
        $fields['payment_method_sepa_iban'] = [
          'type' => 'Text',
          'name' => 'payment_method_sepa_iban',
          'label' => $l10n->ts('IBAN'),
          'required' => TRUE,
          'maxlength' => 34,
          'parent' => 'payment_method',
          'weight' => 1,
          'dependencies' => [
            [
              'command' => 'hide',
              'dependee_field' => 'payment_method',
              'dependee_value' => 'sepa',
            ],
          ],
        ];
        $fields['payment_method_sepa_bic'] = [
          'type' => 'Text',
          'name' => 'payment_method_sepa_bic',
          'label' => $l10n->ts('BIC'),
          'required' => TRUE,
          'maxlength' => 11,
          'parent' => 'payment_method',
          'weight' => 1,
          'dependencies' => [
            [
              'command' => 'hide',
              'dependee_field' => 'payment_method',
              'dependee_value' => 'sepa',
            ],
          ],
        ];
        $fields['payment_method_sepa_bank_name'] = [
          'type' => 'Text',
          'name' => 'payment_method_sepa_bank_name',
          'label' => $l10n->ts('Bank Name'),
          'required' => FALSE,
          'maxlength' => 64,
          'parent' => 'payment_method',
          'weight' => 1,
          'dependencies' => [
            [
              'command' => 'hide',
              'dependee_field' => 'payment_method',
              'dependee_value' => 'sepa',
            ],
          ],
        ];
        break;
    }

    return $fields;
  }

  public static function civiSepaEnabled(): bool {
    return (bool) \Civi\Api4\Extension::get(TRUE)
      ->addWhere('key', '=', 'org.project60.sepa')
      ->addWhere('status', '=', 'installed')
      ->execute()
      ->count();
  }

}
