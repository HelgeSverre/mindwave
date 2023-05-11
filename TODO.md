# TODO

## now

- [] Update all namespaces
- [] Add "dummy brain" (stores everything as an array) for testing

## Next

## later

- [] expand LLM interface, add cohere and some other common llms to dogfood a suitable interface on.
- [] Port the different "agent types" from langchain, but simplify them.
- [] Write Wrapper around Pinecone php package, extract interface
- [] Write Wrapper around Weaviate, extract and refine interface for VectorStore.
- [] Sketch out Brain class that is an abstraction around vectorstores that operate on Knowledge
- [] Sketch out Knoweldge class, it is essentially an abstraction around splitting, embedding and indexing a piece of
  text, with converters for other filetypes (ex: pdf -> text))
- [] Write some simple tests that shows that an agent can answer a simple question based on the content of a text file.
 
