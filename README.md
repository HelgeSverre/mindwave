![Mindwave](./assets/header.png)

# Mindwave: Building AI chatbots, agents, and document Q&A in Laravel made easy.

## What is Mindwave?

Mindwave is a Laravel package that lets you easily build AI-powered chatbots, agents, and document question and
answering (Q&A) functionality into your application.

With Mindwave, you can incorporate the power of OpenAI's state-of-the-art language models, Pinecone's vector search
capabilities and your own custom "tools" to create intelligent software applications.

## Example

![Code Example](./assets/code.png)

## Use Cases

- 💬 **Chatbots**: Building AI-powered chatbots to provide support to customers.
- 🤖 **Agents**: Developing intelligent agents to automate tasks within an application.
- ❓ **Document Q&A**: Creating document question and answering (Q&A) systems to extract insights from text.

## Technical

- Mindwave makes it easy to generate embeddings for many types of documents (text, pdf, html, csv, json, etc), store
  those embeddings in a Vector database.
- Using pre-made prompts you can instruct an Agent to run custom "tools" that can perform an action in your codebase,
  lookup specific information from an external source, search your vector database for semantically similar information
  and use the result of that action to generate an answer.

## Support

Mindwave is "driver" oriented, this means you can swap out the parts to suite your needs and use-cases.

### Vector databases

| Name     | Supported?    |
|----------|---------------|
| Pinecone | Yes           |
| Weaviate | No (planned)  |
| pgvector | No  (planned) |

### LLMs

| Name               | Supported?        |
|--------------------|-------------------|
| OpenAI Chat models | Yes (Recommended) |
| OpenAI Completion  | Yes               |
| Cohere AI          | No (planned)      |

### Embeddings

| Name                | Supported?        |
|---------------------|-------------------|
| OpenAI text-ada-002 | Yes (Recommended) |
| TODO #1             | No                |
| TODO #2             | No                |
| TODO #3             | No                |

- Mindwave provides an easy-to-use API for integrating OpenAI's GPT models and Pinecone's vector search capabilities
  into
  your Laravel app.
- Mindwave uses Pinecone's vector search to find similar documents, which can be useful for document Q&A and other
  natural language processing tasks.
  Mindwave supports Laravel version 10.x or higher.
  Getting started with Mindwave is easy - just install the package and start building intelligent software applications
  with AI-powered chatbots, agents, and document Q&A. Try it today and experience the power of Mindwave for yourself!
  Sure, here's a "Known Limitations" section for the Mindwave Laravel package's readme:

## Known Limitations

While Mindwave offers powerful AI capabilities, there are some limitations to keep in mind:

| Limitation      | Description                                                                                                                     |
|-----------------|---------------------------------------------------------------------------------------------------------------------------------|
| Hallucination   | Large language models (LLMs) like OpenAI's GPT models can occasionally produce nonsensical responses or "hallucinate".          |
| English-Centric | While you can configure Mindwave to respond in different languages, it may not always be accurate or natural-sounding.          |
| Context Length  | OpenAI's GPT models have a hard limit on the length of the input context they can process, which varies depending on the model. |

Here are the current context length limits for some of the OpenAI models supported by Mindwave:

| Model ID         | Max Tokens |
|------------------|------------|
| text-davinci-003 | 4,097      |
| gpt-3.5-turbo    | 4,096      |
| gpt-4            | 4,096      |

It's important to keep these limitations in mind when building applications with Mindwave, and to test and evaluate the
results carefully to ensure the desired outcomes are being achieved.
