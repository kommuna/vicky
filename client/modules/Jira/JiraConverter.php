<?php
namespace Vicky\client\modules\Jira;

abstract class JiraConverter
{
    abstract public function convert(JiraWebhookData $data);
}