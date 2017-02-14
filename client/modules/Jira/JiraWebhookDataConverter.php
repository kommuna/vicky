<?php
namespace Vicky\client\modules\Jira;

abstract class JiraWebhookDataConverter
{
    abstract public function convert(JiraWebhookData $data);
}