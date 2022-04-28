<?php

declare(strict_types=1);

namespace Giann\Trunk;

use ArrayAccess;
use Countable;
use InvalidArgumentException;
use ReflectionProperty;

/**
 * @implements ArrayAccess<string|int, mixed>
 */
class Trunk implements ArrayAccess, Countable
{
    public function __construct(
        public mixed $data = null,
    ) {
    }

    public function count(): int
    {
        if (is_array($this->data) && !self::is_associative($this->data)) {
            return count($this->data);
        }

        return $this->data !== null ? 1 : 0;
    }

    public function offsetExists(mixed $offset): bool
    {
        if (is_array($this->data) && (is_string($offset) || is_int($offset))) {
            return key_exists($offset, $this->data);
        } else if (is_object($this->data) && is_string($offset)) {
            return property_exists($this->data, $offset);
        }

        return false;
    }

    public function offsetGet(mixed $offset): mixed
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

    public function offsetSet(mixed $offset, mixed $value): void
    {
        if (is_array($this->data)) {
            $this->data[$offset] = $value;
        } else if (is_object($this->data) && is_string($offset) && property_exists($this->data, $offset)) {
            $rp = new ReflectionProperty($this->data, $offset);

            if ($rp->isPublic()) {
                $this->data->{$offset} = $value;
            }
        }

        throw new InvalidArgumentException('Value can\'t be indexed');
    }

    public function offsetUnset(mixed $offset): void
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
     * @return Trunk[]|null
     */
    public function listRaw(): ?array
    {
        return is_array($this->data) && !self::is_associative($this->data)
            ? $this->data
            : null;
    }

    /**
     * @return Trunk[]
     */
    public function listRawValue(): array
    {
        return $this->listRaw() ?? [];
    }

    /**
     * @param string $type
     * @param callable|null $builder
     * @return mixed[]|null
     */
    public function listOfClass(string $type, ?callable $builder = null): ?array
    {
        if (is_array($this->data) && !self::is_associative($this->data)) {
            try {
                return array_map(
                    function ($el) use ($type, $builder) {
                        if (is_object($el) && (get_class($el) === $type || is_subclass_of($el, $type))) {
                            return $el;
                        }

                        if ($builder !== null) {
                            $built = $builder($el);

                            if ($built !== null && (get_class($built) === $type || is_subclass_of($built, $type))) {
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
     * @param string $type
     * @param callable|null $builder
     * @return mixed[]
     */
    public function listOfClassValue(string $type, ?callable $builder = null): array
    {
        return $this->listOfClass($type, $builder) ?? [];
    }

    /**
     * @return Trunk[]|null
     */
    public function map(): ?array
    {
        return is_array($this->data) && self::is_associative($this->data)
            ? array_map(fn ($value) => new Trunk($value), $this->data)
            : null;
    }

    /**
     * @return Trunk[]
     */
    public function mapValue(): array
    {
        return $this->map() ?? [];
    }

    /**
     * @return Trunk[]|null
     */
    public function mapRaw(): ?array
    {
        return is_array($this->data) && !self::is_associative($this->data)
            ? $this->data
            : null;
    }

    /**
     * @return Trunk[]
     */
    public function mapRawValue(): array
    {
        return $this->mapRaw() ?? [];
    }

    /**
     * @param string $type
     * @param callable|null $builder
     * @return mixed[]|null
     */
    public function mapOfClass(string $type, ?callable $builder = null): ?array
    {
        if (is_array($this->data) && self::is_associative($this->data)) {
            try {
                return array_map(
                    function ($el) use ($type, $builder) {
                        if (is_object($el) && (get_class($el) === $type || is_subclass_of($el, $type))) {
                            return $el;
                        }

                        if ($builder !== null) {
                            $built = $builder($el);

                            if ($built !== null && (get_class($built) === $type || is_subclass_of($built, $type))) {
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
     * @param string $type
     * @param callable|null $builder
     * @return mixed[]
     */
    public function mapOfClassValue(string $type, ?callable $builder = null): array
    {
        return $this->mapOfClass($type, $builder) ?? [];
    }

    public function ofClass(string $type, ?callable $builder = null): mixed
    {
        if (is_object($this->data) && (get_class($this->data) === $type || is_subclass_of($this->data, $type))) {
            return $this->data;
        }

        if ($builder !== null) {
            $built = $builder($this->data);

            if ($built !== null && (get_class($built) === $type || is_subclass_of($built, $type))) {
                return $built;
            }
        }

        return null;
    }

    /**
     * @param string $type
     * @param callable|null $builder
     * @param bool|string|integer|float|object|mixed[]|array<string, mixed> $default
     * @return bool|string|integer|float|object|mixed[]|array<string, mixed>
     */
    public function ofClassValue(string $type, bool|string|int|float|object|array $default, ?callable $builder = null): bool|string|int|float|object|array
    {
        /** @phpstan-ignore-next-line */
        return $this->ofClass($type, $builder) ?? $default;
    }

    private static function is_associative(mixed $array): bool
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
