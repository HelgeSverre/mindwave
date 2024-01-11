## How agents work.


## Components

- Task
- Agent
  - AgentExecutor
  - AgentAction
- Crew
  - CrewExecutor
- AgentTools 
  - DelegateTask
  - AskQuestion


- IntermediateSteps
  - array of [AgentAction and  Observation]
- AgentScratchpad
- Memory


### Crew 

Crew is a dumy wrapper around this loop:

has list of :

- tasks
- agents
- "config" (ignore, python bs boilerplate)


```
task_output = None
    for task in self.tasks:
        
        if task.agent.allow_delegation 
            give agent agent tools (ability to delegate and ask questions to other agents
        
        task_output = task.execute(task_output)
        self._log(
            "debug", f"\n\n[{task.agent.role}] Task output: {task_output}\n\n"
        )
return task_output
```

### Task

thin wrapper of description and tool list, has an agent assigned.

Executing task will forward call to agent that executes the task,
is also feed the output of the previous task as context, 
agent inherits tools from task



### Crew Agent Executor


```
  agent_args = {
    "input": lambda x: x["input"],
    "tools": lambda x: x["tools"],
    "tool_names": lambda x: x["tool_names"],
    "agent_scratchpad": lambda x: format_log_to_str(x["intermediate_steps"]),
}
    executor_args = {
    "tools": self.tools,
    "verbose": self.verbose,
    "handle_parsing_errors": True,
}

```


created with prompts, injected with agent role, goal and backstory stuff:

*role_playing**
```
You are {role}.
{backstory}

Your personal goal is: {goal}
```

**tools**

```
    TOOLS:
    ------
    You have access to the following tools:
    
    {tools}
    
    To use a tool, please use the exact following format:
    
    ```
    Thought: Do I need to use a tool? Yes
    Action: the action to take, should be one of [{tool_names}], just the name.
    Action Input: the input to the action
    Observation: the result of the action
    ```
    
    When you have a response for your task, or if you do not need to use a tool, you MUST use the format:
    
    ```
    Thought: Do I need to use a tool? No
    Final Answer: [your response here]
```


**task**

```
Begin! This is VERY important to you, your job depends on it!

Current Task: {input}
```


**Optionally with memory**

```
This is the summary of your work so far:
{chat_history}
```



--- 


created with an "inner agent" (aka llm chain of args -> prompt -> param binding of "Observation: " as stopword) -> output parser 

the inner agent plans the next step to do, intermediate steps are maintained in the inner agent

inner agent plans next step(intermediateSteps, inputs) 
    return -> AgentAction / AgentFinish 


yields if action is AgentFinish

// Can potentially return multiple actions

```
For each action
    if AgentAction is tool
        execute tool
        record observation
    yield ActionStep with AgentAction and Observation
```

Task is now "complete" and we loop around to the Crew "for task in tasks" loop


Fancy foreach loop that executes a task, which setups an agent which executes a chain that parses and handle tool calls, returns the output and 

