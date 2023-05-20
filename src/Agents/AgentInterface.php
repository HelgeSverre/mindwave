<?php

namespace Mindwave\Mindwave\Agents;

use Mindwave\Mindwave\Agents\Actions\AgentAction;
use Mindwave\Mindwave\Agents\Actions\AgentFinish;

interface AgentInterface
{
    public function handleUserInput($input, $history): AgentAction|AgentFinish;
}
