<?php

declare(strict_types=1);

namespace Giann\Trunk;

use ArrayAccess;
use Countable;
use InvalidArgumentException;
use ReflectionProperty;
use RuntimeException;

/**
 * @implements ArrayAccess<string|int, mixed>
 */
class Trunk implements ArrayAccess, Countable
{
    /** @var mixed */
    public $data = null;

    /**
     * @param mixed $data
     */
    public function __construct(
        $data = null
    ) {
        $this->data = $data;
    }

    public function count(): int
    {
        if (is_array($this->data) && !self::is_associative($this->data)) {
            return count($this->data);
        }

        return $this->data !== null ? 1 : 0;
    }

    /**
     * @param mixed $offset
     */
    public function offsetExists($offset): bool
    {
        if (is_array($this->data) && (is_string($offset) || is_int($offset))) {
            return key_exists($offset, $this->data);
        } else if (is_object($this->data) && is_string($offset)) {
            return property_exists($this->data, $offset);
        }

        return false;
    }

    /** 
     * @param mixed $offset
     * @return Trunk 
     */
    public function offsetGet($offset): Trunk
    {
        if (is_array($this->data) && (is_string($offset) || is_int($offset))) {
            return new Trunk(
                key_exists($offset, $this->data) ? $this->data[$offset] : null
            );
        } else if (is_object($this->data) && is_string($offset) && property_exists($this->data, $offset)) {
            $rp = new ReflectionProperty($this->data, $offset);

            // TODO: use getters?
            return new Trunk(
                $rp->isPublic() ? $this->data->{$offset} : null
            );
        }

        return new Trunk();
    }

    public function offsetSet($offset, $value): void
    {
        throw new RuntimeException('A Trunk is immutable');
    }

    public function offsetUnset($offset): void
    {
        if (is_array($this->data)) {
            unset($this->data[$offset]);
        }

        throw new InvalidArgumentException('Value can\'t be unset');
    }

    public function string(): ?string
    {
        if (is_string($this->data)) {
            return $this->data;
        }

        return null;
    }

    public function stringValue(): string
    {
        if (is_string($this->data)) {
            return $this->data;
        } else if (is_bool($this->data) || is_numeric($this->data)) {
            return '' . $this->data;
        }

        return '';
    }

    public function int(): ?int
    {
        if (is_int($this->data)) {
            return $this->data;
        }

        return null;
    }

    public function intValue(): int
    {
        if (is_int($this->data)) {
            return $this->data;
        } else if (is_bool($this->data)) {
            return $this->data ? 1 : 0;
        } else if (is_float($this->data)) {
            return (int)$this->data;
        } else if (is_string($this->data)) {
            return is_numeric($this->data) ? (int)$this->data : 0;
        }

        return 0;
    }

    public function bool(): ?bool
    {
        if (is_bool($this->data)) {
            return $this->data;
        }

        return null;
    }

    public function boolValue(): bool
    {
        if (is_bool($this->data)) {
            return $this->data;
        } else if (is_int($this->data) || is_float($this->data)) {
            return $this->data == 1;
        } else if (is_string($this->data)) {
            return in_array(strtolower($this->data), ['true', 'y', 't', 'yes', '1']);
        }

        return false;
    }

    public function float(): ?float
    {
        if (is_float($this->data)) {
            return $this->data;
        }

        return null;
    }

    public function floatValue(): float
    {
        if (is_float($this->data)) {
            return $this->data;
        } else if (is_bool($this->data)) {
            return $this->data ? 1 : 0;
        } else if (is_int($this->data)) {
            return (float)$this->data;
        } else if (is_string($this->data)) {
            return is_numeric($this->data) ? (float)$this->data : 0;
        }

        return 0;
    }

    /**
     * @return array<int|string,Trunk>|null
     */
    public function array(): ?array
    {
        return is_array($this->data)
            ? array_map(fn ($el) => new Trunk($el), $this->data)
            : null;
    }

    /**
     * @return array<int|string,Trunk>
     */
    public function arrayValue(): array
    {
        return $this->array() ?? [];
    }

    /**
     * @return mixed[]|null
     */
    public function arrayRaw(): ?array
    {
        return is_array($this->data)
            ? $this->data
            : null;
    }

    /**
     * @return mixed[]
     */
    public function arrayRawValue(): array
    {
        return $this->arrayRaw() ?? [];
    }

    /**
     * @return Trunk[]|null
     */
    public function list(): ?array
    {
        return is_array($this->data) && !self::is_associative($this->data)
            ? array_map(fn ($el) => new Trunk($el), $this->data)
            : null;
    }

    /**
     * @return Trunk[]
     */
    public function listValue(): array
    {
        return $this->list() ?? [];
    }

    /**
     * @return mixed[]|null
     */
    public function listRaw(): ?array
    {
        return is_array($this->data) && !self::is_associative($this->data)
            ? $this->data
            : null;
    }

