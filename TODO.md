# TODO

## now

- [x] Update all namespaces
- [] Sketch out Knowledge class, it is essentially an abstraction around splitting, embedding and indexing a piece of

## Next
- [] Write Wrapper around Pinecone php package, extract interface

## later

- [] Add "dummy brain" (stores everything as an array) for testing
- [] expand LLM interface, add cohere and some other common llms to dogfood a suitable interface on.
- [] Port the different "agent types" from langchain, but simplify them.
- [] Write Wrapper around Weaviate, extract and refine interface for VectorStore.
- [] Sketch out Brain class that is an abstraction around vectorstores that operate on Knowledge
  text, with converters for other filetypes (ex: pdf -> text))
- [] Write some simple tests that shows that an agent can answer a simple question based on the content of a text file.
- [] Replace .txt prompts with blade files.
- [] Separate classes per loader, registry pattern, allows overriding a loader (ex Pdf loader can use Textract OCR
  instead of PDF Parser etc)
