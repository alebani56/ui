<?php

namespace atk4\ui\FormLayout;

use atk4\ui\Form;
use atk4\ui\View;

/**
 * Generic Layout for a form.
 */
class Generic extends View
{
    public $form = null;

    public $defaultTemplate = 'formlayout/vertical.html';

    public $label = null;

    public $n = null;

    public $inline = null;

    /**
     * Places field inside a layout somewhere.
     */
    public function addField($field, $args = [])
    {
        if (is_string($args)) {
            $args = ['caption' => $args];
        } elseif (is_array($args) && isset($args[0])) {
            $args['caption'] = $args[0];
            unset($args[0]);
        }

        /*
        if (isset($args[1]) && is_string($args[1])) {
            $args[1] = ['ui'=>['caption'=>$args[1]]];
        }
         */

        if (!$field instanceof \atk4\ui\FormField\Generic) {
            if (is_array($field)) {
                $field = $this->form->fieldFactory(...$field);
            } else {
                $field = $this->form->fieldFactory($field);
            }
        }

        if (isset($args['caption'])) {
            $field->field->ui['caption'] = $args['caption'];
        }

        if (isset($args['width'])) {
            $field->field->ui['width'] = $args['width'];
        }

        return $this->_add($field, ['name'=>$field->short_name]);
    }

    public function addButton(\atk4\ui\Button $button)
    {
        return $this->_add($button);
    }

    /**
     * Create a group with fields.
     */
    public function addHeader($label = null)
    {
        if ($label) {
            $this->add(new View([$label, 'ui'=>'dividing header', 'element'=>'h4']));
        }

        return $this;
    }

    public function addGroup($label = null)
    {
        if (!is_array($label)) {
            $label = ['label'=>$label];
        } elseif (isset($label[0])) {
            $label['label'] = $label[0];
            unset($label[0]);
        }

        $label['form'] = $this->form;

        return $this->add(new self($label));
    }

    public function recursiveRender()
    {
        $field_input = $this->template->cloneRegion('InputField');
        $field_no_label = $this->template->cloneRegion('InputNoLabel');
        $labeled_group = $this->template->cloneRegion('LabeledGroup');
        $no_label_group = $this->template->cloneRegion('NoLabelGroup');

        $this->template->del('Content');

        foreach ($this->elements as $el) {

            // Buttons go under Button section
            if ($el instanceof \atk4\ui\Button) {
                $this->template->appendHTML('Buttons', $el->getHTML());
                continue;
            }

            if ($el instanceof \atk4\ui\FormLayout\Generic) {
                if ($el->label && !$el->inline) {
                    $template = $labeled_group;
                    $template->set('label', $el->label);
                } else {
                    $template = $no_label_group;
                }

                if ($el->n) {
                    $template->set('n', $el->n);
                }

                if ($el->inline) {
                    $template->set('class', 'inline');
                }
                $template->setHTML('Content', $el->getHTML());

                $this->template->appendHTML('Content', $template->render());
                continue;
            }

            // Anything but fields gets inserted directly
            if (!$el instanceof \atk4\ui\FormField\Generic) {
                $this->template->appendHTML('Content', $el->getHTML());
                continue;
            }

            $template = $field_input;
            $label = isset($el->field->ui['caption']) ?
                $el->field->ui['caption'] : ucwords(str_replace('_', ' ', $el->field->short_name));

            // Anything but fields gets inserted directly
            if ($el instanceof \atk4\ui\FormField\Checkbox) {
                $template = $field_no_label;
                $el->template->set('Content', $label);
                /*
                $el->addClass('field');
                $this->template->appendHTML('Fields', '<div class="field">'.$el->getHTML().'</div>');
                continue;
                 */
            }

            if ($this->label && $this->inline) {
                $el->placeholder = $label;
                $label = $this->label;
                $this->label = null;
            } elseif ($this->label || $this->inline) {
                $template = $field_no_label;
                $el->placeholder = $label;
            }

            // Fields get extra pampering
            $template->setHTML('Input', $el->getHTML());
            $template->trySet('label', $label);
            $template->trySet('label_for', $el->id.'_input');

            if (isset($el->field->ui['width'])) {
                $template->set('field_class', $el->field->ui['width'].' wide');
            }

            $this->template->appendHTML('Content', $template->render());
        }

        // Now collect JS from everywhere
        foreach ($this->elements as $el) {
            if ($el->_js_actions) {
                $this->_js_actions = array_merge_recursive($this->_js_actions, $el->_js_actions);
            }
        }
    }
}
