# Server-Sent Events (SSE) Streaming Examples

This document provides JavaScript examples for consuming streaming LLM responses from Mindwave using Server-Sent Events (SSE).

## Table of Contents

- [Basic Vanilla JavaScript](#basic-vanilla-javascript)
- [Alpine.js Reactive Example](#alpinejs-reactive-example)
- [Vue.js Component](#vuejs-component)
- [Blade Component with Livewire](#blade-component-with-livewire)
- [Error Handling](#error-handling)
- [TypeScript Example](#typescript-example)

---

## Basic Vanilla JavaScript

```html
<!DOCTYPE html>
<html>
<head>
    <title>Mindwave Streaming Example</title>
</head>
<body>
    <div>
        <input type="text" id="prompt" placeholder="Enter your prompt..." />
        <button onclick="startStreaming()">Send</button>
    </div>
    <div id="output"></div>

    <script>
        let eventSource = null;

        function startStreaming() {
            const prompt = document.getElementById('prompt').value;
            const output = document.getElementById('output');

            // Clear previous output
            output.textContent = '';

            // Close any existing connection
            if (eventSource) {
                eventSource.close();
            }

            // Create new SSE connection
            eventSource = new EventSource(`/api/chat?prompt=${encodeURIComponent(prompt)}`);

            // Listen for message events
            eventSource.addEventListener('message', (event) => {
                output.textContent += event.data;
            });

            // Listen for done event
            eventSource.addEventListener('done', () => {
                console.log('Stream completed');
                eventSource.close();
                eventSource = null;
            });

            // Handle errors
            eventSource.onerror = (error) => {
                console.error('SSE Error:', error);
                eventSource.close();
                eventSource = null;
            };
        }

        // Clean up on page unload
        window.addEventListener('beforeunload', () => {
            if (eventSource) {
                eventSource.close();
            }
        });
    </script>
</body>
</html>
```

### Laravel Route
```php
use Illuminate\Http\Request;
use Mindwave\Mindwave\Facades\Mindwave;

Route::get('/api/chat', function (Request $request) {
    $prompt = $request->input('prompt');

    return Mindwave::stream($prompt)
        ->toStreamedResponse();
});
```

---

## Alpine.js Reactive Example

```html
<!DOCTYPE html>
<html>
<head>
    <title>Mindwave Streaming with Alpine.js</title>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body>
    <div x-data="chatApp()">
        <div>
            <input type="text" x-model="prompt" @keyup.enter="sendMessage" placeholder="Ask me anything..." />
            <button @click="sendMessage" :disabled="isStreaming">
                <span x-show="!isStreaming">Send</span>
                <span x-show="isStreaming">Streaming...</span>
            </button>
        </div>

        <div class="output" x-html="response"></div>

        <div x-show="error" class="error" x-text="error"></div>
    </div>

    <script>
        function chatApp() {
            return {
                prompt: '',
                response: '',
                error: '',
                isStreaming: false,
                eventSource: null,

                sendMessage() {
                    if (!this.prompt.trim() || this.isStreaming) return;

                    this.response = '';
                    this.error = '';
                    this.isStreaming = true;

                    // Close any existing connection
                    if (this.eventSource) {
                        this.eventSource.close();
                    }

                    const encodedPrompt = encodeURIComponent(this.prompt);
                    this.eventSource = new EventSource(`/api/chat?prompt=${encodedPrompt}`);

                    this.eventSource.addEventListener('message', (event) => {
                        // Escape HTML and convert newlines to <br>
                        const escaped = event.data
                            .replace(/&/g, '&amp;')
                            .replace(/</g, '&lt;')
                            .replace(/>/g, '&gt;')
                            .replace(/\n/g, '<br>');
                        this.response += escaped;
                    });

                    this.eventSource.addEventListener('done', () => {
                        this.isStreaming = false;
                        this.eventSource.close();
                        this.eventSource = null;
                    });

                    this.eventSource.onerror = (error) => {
                        this.error = 'Connection error. Please try again.';
                        this.isStreaming = false;
                        this.eventSource.close();
                        this.eventSource = null;
                    };
                }
            }
        }
    </script>
</body>
</html>
```

---

## Vue.js Component

```vue
<template>
    <div class="chat-component">
        <div class="input-container">
            <input
                v-model="prompt"
                @keyup.enter="sendMessage"
                :disabled="isStreaming"
                placeholder="Enter your prompt..."
            />
            <button @click="sendMessage" :disabled="isStreaming || !prompt.trim()">
                {{ isStreaming ? 'Streaming...' : 'Send' }}
            </button>
        </div>

        <div class="response-container">
            <div v-if="response" class="response" v-html="formattedResponse"></div>
            <div v-if="error" class="error">{{ error }}</div>
        </div>
    </div>
</template>

<script>
export default {
    data() {
        return {
            prompt: '',
            response: '',
            error: '',
            isStreaming: false,
            eventSource: null
        };
    },

    computed: {
        formattedResponse() {
            return this.response
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/\n/g, '<br>');
        }
    },

    methods: {
        async sendMessage() {
            if (!this.prompt.trim() || this.isStreaming) return;

            this.response = '';
            this.error = '';
            this.isStreaming = true;

            this.closeExistingConnection();

            try {
                const encodedPrompt = encodeURIComponent(this.prompt);
                this.eventSource = new EventSource(`/api/chat?prompt=${encodedPrompt}`);

                this.eventSource.addEventListener('message', (event) => {
                    this.response += event.data;
                });

                this.eventSource.addEventListener('done', () => {
                    this.isStreaming = false;
                    this.closeExistingConnection();
                });

                this.eventSource.onerror = (error) => {
                    this.error = 'Failed to connect to the server';
                    this.isStreaming = false;
                    this.closeExistingConnection();
                };
            } catch (error) {
                this.error = 'An unexpected error occurred';
                this.isStreaming = false;
            }
        },

        closeExistingConnection() {
            if (this.eventSource) {
                this.eventSource.close();
                this.eventSource = null;
            }
        }
    },

    beforeUnmount() {
        this.closeExistingConnection();
    }
};
</script>

<style scoped>
.chat-component {
    max-width: 800px;
    margin: 0 auto;
    padding: 20px;
}

.input-container {
    display: flex;
    gap: 10px;
    margin-bottom: 20px;
}

input {
    flex: 1;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 4px;
}

button {
    padding: 10px 20px;
    background: #4CAF50;
    color: white;
    border: none;
    border-radius: 4px;
    cursor: pointer;
}

button:disabled {
    background: #ccc;
    cursor: not-allowed;
}

.response {
    padding: 15px;
    background: #f5f5f5;
    border-radius: 4px;
    white-space: pre-wrap;
    word-wrap: break-word;
}

.error {
    padding: 15px;
    background: #ffebee;
    color: #c62828;
    border-radius: 4px;
}
</style>
```

---

## Blade Component with Livewire

```php
// app/Livewire/ChatStream.php
namespace App\Livewire;

use Livewire\Component;

class ChatStream extends Component
{
    public string $prompt = '';

    public function render()
    {
        return view('livewire.chat-stream');
    }
}
```

```blade
{{-- resources/views/livewire/chat-stream.blade.php --}}
<div x-data="livewireChatStream()">
    <div class="mb-4">
        <input
            type="text"
            wire:model="prompt"
            x-model="prompt"
            @keyup.enter="sendMessage"
            placeholder="Enter your prompt..."
            class="w-full px-4 py-2 border rounded"
        />
        <button
            @click="sendMessage"
            :disabled="isStreaming"
            class="mt-2 px-4 py-2 bg-blue-500 text-white rounded disabled:bg-gray-300"
        >
            <span x-show="!isStreaming">Send</span>
            <span x-show="isStreaming">Streaming...</span>
        </button>
    </div>

    <div x-show="response" class="p-4 bg-gray-100 rounded" x-html="response"></div>
</div>

<script>
function livewireChatStream() {
    return {
        prompt: '',
        response: '',
        isStreaming: false,
        eventSource: null,

        sendMessage() {
            if (!this.prompt.trim() || this.isStreaming) return;

            this.response = '';
            this.isStreaming = true;

            if (this.eventSource) {
                this.eventSource.close();
            }

            const encodedPrompt = encodeURIComponent(this.prompt);
            this.eventSource = new EventSource(`/api/chat?prompt=${encodedPrompt}`);

            this.eventSource.addEventListener('message', (event) => {
                this.response += event.data.replace(/\n/g, '<br>');
            });

            this.eventSource.addEventListener('done', () => {
                this.isStreaming = false;
                this.eventSource.close();
                this.eventSource = null;
            });

            this.eventSource.onerror = () => {
                this.isStreaming = false;
                this.eventSource.close();
                this.eventSource = null;
            };
        }
    }
}
</script>
```

---

## Error Handling

Complete example with robust error handling:

```javascript
class StreamingChat {
    constructor(apiUrl) {
        this.apiUrl = apiUrl;
        this.eventSource = null;
        this.maxRetries = 3;
        this.retryCount = 0;
        this.retryDelay = 1000; // 1 second
    }

    async send(prompt, onMessage, onComplete, onError) {
        this.close();
        this.retryCount = 0;

        const encodedPrompt = encodeURIComponent(prompt);
        this.connect(`${this.apiUrl}?prompt=${encodedPrompt}`, onMessage, onComplete, onError);
    }

    connect(url, onMessage, onComplete, onError) {
        this.eventSource = new EventSource(url);

        this.eventSource.addEventListener('message', (event) => {
            this.retryCount = 0; // Reset retry count on successful message
            onMessage(event.data);
        });

        this.eventSource.addEventListener('done', () => {
            onComplete();
            this.close();
        });

        this.eventSource.onerror = (error) => {
            console.error('Stream error:', error);

            if (this.retryCount < this.maxRetries) {
                this.retryCount++;
                console.log(`Retrying connection (${this.retryCount}/${this.maxRetries})...`);

                this.close();

                setTimeout(() => {
                    this.connect(url, onMessage, onComplete, onError);
                }, this.retryDelay * this.retryCount);
            } else {
                onError(new Error('Max retries exceeded'));
                this.close();
            }
        };
    }

    close() {
        if (this.eventSource) {
            this.eventSource.close();
            this.eventSource = null;
        }
    }
}

// Usage
const chat = new StreamingChat('/api/chat');

chat.send(
    'Tell me a story',
    (message) => {
        console.log('Received:', message);
        document.getElementById('output').textContent += message;
    },
    () => {
        console.log('Stream completed');
    },
    (error) => {
        console.error('Error:', error);
        alert('Failed to stream response: ' + error.message);
    }
);
```

---

## TypeScript Example

```typescript
interface StreamingChatOptions {
    apiUrl: string;
    maxRetries?: number;
    retryDelay?: number;
}

interface StreamCallbacks {
    onMessage: (message: string) => void;
    onComplete: () => void;
    onError: (error: Error) => void;
}

class TypedStreamingChat {
    private apiUrl: string;
    private eventSource: EventSource | null = null;
    private maxRetries: number;
    private retryCount: number = 0;
    private retryDelay: number;

    constructor(options: StreamingChatOptions) {
        this.apiUrl = options.apiUrl;
        this.maxRetries = options.maxRetries ?? 3;
        this.retryDelay = options.retryDelay ?? 1000;
    }

    public async send(prompt: string, callbacks: StreamCallbacks): Promise<void> {
        this.close();
        this.retryCount = 0;

        const encodedPrompt = encodeURIComponent(prompt);
        const url = `${this.apiUrl}?prompt=${encodedPrompt}`;

        this.connect(url, callbacks);
    }

    private connect(url: string, callbacks: StreamCallbacks): void {
        this.eventSource = new EventSource(url);

        this.eventSource.addEventListener('message', (event: MessageEvent) => {
            this.retryCount = 0;
            callbacks.onMessage(event.data);
        });

        this.eventSource.addEventListener('done', () => {
            callbacks.onComplete();
            this.close();
        });

        this.eventSource.onerror = (error: Event) => {
            console.error('Stream error:', error);

            if (this.retryCount < this.maxRetries) {
                this.retryCount++;
                console.log(`Retrying connection (${this.retryCount}/${this.maxRetries})...`);

                this.close();

                setTimeout(() => {
                    this.connect(url, callbacks);
                }, this.retryDelay * this.retryCount);
            } else {
                callbacks.onError(new Error('Max retries exceeded'));
                this.close();
            }
        };
    }

    public close(): void {
        if (this.eventSource) {
            this.eventSource.close();
            this.eventSource = null;
        }
    }
}

// Usage
const chat = new TypedStreamingChat({ apiUrl: '/api/chat' });

chat.send('Tell me a story', {
    onMessage: (message) => {
        console.log('Received:', message);
    },
    onComplete: () => {
        console.log('Stream completed');
    },
    onError: (error) => {
        console.error('Error:', error);
    }
});
```

---

## Best Practices

1. **Always close connections**: Clean up EventSource connections when done or when the component unmounts
2. **Handle errors gracefully**: Implement retry logic and user-friendly error messages
3. **Escape HTML**: Always escape user-generated content before displaying it
4. **Provide feedback**: Show loading states and stream progress to users
5. **Limit concurrent streams**: Prevent users from opening multiple streams simultaneously
6. **Test connection interruptions**: Handle network failures and connection drops
7. **Consider mobile**: SSE works on mobile browsers but may have reliability issues on poor connections
8. **Use HTTPS in production**: EventSource requires HTTPS in production environments
9. **Monitor connection health**: Implement heartbeat or ping mechanisms for long-running streams
10. **Clean up on navigation**: Close streams when users navigate away from the page
