# SR_CatalogRuleApi

`studioraz/magento2-catalogrule-api`

Adds REST API endpoints for Magento **catalog price rules** (`Magento_CatalogRule`), which ships with
service contracts but no `etc/webapi.xml` out of the box. The module follows the same REST design Magento
uses for `Magento\SalesRule\Api\RuleRepositoryInterface` (sales/cart price rules): a repository interface
with `save` / `getById` / `getList` / `deleteById`, a plain extensible DTO, and converters between the DTO
and the core ORM model.

## Contents

- [Why a new module](#why-a-new-module-instead-of-routing-the-core-repository)
- [Requirements](#requirements)
- [Installation](#installation)
- [Endpoints](#endpoints)
- [Authentication & permissions](#authentication--permissions)
- [Rule fields](#rule-fields)
- [Examples](#examples)
- [Validation errors](#validation-errors)
- [Indexing behavior](#indexing-behavior)
- [Architecture](#architecture)
- [Tests](#tests)
- [License](#license)

## Why a new module instead of routing the core repository

`Magento\CatalogRule\Api\CatalogRuleRepositoryInterface` cannot be exposed over REST as-is:

- its `Api\Data\RuleInterface` has no `from_date`, `to_date`, `website_ids` or `customer_group_ids`, and no
  `getList()`;
- its `save()` merges the incoming data onto the currently loaded model via `addData()`, so a PUT keeps the
  **old condition tree**, swallows real validation error messages, skips `Rule::validateData()`, and never
  sets the admin "rules need to be applied" flag.

This module reuses the core `Magento\CatalogRule\Model\Rule` ORM model, its resource model, and its
condition converter **unchanged** — it only adds a REST-facing service layer around them, the same way
`Magento_SalesRule` does for sales rules. Nothing in `Magento\CatalogRule\*` is overridden, plugged into,
or observed; this module only calls the same public entry points the admin controllers already use, so it
carries no risk of regressing native catalog-rule behavior. Disabling or removing the module removes
everything it added, with no data migration to undo.

## Requirements

- Magento / Mage-OS 2.4.x with `Magento_CatalogRule` and `Magento_Webapi` enabled
- PHP `~8.1.0||~8.2.0||~8.3.0||~8.4.0`

## Installation

```bash
composer require studioraz/magento2-catalogrule-api
bin/magento setup:upgrade
bin/magento setup:di:compile
bin/magento cache:flush
bin/magento module:status SR_CatalogRuleApi   # should print "enabled"
```

## Endpoints

| Method | Path | Service | Purpose |
|---|---|---|---|
| GET | `/V1/catalogRules/:ruleId` | `RuleRepositoryInterface::getById` | Fetch a single catalog price rule by its id |
| GET | `/V1/catalogRules/search` | `RuleRepositoryInterface::getList` | Search/list rules using standard `SearchCriteriaInterface` filters, sorting, and paging |
| POST | `/V1/catalogRules` | `RuleRepositoryInterface::save` | Create a new rule |
| PUT | `/V1/catalogRules/:ruleId` | `RuleRepositoryInterface::save` | Update an existing rule, **replacing** its condition tree rather than merging into it |
| DELETE | `/V1/catalogRules/:ruleId` | `RuleRepositoryInterface::deleteById` | Delete a rule by its id |
| POST | `/V1/catalogRules/apply` | `RuleManagementInterface::applyAll` | Apply all active rules now — the REST equivalent of the admin "Apply Rules" button |

`/search` and `/apply` are safe alongside `/:ruleId` — Magento's REST router matches exact paths before
parameterized ones, the same way `/V1/salesRules/search` coexists with `/V1/salesRules/:ruleId`.

## Authentication & permissions

Every route requires a valid Magento admin (or integration) token **and** the
`Magento_CatalogRule::promo_catalog` ACL resource (Admin: *Marketing > Promotions > Catalog Price Rule*) —
the same resource the admin grid itself uses. Both conditions were verified against a running instance:

| Caller | Result |
|---|---|
| No `Authorization` header | `401` |
| Invalid/garbage token | `401` |
| Valid token, admin **without** `Magento_CatalogRule::promo_catalog` | `401` — `"The consumer isn't authorized to access Magento_CatalogRule::promo_catalog"` |
| Same valid token, on a resource that admin **does** hold (e.g. `Magento_Catalog::products`) | `200` |
| Valid token, admin **with** `Magento_CatalogRule::promo_catalog` | `200` |

Get a token:

```bash
curl -sk -X POST "https://<host>/rest/V1/integration/admin/token" \
  -H "Content-Type: application/json" \
  -d '{"username":"<admin-user>","password":"<admin-password>"}'
```

(or, in a dev environment with `n98-magerun2`: `n98-magerun2.phar admin:token:create <admin-user>`.)

## Rule fields

| Field | Type | Notes |
|---|---|---|
| `rule_id` | int\|null | omit on create |
| `name` | string | required |
| `description` | string\|null | |
| `is_active` | bool | |
| `website_ids` | int[] | required, non-empty |
| `customer_group_ids` | int[] | required, non-empty |
| `from_date` / `to_date` | string\|null | `Y-m-d` |
| `condition` | object\|null | see below; a `PUT` **replaces** the whole tree |
| `stop_rules_processing` | bool | |
| `sort_order` | int | |
| `simple_action` | string | one of `by_percent`, `by_fixed`, `to_percent`, `to_fixed` |
| `discount_amount` | float | |
| `extension_attributes` | object | |

`condition` reuses the core catalog-rule condition DTO
(`Magento\CatalogRule\Api\Data\ConditionInterface`): `type`, `attribute`, `operator`, `value`,
`aggregator`, `conditions[]` (nested). `type` is restricted to a whitelist —
`Magento\CatalogRule\Model\Rule\Condition\Combine` and `...\Condition\Product` by default (see
[Architecture](#architecture)).

## Examples

All examples assume `HOST=https://project-cloudflare.ddev.site` and a valid `$TOKEN`.

### Create a rule (POST)

```bash
curl -sk -X POST "$HOST/rest/V1/catalogRules" \
  -H "Authorization: Bearer $TOKEN" -H "Content-Type: application/json" \
  -d '{
    "rule": {
      "name": "15% off MB products",
      "is_active": true,
      "website_ids": [1],
      "customer_group_ids": [0, 1, 2, 3],
      "from_date": "2026-09-22",
      "to_date": "2026-12-31",
      "simple_action": "by_percent",
      "discount_amount": 15,
      "condition": {
        "type": "Magento\\CatalogRule\\Model\\Rule\\Condition\\Combine",
        "aggregator": "all",
        "value": "1",
        "conditions": [
          {
            "type": "Magento\\CatalogRule\\Model\\Rule\\Condition\\Product",
            "attribute": "sku",
            "operator": "{}",
            "value": "MB"
          }
        ]
      }
    }
  }'
```

`200 OK` with the saved rule, including its new `rule_id`.

### Get a rule (GET)

```bash
curl -sk -H "Authorization: Bearer $TOKEN" "$HOST/rest/V1/catalogRules/2"
```

### Search rules (GET)

```bash
curl -sk -H "Authorization: Bearer $TOKEN" -G "$HOST/rest/V1/catalogRules/search" \
  --data-urlencode "searchCriteria[filter_groups][0][filters][0][field]=is_active" \
  --data-urlencode "searchCriteria[filter_groups][0][filters][0][value]=1"
```

Standard `SearchCriteriaInterface` query params apply (`filter_groups`, `sort_orders`, `pageSize`,
`currentPage`). Note that `[`/`]` must be URL-encoded — use `curl -G --data-urlencode` or an HTTP client
that encodes brackets automatically, otherwise `curl` reports a malformed URL.

### Update a rule (PUT) — replaces the condition tree

```bash
curl -sk -X PUT "$HOST/rest/V1/catalogRules/2" \
  -H "Authorization: Bearer $TOKEN" -H "Content-Type: application/json" \
  -d '{
    "rule": {
      "name": "15% off a single SKU",
      "is_active": true,
      "website_ids": [1],
      "customer_group_ids": [0, 1, 2, 3],
      "simple_action": "by_percent",
      "discount_amount": 15,
      "condition": {
        "type": "Magento\\CatalogRule\\Model\\Rule\\Condition\\Combine",
        "aggregator": "all",
        "value": "1",
        "conditions": [
          {
            "type": "Magento\\CatalogRule\\Model\\Rule\\Condition\\Product",
            "attribute": "sku",
            "operator": "==",
            "value": "LW-TEST-001"
          }
        ]
      }
    }
  }'
```

The `:ruleId` path segment always wins over any `rule_id` in the body. A subsequent `GET` returns the new
condition tree only — the previous subtree is gone, not merged.

### Delete a rule (DELETE)

```bash
curl -sk -X DELETE -H "Authorization: Bearer $TOKEN" "$HOST/rest/V1/catalogRules/2"
```

`200` with body `true` on success; a second call (or any unknown id) returns `404`.

### Apply all active rules (POST)

```bash
curl -sk -X POST -H "Authorization: Bearer $TOKEN" "$HOST/rest/V1/catalogRules/apply"
```

Equivalent to the *Apply Rules* button in Admin: *Marketing > Promotions > Catalog Price Rules*. `200` with
body `true` on success.

## Validation errors

Invalid input returns `400` with the real error messages — core rule-model errors and this module's own
validator errors are both included, unlike the core repository which masks them:

```bash
curl -sk -X POST "$HOST/rest/V1/catalogRules" \
  -H "Authorization: Bearer $TOKEN" -H "Content-Type: application/json" \
  -d '{"rule": {"name": "Bad discount", "website_ids": [1], "customer_group_ids": [0], "simple_action": "by_percent", "discount_amount": 150}}'
# -> 400 {"message":"Percentage discount should be between 0 and 100."}

curl -sk -X POST "$HOST/rest/V1/catalogRules" \
  -H "Authorization: Bearer $TOKEN" -H "Content-Type: application/json" \
  -d '{"rule": {"name": "No websites", "website_ids": [], "customer_group_ids": [0], "simple_action": "by_percent", "discount_amount": 10}}'
# -> 400 {"message":"One or more input exceptions have occurred.","errors":[
#      {"message":"Please specify a website."},
#      {"message":"\"%fieldName\" is required. Enter and try again.","parameters":{"fieldName":"website_ids"}}
#    ]}
```

## Indexing behavior

Saving or deleting a rule via REST behaves exactly like an admin save/delete: with the `catalogrule_rule`
indexer on *Update on Save* it reindexes on commit; on *Update by Schedule*, nothing happens until cron (or
`POST /V1/catalogRules/apply`) runs. Every save/delete also sets the same "rule changes need to be applied"
admin flag (`catalogrule_rules_dirty`) the admin Save/Delete controllers set; `apply` clears it.

## Architecture

Module code lives under `src/` (the composer package root itself, so Magento's `ComponentRegistrar`
resolves `src/` as the module directory — `composer.json`'s `autoload` maps the `SR\CatalogRuleApi\`
namespace to `src/`):

```
src/Api/RuleRepositoryInterface, src/Api/RuleManagementInterface      service contracts
src/Api/Data/RuleInterface, src/Api/Data/RuleSearchResultInterface    DTO contracts
src/Model/Data/Rule                                                   DTO (AbstractExtensibleObject)
src/Model/Converter/ToModel                                           DTO -> core Magento\CatalogRule\Model\Rule
src/Model/Converter/ToDataModel                                       core Rule -> DTO
src/Model/Data/Validator (+ RequiredFields, DateRange, ConditionType)  composite input validator
src/Model/RuleRepository, src/Model/RuleManagement                    di.xml-bound implementations
```

- `ToModel` merges the DTO onto the loaded model, calls the core `Rule::validateData()`, and — when a
  `condition` was submitted — calls `Rule::setRuleCondition()` to fully replace the condition tree (rather
  than the core repository's `addData()` merge, which leaves stale conditions in place on `PUT`).
- `ToDataModel` reads the model back out (forcing `getWebsiteIds()`/`getCustomerGroupIds()` to lazy-load
  first) and converts the serialized condition column via `Rule::getRuleCondition()`.
- `ConditionType` whitelists the condition class names accepted in `condition.type`, in addition to the
  core `Magento\Rule\Model\ConditionFactory` guard; extend the whitelist via `src/etc/di.xml` if custom
  condition types are added.

## Tests

Unit tests live under `src/Test/Unit/`, mirroring Magento core's own testing conventions (PHPUnit,
`Magento\Framework\TestFramework\Unit\Helper\ObjectManager`, `#[DataProvider]`):

```bash
vendor/bin/phpunit -c dev/tests/unit/phpunit.xml.dist module/studioraz/magento2-catalogrule-api/src/Test/Unit
```

## License

[MIT](LICENSE.txt) © Studio Raz
