# TODO

## Done-ish

- [x] Update all namespaces
- [x] Sketch out Knowledge class, it is essentially an abstraction around splitting, embedding and indexing a piece of
- [x] Separate classes per loader, registry pattern, allows overriding a loader (ex Pdf loader can use Textract OCR
  instead of PDF Parser etc)
- [x] Figure out what vectorstore interface should look like
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
- [x] InMemory (array) vectorstore driver for testing and "throwaway use"
- [x] expand LLM interface, add some other common llms to dogfood a suitable interface on.
- [x] Pinecone vectorstore driver ( index = collection, meta = meta) + tests
- [x] "File" vectorstore driver (Stores everything as JSON file, very stupid and naive solution, for local dev and
  testing)
- [x] Command to generate a Tool class (php artisan mindwave:tool)
- [x] Sketch out Brain class that is an abstraction around vectorstores that operate on Documents
- [x] Write some simple tests that shows that an agent can answer a simple question based on the content of a text file.

## Vectorstore Drivers

- [ ] Weaviate vectorstore driver ( collection = collection, properties = meta) + tests
- [ ] Qdrant vectorstore driver ( collection = collection, properties = meta) + tests
- [ ] Milvus vectorstore driver ( collection = collection, properties = meta) + tests
- [ ] Redis vectorstore driver (https://redis.io/docs/stack/search/indexing_json/)
- [ ] PGVector vectorstore driver

## TODO

- [ ] Document loader for text/csv
- [ ] Document loader for text/xml
- [ ] Document loader for text/calendar (.ical files)
- [ ] Document Loader for Excel file
- [ ] Tool to search your Gmail (provide access token or service account in constructor, make example in demo app on how
  to do that in practice)
- [ ] Laravel Scout-ish feature where you can modify your Model to make it indexable, and toPrompt() method to describe
  text, with converters for other filetypes (ex: pdf -> text))
- [ ] Port the different "agent types" from LangChain, but simplify them.
- [ ] Chain: QA With sources (
  inspiration: https://github.com/hwchase17/langchain/blob/master/langchain/chains/qa_with_sources/stuff_prompt.py)
- [ ] Chain: Related to above ^: QA With Source and Eloquent Model: Combine embedding search, model references and
  retrieve the llm answer along with a model instance as result
- [ ] Add eloquent backend ChatHistoryMemory (migration, model, config file (use mindwave.php) -> chat_history (id,
  role, message, meta, created_at, updated_at))
  the model in natural language or as a CSV
- [ ] Callbacks implemented as Events (base the names
  on [these](https://python.langchain.com/en/latest/modules/callbacks/getting_started.html) )

## Notes & thoughts

- Is a `Brain` a tool, does it make sense to implement it as a tool? (langchain has cconcept of retrievers, which is
  similar to brains)
