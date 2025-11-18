<?php

namespace Mindwave\Mindwave\Context\Contracts;

use Mindwave\Mindwave\Context\ContextCollection;

/**
 * Context Source Interface
 *
 * Defines the contract for all context sources (TNTSearch, VectorStore, etc.)
 */
interface ContextSource
{
    /**
     * Search for relevant context items.
     *
     * @param  string  $query  The search query
     * @param  int  $limit  Maximum number of results to return
     * @return ContextCollection Collection of ContextItems
     */
    public function search(string $query, int $limit = 5): ContextCollection;

    /**
     * Get the source name for identification and tracing.
     *
     * @return string Source identifier (e.g., 'tntsearch-users', 'vectorstore', 'static-faqs')
     */
    public function getName(): string;

    /**
     * Initialize the source (e.g., build indexes, connect to services).
     *
     * Called automatically before search if not already initialized.
     */
    public function initialize(): void;

    /**
     * Clean up resources (e.g., delete temporary indexes, close connections).
     *
     * Should be called when source is no longer needed.
     */
    public function cleanup(): void;
}
