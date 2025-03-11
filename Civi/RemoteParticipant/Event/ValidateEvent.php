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


namespace Civi\RemoteParticipant\Event;
use Civi\RemoteEvent;

/**
 * Class ValidateEvent
 *
 * @package Civi\RemoteParticipant\Event
 *
 * This event will be triggered at the beginning of the
 *  RemoteParticipant.validate API call, so the search parameters can be manipulated
 */
class ValidateEvent extends RemoteEvent
{

    public const NAME = 'civi.remoteevent.registration.validate';

    /** @var array holds the original RemoteParticipant.validate submission */
    protected $submission;

    public function __construct($submission_data, $error_list = [])
    {
        $this->submission  = $submission_data;
        $this->error_list = $error_list;
        $this->token_usages = ['invite', 'update'];
    }

    /**
     * Add an error to the given field
     *
     * @param string $field_name
     *   field name
     *
     * @param string $error
     *   error message to be displayed to the user
     */
    public function addValidationError($field_name, $error)
    {
        // just pass to the underlying error system
        $this->addError($error, $field_name);
    }

    /**
     * Modify Validation Errors
     *
     * @return array
     *  $validation error list (reference!)
     */
    public function &modifyValidationErrors()
    {
        // just pass to the underlying error system
        return $this->error_list;
    }

    /**
     * Get the complete submission
     *
     * @return array
     *   submission data
     */
    public function getSubmission()
    {
        return $this->submission;
    }

    /**
     * Get the parameters of the original query
     *
     * @return array
     *   parameters of the query
     */
    public function getQueryParameters()
    {
        return $this->submission;
    }
}
