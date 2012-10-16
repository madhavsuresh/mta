<?php

abstract class Script
{
    abstract function getFormHTML();
    function getFormScripts(){}
    abstract function getName();
    abstract function getDescription();
    abstract function executeAndGetResult();
    function hasParams() { return true; }
}

