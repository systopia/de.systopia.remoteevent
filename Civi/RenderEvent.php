<?php
/*-------------------------------------------------------+
| SYSTOPIA Remote Event Extension                        |
| Copyright (C) 2021 SYSTOPIA                            |
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

declare(strict_types = 1);

namespace Civi;

use Symfony\Contracts\EventDispatcher\Event;

/**
 * Class RenderEvent
 *
 * @package Civi\RemoteEvent\Event
 *
 * Render smarty snippets
 */
class RenderEvent extends Event {

  public const NAME = 'civi.remoteevent.render';

  /**
   * The full path to the template.
   */
  protected ?string $current_template_file;

  /**
   * The full path to the original template.
   */
  protected ?string $original_template_file;

  /**
   * @phpstan-var array<mixed>
   * The list of smarty variables to be passed to the renderer.
   */
  protected array $smarty_variables;

  /**
   * The render context.
   */
  protected string $context;

  /**
   * The trim mode, see below.
   */
  protected string $trim_mode;

  /**
   * @param ?string $template_path
   * @param array<mixed> $smarty_variables
   * @param string $context
   * @param string $trim_mode
   */
  protected function __construct(?string $template_path, array $smarty_variables, string $context, string $trim_mode) {
    $this->current_template_file = $template_path;
    $this->original_template_file = $template_path;
    $this->smarty_variables = $smarty_variables;
    $this->context = $context;
    $this->trim_mode = $trim_mode;
  }

  /**
   * Utility function: render the given template
   *
   * @param string|null $template_path
   *   full file path to the (intended) template snippet
   *
   * @param array<mixed> $smarty_variables
   *   the variables passed to smarty
   *
   * @param string $context
   *    a string identifying the context of the renderer
   *
   * @param string $trim_mode
   *    should the string be trimmed? See options above
   *
   * @return string|null
   *   rendered
   */
  public static function renderTemplate($template_path, $smarty_variables, $context, $trim_mode = 'none') {
    // Trigger the event.
    $render_event = new RenderEvent($template_path, $smarty_variables, $context, $trim_mode);
    \Civi::dispatcher()->dispatch(RenderEvent::NAME, $render_event);

    // Load the template.
    static $template_cache = [];
    $template_file = $render_event->getTemplateFile();
    if (NULL === $template_file) {
      // An empty template file cannot be rendered.
      return NULL;
    }
    if (!isset($template_cache[$template_file])) {
      $template_cache[$template_file] = 'string:' . file_get_contents($template_file);
    }
    $template = $template_cache[$template_file];

    // Render the template.
    $rendered_text = \CRM_Utils_String::parseOneOffStringThroughSmarty($template, $render_event->getVars());

    // Clean the output.
    $trim_mode = $render_event->getTrimMode();
    switch ($trim_mode) {
      case 'trim':
        $rendered_text = trim($rendered_text);
        break;

      case 'meta-trim':
        // trim, and strip literal '\r' and '\n' strings from the start and end
        $rendered_text = preg_replace('/^( |\\r|\\n|\t|\n|\r)*/', '', $rendered_text);
        $rendered_text = preg_replace('/( |\\r|\\n|\t|\n|\r)*$/', '', $rendered_text);
        break;

      case 'none':
        // do nothing
        break;

      default:
        \Civi::log()->debug("RenderEvent: Unknown trim mode '{$trim_mode}' for context '{$context}'. Ignored.");
        break;
    }

    return $rendered_text;
  }

  /**
   * Get the template file to be rendered
   *
   * @return ?string
   */
  public function getTemplateFile() {
    return $this->current_template_file;
  }

  /**
   * Get the smarty variables,
   *  can be edited in place
   *
   * @return array<mixed>
   */
  public function &getVars() {
    return $this->smarty_variables;
  }

  /**
   * Get trim mode
   *
   * @return string
   */
  public function getTrimMode() {
    return $this->trim_mode;
  }

  /**
   * Set trim mode
   *
   * @param string $trim_mode
   *    should the string be trimmed? Options are:
   *       none: no trimming
   *       trim: standard trim() function
   */
  public function setTrimMode($trim_mode): void {
    $this->trim_mode = $trim_mode;
  }

  /**
   * Set the template file to be rendered
   *
   * @return string
   *   the new (full) file path to the template
   */
  public function setTemplateFile(string $file_path) {
    return $this->current_template_file = $file_path;
  }

  /**
   * Get the template file to be rendered
   *
   * @return string
   */
  public function getOriginalTemplateFile() {
    return $this->original_template_file;
  }

  /**
   * Set/override a smarty variable
   *
   * @param string $key
   *   the variable key/name
   *
   * @param mixed $value
   *   the value
   */
  public function setVar($key, $value): void {
    $this->smarty_variables[$key] = $value;
  }

  /**
   * Get render context
   *
   * @return string
   */
  public function getContext() {
    return $this->context;
  }

}
