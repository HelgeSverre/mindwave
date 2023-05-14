# TODO

## now

- [x] Update all namespaces
- [x] Sketch out Knowledge class, it is essentially an abstraction around splitting, embedding and indexing a piece of
- [x] Separate classes per loader, registry pattern, allows overriding a loader (ex Pdf loader can use Textract OCR
  instead of PDF Parser etc)

## Next

- [] Figure out what vectorstore interface should look like
    - We need these features:
        - Create an index of a certain dimension
        - Delete an index??
        - Insert a single vector into a "collection" and specify an id (with metadata)
        - Upsert a single vector into a "collection" and specify an id (with metadata)
        - Insert multiple vectors into a "collection" and specify an id (with metadata)
        - Upsert multiple vectors into a "collection" and specify an id (with metadata)
        - Need class to represent a single result from vectorstore (aka Result), contains:
            - score (float)
            - id (string)
            - meta (array)
            - vector (EmbeddingVector)
- [] InMemory (array) vectorstore driver for testing and "throwaway use"
- [] Pinecone vectorstore driver ( index = collection, meta = meta)
- [] Weaviate vectorstore driver ( collection = collection, properties = meta)

## later

- [] Sketch out Brain class that is an abstraction around vectorstores that operate on Knowledge
- [] PGVector vectorstore driver
- [] Add "dummy brain" (stores everything as an array) for testing
- [] expand LLM interface, add cohere and some other common llms to dogfood a suitable interface on.
- [] Port the different "agent types" from langchain, but simplify them.
  text, with converters for other filetypes (ex: pdf -> text))
- [] Write some simple tests that shows that an agent can answer a simple question based on the content of a text file.
- [] Replace .txt prompts with blade files.

