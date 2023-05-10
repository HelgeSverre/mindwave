# Table of Contents

- Introduction
- Concepts
    - Brain
    - Knowledge
    - Agent
    - Tools
    - Chat History
- Usage
    - Installation
    - Configuration
        - Tools
            - Bundled Tools
                - EloquentQueryTool
                - GoogleSearchTool (SerpApi)
                - SimpleWebRequestTool (url -> text)
            - Creating custom tools
    - Brain
        - Consuming Knowledge
        - Retrieving knowledge
    - Chat History
        - Remembering previous conversations
        - Persisting conversation history
            - Storing history in Eloquent
            - Storing history in Session
            - Storing history in LocalStorage
        - Problem: Limited context length
            - Solution 1: Sliding memory window
            - Solution 2: Recursive summarization


- Examples
    - Building a Q&A Chatbot from a PDF
    - Building a Q&A Chatbot from a website
    - Building a meeting scheduler assistant using EloquentQueryTool
    - Building a meeting scheduler assistant using Custom tool Google Calendar
    - Connecting Mindwave to your own Email account.