    /**
     * @return mixed[]
     */
    public function listRawValue(): array
    {
        return $this->listRaw() ?? [];
    }

    /**
     * @template T of object
     * @param class-string<T> $type
     * @param callable(mixed): ?T|null $builder
     * @return T[]|null
     */
    public function listOfClass(string $type, ?callable $builder = null): ?array
    {
        if (is_array($this->data) && !self::is_associative($this->data)) {
            try {
                return array_map(
                    function ($el) use ($type, $builder) {
                        if (is_object($el) && (get_class($el) === $type || is_subclass_of($el, (string)$type))) {
                            /** @var T */
                            return $el;
                        }

                        if ($builder !== null) {
                            $built = $builder($el);

                            /** @phpstan-ignore-next-line */
                            if ($built !== null && (get_class($built) === $type || is_subclass_of($built, (string)$type))) {
                                return $built;
                            }
                        }

                        throw new InvalidArgumentException('At least one element is not of type `' . $type . '`');
                    },
                    $this->data
                );
            } catch (InvalidArgumentException $e) {
                return null;
            }
        }

        return null;
    }

    /**
     * @template T of object
     * @param class-string<T> $type
     * @param callable(mixed): ?T|null $builder
     * @return T[]
     */
    public function listOfClassValue(string $type, ?callable $builder = null): array
    {
        return $this->listOfClass((string)$type, $builder) ?? [];
    }

    /**
     * @return array<string,Trunk>|null
     */
    public function map(): ?array
    {
        return is_array($this->data) && self::is_associative($this->data)
            ? array_map(fn ($value) => new Trunk($value), $this->data)
            : null;
    }

    /**
     * @return array<string,Trunk>
     */
    public function mapValue(): array
    {
        return $this->map() ?? [];
    }

    /**
     * @return array<string,mixed>|null
     */
    public function mapRaw(): ?array
    {
        return is_array($this->data) && self::is_associative($this->data)
            ? $this->data
            : null;
    }

    /**
     * @return array<string,mixed>
     */
    public function mapRawValue(): array
    {
        return $this->mapRaw() ?? [];
    }

    /**
     * @template T of object
     * @param class-string<T> $type
     * @param callable(mixed): ?T|null $builder
     * @return array<string,T>|null
     */
    public function mapOfClass(string $type, ?callable $builder = null): ?array
    {
        if (is_array($this->data) && self::is_associative($this->data)) {
            try {
                return array_map(
                    function ($el) use ($type, $builder) {
                        if (is_object($el) && (get_class($el) === $type || is_subclass_of($el, (string)$type))) {
                            /** @var T */
                            return $el;
                        }

                        if ($builder !== null) {
                            $built = $builder($el);

                            /** @phpstan-ignore-next-line */
                            if ($built !== null && (get_class($built) === $type || is_subclass_of($built, (string)$type))) {
                                /** @var T */
                                return $built;
                            }
                        }

                        throw new InvalidArgumentException('At least one element is not of type `' . $type . '`');
                    },
                    $this->data
                );
            } catch (InvalidArgumentException $e) {
                return null;
            }
        }

        return null;
    }

    /**
     * @template T of object
     * @param class-string<T> $type
     * @param callable(mixed): ?T|null $builder
     * @return array<string,T>
     */
    public function mapOfClassValue(string $type, ?callable $builder = null): array
    {
        return $this->mapOfClass((string)$type, $builder) ?? [];
    }

    /**
     * @template T of object
     * @param class-string<T> $type
     * @param callable(mixed): ?T|null $builder
     * @return ?T
     */
    public function ofClass(string $type, ?callable $builder = null): ?object
    {
        if (is_object($this->data) && (get_class($this->data) === $type || is_subclass_of($this->data, (string)$type))) {
            /** @var T */
            return $this->data;
        }

        if ($builder !== null) {
            $built = $builder($this->data);

            /** @phpstan-ignore-next-line */
            if ($built !== null && (get_class($built) === $type || is_subclass_of($built, (string)$type))) {
                return $built;
            }
        }

        return null;
    }

    /**
     * @template T of object
     * @param class-string<T> $type
     * @param callable|null $builder
     * @param T $default
     * @return T
     */
    public function ofClassValue(string $type, object $default, ?callable $builder = null): object
    {
        return $this->ofClass((string)$type, $builder) ?? $default;
    }

    /**
     * @param array<int|string,mixed> $array
     * @return boolean
     */
    private static function is_associative(array $array): bool
    {
        if (!is_array($array)) {
            return false;
        }

        if ([] === $array) {
            return false;
        }

        if (array_keys($array) !== range(0, count($array) - 1)) {
            return true;
        }

        // Dealing with a Sequential array
        return false;
    }
}
