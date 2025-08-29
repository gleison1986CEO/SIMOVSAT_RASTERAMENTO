<?php
namespace App\Policies\Action;

class ActionPolicyManager
{
    protected $policyMap;

    public function policyFor($action)
    {
        $className = '\App\Policies\Action\\'.studly_case($action).'Policy';

        if (! class_exists($className)) {
            throw new \Exception("Action \"{$action}\" class \"{$className}\" not found");
        }

        return new $className();
    }
}