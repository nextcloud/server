<?php

namespace Amp;

final class CombinedCancellationToken implements CancellationToken
{
    /** @var array{0: CancellationToken, 1: string}[] */
    private $tokens = [];

    /** @var string */
    private $nextId = "a";

    /** @var callable[] */
    private $callbacks = [];

    /** @var CancelledException|null */
    private $exception;

    public function __construct(CancellationToken ...$tokens)
    {
        foreach ($tokens as $token) {
            $id = $token->subscribe(function (CancelledException $exception) {
                $this->exception = $exception;

                $callbacks = $this->callbacks;
                $this->callbacks = [];

                foreach ($callbacks as $callback) {
                    asyncCall($callback, $this->exception);
                }
            });

            $this->tokens[] = [$token, $id];
        }
    }

    public function __destruct()
    {
        foreach ($this->tokens as list($token, $id)) {
            /** @var CancellationToken $token */
            $token->unsubscribe($id);
        }
    }

    /** @inheritdoc */
    public function subscribe(callable $callback): string
    {
        $id = $this->nextId++;

        if ($this->exception) {
            asyncCall($callback, $this->exception);
        } else {
            $this->callbacks[$id] = $callback;
        }

        return $id;
    }

    /** @inheritdoc */
    public function unsubscribe(string $id)
    {
        unset($this->callbacks[$id]);
    }

    /** @inheritdoc */
    public function isRequested(): bool
    {
        foreach ($this->tokens as list($token)) {
            if ($token->isRequested()) {
                return true;
            }
        }

        return false;
    }

    /** @inheritdoc */
    public function throwIfRequested()
    {
        foreach ($this->tokens as list($token)) {
            $token->throwIfRequested();
        }
    }
}
