<?php

declare(strict_types=1);

namespace Giann\Trunk;

use ArrayAccess;
use Countable;
use Exception;
use InvalidArgumentException;
use Iterator;
use ReflectionProperty;
use RuntimeException;

abstract class TrunkException extends Exception
{
}

class UnsupportedTypeTrunkException extends TrunkException
{
}

class IndexOutOfBoundsTypeTrunkException extends TrunkException
{
}

class WrongTypeTrunkException extends TrunkException
{
}

class DoesNotExistTrunkException extends TrunkException
{
}

/**
 * @implements ArrayAccess<string|int, mixed>
 * @implements Iterator<string|int, mixed>
 */
class Trunk implements ArrayAccess, Countable, Iterator
{
    /** @var mixed */
    public $data = null;

    /** @var TrunkException|null */
    public $exception = null;

    /** @var array<string,mixed>|null */
    protected $object_vars = null;

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
        if ($this->exception != null) {
            return false;
        }

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
            $associative = self::is_associative($this->data);

            if (!$associative && !is_int($offset)) {
                $this->exception = $this->exception ?? new WrongTypeTrunkException();
            } else if (!$associative && ($offset < 0 || $offset >= count($this->data))) {
                $this->exception = $this->exception ?? new IndexOutOfBoundsTypeTrunkException();
            } else if ($associative && !isset($this->data[$offset])) {
                $this->exception = $this->exception ?? new DoesNotExistTrunkException();
            }

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

        $this->exception = $this->exception ?? new WrongTypeTrunkException();

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

    /**
     * @return Trunk|false
     */
    public function current(): mixed
    {
        if (!is_array($this->data) && !is_object($this->data)) {
            $this->exception = $this->exception ?? new WrongTypeTrunkException();
            return false;
        }

        if (is_array($this->data)) {
            return new Trunk(current($this->data));
        }

        assert(is_object($this->data));

        $this->object_vars = $this->object_vars ?? get_object_vars($this->data);
        return new Trunk(current($this->object_vars));
    }

    /**
     * @return int|string|null
     */
    public function key(): mixed
    {
        if (!is_array($this->data) && !is_object($this->data)) {
            $this->exception = $this->exception ?? new WrongTypeTrunkException();
            return null;
        }

        if (is_array($this->data)) {
            return key($this->data);
        }

        assert(is_object($this->data));

        $this->object_vars = $this->object_vars ?? get_object_vars($this->data);
        return key($this->object_vars);
    }

    public function next(): void
    {
        if (!is_array($this->data) && !is_object($this->data)) {
            $this->exception = $this->exception ?? new WrongTypeTrunkException();
            return;
        }

        if (is_array($this->data)) {
            next($this->data);

            return;
        }

        assert(is_object($this->data));

        $this->object_vars = $this->object_vars ?? get_object_vars($this->data);
        next($this->object_vars);
    }

    public function rewind(): void
    {
        if (!is_array($this->data) && !is_object($this->data)) {
            $this->exception = $this->exception ?? new WrongTypeTrunkException();
            return;
        }

        if (is_array($this->data)) {
            reset($this->data);

            return;
        }

        assert(is_object($this->data));

        $this->object_vars = $this->object_vars ?? get_object_vars($this->data);
        reset($this->object_vars);
    }

    public function valid(): bool
    {
        if (!is_array($this->data) && !is_object($this->data)) {
            $this->exception = $this->exception ?? new WrongTypeTrunkException();
            return false;
        }

        if (is_array($this->data)) {
            return isset($this->data[key($this->data)]);
        }

        assert(is_object($this->data));

        $this->object_vars = $this->object_vars ?? get_object_vars($this->data);
        return isset($this->object_vars[key($this->object_vars)]);
    }

    public function string(): ?string
    {
        if (is_string($this->data)) {
            return $this->data;
        }

        return null;
    }

    /**
     * @param mixed $element
     * @return string
     */
    private static function stringCast($element): string
    {
        if (is_string($element)) {
            return $element;
        } else if (is_bool($element) || is_numeric($element)) {
            return '' . $element;
        }

        return '';
    }

