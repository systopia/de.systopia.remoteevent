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


namespace Civi;

use Symfony\Component\EventDispatcher\Event;

/**
 * Class RenderEvent
 *
 * @package Civi\RemoteEvent\Event
 *
 * Render smarty snippets
 */
class RenderEvent extends Event
{
    /** @var string the full path to the template */
    protected $current_template_file;

    /** @var string the full path to the template originally queried */
    protected $original_template_file;

    /** @var array the list of smarty variables to be passed to the renderer */
    protected $smarty_variables;

    /** @var string the render context */
    protected $context;

    /** @var string the trim mode, see below */
    protected $trim_mode;


    protected function __construct($template_path, $smarty_variables, $context, $trim_mode)
    {
        $this->current_template_file = $template_path;
        $this->original_template_file = $template_path;
        $this->smarty_variables = $smarty_variables;
        $this->context = $context;
        $this->trim_mode = $trim_mode;
    }

    /**
     * Get the template file to be rendered
     *
     * @return string
     */
    public function getTemplateFile()
    {
        return $this->current_template_file;
    }

    /**
     * Set the template file to be rendered
     *
     * @return string
     *   the new (full) file path to the template
     */
    public function setTemplateFile($file_path)
    {
        return $this->current_template_file = $file_path;
    }

    /**
     * Get the template file to be rendered
     *
     * @return string
     */
    public function getOriginalTemplateFile()
    {
        return $this->original_template_file;
    }

    /**
     * Get the smarty variables,
     *  can be edited in place
     *
     * @return array
     */
    public function &getVars()
    {
        return $this->smarty_variables;
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
    public function setVar($key, $value)
    {
        $this->smarty_variables[$key] = $value;
    }

    /**
     * Get render context
     *
     * @return string
     */
    public function getContext()
    {
        return $this->context;
    }

    /**
     * Get trim mode
     *
     * @return string
     */
    public function getTrimMode()
    {
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
    public function setTrimMode($trim_mode)
    {
        $this->trim_mode = $trim_mode;
    }


    /**
     * Utility function: render the given template
     *
     * @param string $template_file
     *   full file path to the (intended) template snippet
     *
     * @param array $smarty_variables
     *   the variables passed to smarty
     *
     * @param string $context
     *    a string identifying the context of the renderer
     *
     * @param string $trim_mode
     *    should the string be trimmed? See options above
     *
     * @return string
     *   rendered
     */
    public static function renderTemplate($template_path, $smarty_variables, $context, $trim_mode = 'none')
    {
        // step 1: trigger the event
        $render_event = new RenderEvent($template_path, $smarty_variables, $context, $trim_mode);
        \Civi::dispatcher()->dispatch('civi.remoteevent.render', $render_event);

        // step 2: load the template
        static $template_cache = [];
        $template_file = $render_event->getTemplateFile();
        if (empty($template_file)) {
            // an empty template file cannot be rendered
            return null;
        }
        if (!isset($template_cache[$template_file])) {
            $template_cache[$template_file] = 'string:' . file_get_contents($template_file);
        }
        $template = $template_cache[$template_file];

        // step 3: render the template
        $smarty = \CRM_Core_Smarty::singleton();
        $new_smarty_vars = $render_event->getVars();
        $previous_smarty_vars = $smarty->get_template_vars();
        $smarty_var_backup = [];
        foreach ($new_smarty_vars as $key => $value) {
            if (isset($previous_smarty_vars[$key])) {
                $smarty_var_backup[$key] = $previous_smarty_vars[$key];
            } else {
                $smarty_var_backup[$key] = null;
            }
            $smarty->assign($key, $value);
        }
        $rendered_text = $smarty->fetch($template);

        // step 4: restore smarty state
        foreach ($new_smarty_vars as $key => $value) {
            $smarty->clear_assign($key);
        }
        foreach ($smarty_var_backup as $key => $value) {
            if ($value === null) {
                $smarty->clear_assign($key);
            } else {
                $smarty->assign($key, $value);
            }
        }

        // step 5: clean the output
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
}
