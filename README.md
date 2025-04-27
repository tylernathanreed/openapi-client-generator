# OpenAPI Client Generator

## Introduction

The **OpenAPI Client Generator** is a tool intended to be used to create and maintain OpenAPI Client packages.

## Installation

Install this package using [Composer](https://getcomposer.org/)

```bash
composer install reedware/openapi-client-generator --dev
```

Once installed, run the `install` command, which will ask you some basic questions to set up a configuration file and generate the project baseline.

```bash
vendor/bin/openapi install --generate
```

## Usage

### Regenerate

At any point, you may run the `generate` command to update your client from its OpenAPI Specification.

```bash
vendor/bin/openapi generate
```

You may also generate subsets of your client as needed:

```bash
vendor/bin/openapi generate readme
vendor/bin/openapi generate client
vendor/bin/openapi generate schema
vendor/bin/openapi generate schema [name]
vendor/bin/openapi generate operations
vendor/bin/openapi generate operations [name]
```

### Applying Fixes to the OpenAPI Specification

It's common for the OpenAPI Specification to be coming from a source that you don't own, and may include problems.
You can create a `fixes` directory, which can contain explicit modifications to the OpenAPI Specification.

```json
[
    {
        "type": "set",
        "path": "components.schemas.Field.properties.identifier",
        "value": {
            "type": "string"
        }
    },
    {
        "type": "merge",
        "path": "components.schemas.Issue.properties.expirationDate",
        "value": {
            "type": "string",
            "format": "date-time"
        }
    }
]
```

All `json` files in the `fixes` directory are processed.
It's up to you if you want to use several smaller organized files, one large json file, or anything in between.

### Testing

Every operation will have a backing test generated for it.
If there are enough examples provided by the OpenAPI Specification, a running test will be generated.
If there are not enough examples, a test is still generated, but it as marked as incomplete.

You can run your tests using PHPUnit:

```bash
vendor/bin/phpunit
```

Or use the provided composer script:

```bash
composer test:suite     # No Coverage
composer test:coverage  # Suite + Coverage
```

If any tests fail, this is likely due to incomplete or inaccurate examples in the OpenAPI Specification.
You will need to apply fixes to the specification to get the test to pass.
