<?php namespace Tobuli\Helpers\Templates;

class TemplateManager
{
    public function loadTemplateBuilder($template)
    {
        $builder = 'Tobuli\Helpers\Templates\Builders\\' . studly_case($template) . 'Template';

        if ( ! class_exists($builder))
            throw new \Exception('Not found template builder for template');

        return new $builder();
    }
}