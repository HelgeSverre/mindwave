<?php

namespace Mindwave\Mindwave\PromptComposer\Tokenizer;

use Exception;
use Yethee\Tiktoken\Encoder;
use Yethee\Tiktoken\EncoderProvider;

class TiktokenTokenizer implements TokenizerInterface
{
    private EncoderProvider $provider;

    /** @var array<string, Encoder> */
    private array $encoders = [];

    public function __construct(?EncoderProvider $provider = null)
    {
        $this->provider = $provider ?? new EncoderProvider;
    }

    public function count(string $text, string $model): int
    {
        $encoder = $this->getEncoder($model);

        return count($encoder->encode($text));
    }

    public function encode(string $text, string $model): array
    {
        $encoder = $this->getEncoder($model);

        return $encoder->encode($text);
    }

    public function decode(array $tokens, string $model): string
    {
        $encoder = $this->getEncoder($model);

        return $encoder->decode($tokens);
    }

    public function getContextWindow(string $model): int
    {
        return ModelTokenLimits::getContextWindow($model);
    }

    public function supports(string $model): bool
    {
        try {
            $this->getEncoder($model);

            return true;
        } catch (Exception) {
            return false;
        }
    }

    /**
     * Get or create an encoder for the specified model.
     */
    private function getEncoder(string $model): Encoder
    {
        $encodingName = ModelTokenLimits::getEncoding($model);

        if (! isset($this->encoders[$encodingName])) {
            $this->encoders[$encodingName] = $this->provider->get($encodingName);
        }

        return $this->encoders[$encodingName];
    }
}