    public function stringValue(): string
    {
        return self::stringCast($this->data);
    }

    public function int(): ?int
    {
        if (is_int($this->data)) {
            return $this->data;
        }

        return null;
    }

    /**
     * @param mixed $element
     * @return int
     */
    private static function intCast($element): int
    {
        if (is_int($element)) {
            return $element;
        } else if (is_bool($element)) {
            return $element ? 1 : 0;
        } else if (is_float($element)) {
            return (int)$element;
        } else if (is_string($element)) {
            return is_numeric($element) ? (int)$element : 0;
        }

        return 0;
    }

    public function intValue(): int
    {
        return self::intCast($this->data);
    }

    public function bool(): ?bool
    {
        if (is_bool($this->data)) {
            return $this->data;
        }

        return null;
    }

    /**
     * @param mixed $element
     * @return boolean
     */
    private static function boolCast($element): bool
    {
        if (is_bool($element)) {
            return $element;
        } else if (is_int($element) || is_float($element)) {
            return $element == 1;
        } else if (is_string($element)) {
            return in_array(strtolower($element), ['true', 'y', 't', 'yes', '1']);
        }

        return false;
    }

    public function boolValue(): bool
    {
        return self::boolCast($this->data);
    }

    public function float(): ?float
    {
        if (is_float($this->data)) {
            return $this->data;
        }

        return null;
    }

    /**
     * @param mixed $element
     * @return float
     */
    private static function floatCast($element): float
    {
        if (is_float($element)) {
            return $element;
        } else if (is_bool($element)) {
            return $element ? 1 : 0;
        } else if (is_int($element)) {
            return (float)$element;
        } else if (is_string($element)) {
            return is_numeric($element) ? (float)$element : 0;
        }

        return 0;
    }

    public function floatValue(): float
    {
        return self::floatCast($this->data);
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
     * @return string[]|null
     */
    public function listOfString(): ?array
    {
        if (
            is_array($this->data)
            && !self::is_associative($this->data)
            && count(array_filter($this->data, fn ($el) => is_string($el))) == count($this->data)
        ) {
            return $this->data;
        }

        return null;
    }

    /**
     * @return string[]
     */
    public function listOfStringValue(): array
    {
        $raw = is_array($this->data) && !self::is_associative($this->data) ? $this->data : [];

        return array_map(fn ($el) => self::stringCast($el), $raw);
    }

    /**
     * @return int[]|null
     */
    public function listOfInt(): ?array
    {
        if (
            is_array($this->data)
            && !self::is_associative($this->data)
            && count(array_filter($this->data, fn ($el) => is_int($el))) == count($this->data)
        ) {
            return $this->data;
        }

        return null;
    }

    /**
     * @return int[]
     */
    public function listOfIntValue(): array
    {
        $raw = is_array($this->data) && !self::is_associative($this->data) ? $this->data : [];

        return array_map(fn ($el) => self::intCast($el), $raw);
    }

    /**
     * @return float[]|null
     */
    public function listOfFloat(): ?array
    {
        if (
            is_array($this->data)
            && !self::is_associative($this->data)
            && count(array_filter($this->data, fn ($el) => is_float($el))) == count($this->data)
        ) {
            return $this->data;
        }

        return null;
    }

    /**
     * @return float[]
     */
    public function listOfFloatValue(): array
    {
        $raw = is_array($this->data) && !self::is_associative($this->data) ? $this->data : [];

        return array_map(fn ($el) => self::floatCast($el), $raw);
    }

    /**
     * @return bool[]|null
     */
    public function listOfBool(): ?array
    {
        if (
            is_array($this->data)
            && !self::is_associative($this->data)
            && count(array_filter($this->data, fn ($el) => is_bool($el))) == count($this->data)
        ) {
            return $this->data;
        }

        return null;
    }

    /**
     * @return bool[]
     */
    public function listOfBoolValue(): array
    {
        $raw = is_array($this->data) && !self::is_associative($this->data) ? $this->data : [];

        return array_map(fn ($el) => self::boolCast($el), $raw);
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
        if (!function_exists('array_is_list')) {
            return !array_is_list($array);
        }

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
