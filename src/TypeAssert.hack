/*
 *  Copyright (c) 2015-present, Facebook, Inc.
 *  All rights reserved.
 *
 *  This source code is licensed under the MIT license found in the
 *  LICENSE file in the root directory of this source tree.
 *
 */

// Special-casing minimal reimplementation to avoid the dependency
namespace Facebook\AutoloadMap\__Private\TypeAssert;

function is_string(mixed $value, string $field): string {
  invariant($value is string, '%s should be a string', $field);
  return $value;
}

function is_nullable_string(mixed $value, string $field): ?string {
  if ($value === null) {
    return null;
  }
  invariant($value is string, '%s should be a ?string', $field);
  return $value;
}

function is_nullable_bool(mixed $value, string $field): ?bool {
  if ($value === null) {
    return null;
  }
  invariant($value is bool, '%s should be a ?bool', $field);
  return $value;
}

function is_array_of_strings(mixed $value, string $field): array<string> {
  invariant(\is_array($value), '%s should be an array<string>', $field);
  $out = varray[];
  foreach ($value as $it) {
    invariant($it is string, '%s should be an array<string>', $field);
    $out[] = $it;
  }
  return $out;
}

function is_nullable_array_of_strings(
  mixed $value,
  string $field,
): ?array<string> {
  if ($value === null) {
    return null;
  }

  invariant(\is_array($value), '%s should be an ?array<string>', $field);
  $out = varray[];
  foreach ($value as $it) {
    invariant($it is string, '%s should be an ?array<string>', $field);
    $out[] = $it;
  }
  return $out;
}

/* HH_IGNORE_ERROR[2053] enum usage */
function is_nullable_enum<Tval as arraykey, T as \HH\BuiltinEnum<Tval>>(
  classname<T> $what,
  mixed $value,
  string $field,
): ?Tval {
  if ($value === null) {
    return null;
  }
  $value = $what::coerce($value);
  invariant($value !== null, '%s should be a %s value', $field, $what);
  return $value;
}

function is_array_of_shapes_with_name_field(
  mixed $value,
  string $field,
): array<shape('name' => string)> {
  $msg = $field.'should be an array<shape(\'name\' => string)>';
  invariant(\is_array($value), '%s', $msg);
  $out = varray[];
  foreach ($value as $it) {
    invariant(\is_array($it), '%s', $msg);
    $name = $it['name'] ?? null;
    invariant($name is string, '%s', $msg);
    $out[] = shape('name' => $name);
  }
  return $out;
}
