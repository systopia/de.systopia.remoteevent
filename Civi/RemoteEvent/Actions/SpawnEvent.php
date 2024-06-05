<?php

namespace Civi\RemoteEvent\Actions;

use Civi\ActionProvider\Action\AbstractAction;
use Civi\ActionProvider\Action\Contact\ContactActionUtils;
use Civi\ActionProvider\ConfigContainer;
use Civi\ActionProvider\Parameter\ParameterBagInterface;
use Civi\ActionProvider\Parameter\SpecificationBag;
use Civi\ActionProvider\Parameter\Specification;
use Civi\ActionProvider\Parameter\OptionGroupSpecification;
use Civi\ActionProvider\Utils\CustomField;

use CRM_ActionProvider_ExtensionUtil as E;
use Dompdf\Exception;

class SpawnEvent extends AbstractAction {

  /**
   * Returns the specification of the configuration options for the actual action.
   */
  public function getConfigurationSpecification(): SpecificationBag {

    return new SpecificationBag([
        new Specification('template_id', 'Integer', E::ts('Event Template ID'), FALSE, NULL, NULL, NULL, FALSE),
    ]);
  }

  /**
   * @inheritDoc
   */
  public function getParameterSpecification(): SpecificationBag{
    $specs = new SpecificationBag(array(
      /**
       * The parameters given to the Specification object are:
       * @param string $name
       * @param string $dataType
       * @param string $title
       * @param bool $required
       * @param mixed $defaultValue
       * @param string|NULL $fkEntity
       * @param array $options
       * @param bool $multiple
       */
      new Specification('template_id', 'Integer', E::ts('Event Template ID'), FALSE, NULL, NULL, NULL, FALSE),
      new Specification('event_id', 'Integer', E::ts('Event ID'), FALSE, NULL, NULL, NULL, FALSE),
      new OptionGroupSpecification('event_type', 'event_type', E::ts('Event Type'), FALSE),
      new Specification('title', 'String', E::ts('Title'), TRUE, NULL, NULL, NULL, FALSE),
      new Specification('description', 'String', E::ts('Description'), FALSE, NULL, NULL, NULL, FALSE),
      new Specification('summary', 'String', E::ts('Summary'), FALSE, NULL, NULL, NULL, FALSE),
      new Specification('start_date', 'Timestamp', E::ts('Start Date'), TRUE, NULL, NULL, NULL, FALSE),
      new Specification('end_date', 'Timestamp', E::ts('End Date'), FALSE, NULL, NULL, NULL, FALSE),
      new Specification('is_active', 'Boolean', E::ts('Is Active'), FALSE, 1, NULL, NULL, FALSE),
      new Specification('is_public', 'Boolean', E::ts('Is Public'), FALSE, 0, NULL, NULL, FALSE),
      new Specification('max_participants', 'Integer', E::ts('Max. Participants'), FALSE, NULL),
      new Specification('event_full_text', 'String', E::ts('Text when event is full'), FALSE, NULL),
      new Specification('waitlist_text', 'String', E::ts('Waitlist Text'), FALSE, NULL),
    ));

    $config = ConfigContainer::getInstance();
    $customGroups = $config->getCustomGroupsForEntity('Event');
    foreach ($customGroups as $customGroup) {
      if (!empty($customGroup['is_active'])) {
        $specs->addSpecification(CustomField::getSpecForCustomGroup($customGroup['id'], $customGroup['name'], $customGroup['title']));
      }
    }

    return $specs;
  }

  /**
   * Returns the specification of the output parameters of this action.
   *
   * This function could be overridden by child classes.
   */
  public function getOutputSpecification(): SpecificationBag {
    return new SpecificationBag([
      new Specification('id', 'Integer', E::ts('Event ID')),
    ]);
  }

  /**
   * Run the action
   *
   * @param ParameterInterface $parameters
   *   The parameters to this action.
   * @param ParameterBagInterface $output
   *   The parameters this action can send back
   */
  protected function doAction(ParameterBagInterface $parameters, ParameterBagInterface $output): void {
    // Get the contact and the event.
    $apiParams = CustomField::getCustomFieldsApiParameter($parameters, $this->getParameterSpecification());

    if ($this->configuration->doesParameterExists('template_id')) {
      $apiParams['template_id'] = $this->configuration->getParameter('template_id');
    } elseif ($parameters->doesParameterExists('template_id')) {
      $apiParams['template_id'] = $parameters->getParameter('template_id');
    }
    if ($parameters->doesParameterExists('event_id')) {
      $apiParams['id'] = $parameters->getParameter('event_id');
    }
    $apiParams['title'] = $parameters->getParameter('title');
    if ($parameters->doesParameterExists('description')) {
      $apiParams['description'] = $parameters->getParameter('description');
    }
    if ($parameters->doesParameterExists('summary')) {
      $apiParams['summary'] = $parameters->getParameter('summary');
    }
    $apiParams['start_date'] = $parameters->getParameter('start_date');
    if ($parameters->doesParameterExists('end_date')) {
      $apiParams['end_date'] = $parameters->getParameter('end_date');
    }

    if ($parameters->doesParameterExists('event_type')) {
      $apiParams['event_type_id'] = $parameters->getParameter('event_type');
    }
    if ($parameters->doesParameterExists('is_active')) {
      $apiParams['is_active'] = $parameters->getParameter('is_active');
    }
    if ($parameters->doesParameterExists('is_public')) {
      $apiParams['is_public'] = $parameters->getParameter('is_public');
    }
    if ($parameters->doesParameterExists('max_participants')) {
      $apiParams['max_participants'] = $parameters->getParameter('max_participants');
    }
    if ($parameters->doesParameterExists('event_full_text')) {
      $apiParams['event_full_text'] = $parameters->getParameter('event_full_text');
    }
    if ($parameters->doesParameterExists('waitlist_text')) {
      $apiParams['waitlist_text'] = $parameters->getParameter('waitlist_text');
    }

    // Create or Update the event through an API call.
    try {
      $result = civicrm_api3('RemoteEvent', 'spawn', $apiParams);
      $output->setParameter('id', $result['id']);
    } catch (Exception $e) {
      throw new ExecutionException(E::ts('Could not update or create an event.'), $e->getCode(), $e);
    }
  }

}
