# CONFIG
Config is an inheritance based configuration system, that enables multiple
configurations to be loaded in a cascading manner.

## Installation
Install the autoloader:

    composer.phar install

TODO: add the composer.json snippet that developers should add to fetch this package.

## Syntax

    [;|#|//] <key>[, <type>] = <value>[, <value>]

## Example

    # Write hosts:
    db.write.host = 10.0.0.1
    db.write.port = 3306

    # Read hosts:
    db.read.host, string[] = 10.0.0.2, 10.0.0.3, 10.0.0.4
    db.read.port = 3306

## Types
- `string`: Default value type.
- `bool`, `boolean`:
  - False values include: `0`, `false`, `no`, `disabled`, `off`.
  - True values include: `1`, `true`, `yes`, `enabled`, `on`.
- `int`, `integer`
- `float`, `double`

## Lists
Types can be declared as lists by appending square brackets `[]`
to the type and separating the values with commas:

    hosts, string[] = 10.0.0.1, 10.0.0.2

Commas in the value lists can be escaped with the backslash:

    places, string[] = SF\, CA, Boston\, MA

## Comments
Lines that start with any of the following characters are ignored: `; # //`

    ; comment
    # comment
    // comment

## Inheritance
TODO: describe the inheritance model, and the environment parser


## Further reading
- [DocOpt](https://github.com/docopt/docopt.php/)
- [DocOpt tester](http://try.docopt.org/)
