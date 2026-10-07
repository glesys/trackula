# Trackula

Guesses which carrier a parcel tracking number belongs to — offline, with no API calls. Nordic carriers come first, then European, then the rest of the world.

```php
use Glesys\Trackula\Trackula;

$result = Trackula::create()->detect('3704 3810 1268 0585 36');

$best = $result->best();
$best->carrier->name;   // "Bring"
$best->confidence;      // Confidence::Certain
$best->trackingUrl;     // "https://tracking.bring.com/tracking/370438101268058536"
```

Many formats are shared between carriers, so `detect()` returns every plausible carrier, best first. It never throws on bad input; an unknown number gives an empty result.

## Result

| Field | Meaning |
|---|---|
| `$result->normalized` | The number uppercased, without whitespace or GS1 brackets. |
| `$result->candidates` | `list<Candidate>`, best first. |
| `$result->best()` | The first candidate, or `null`. |
| `$candidate->carrier` | `id`, `name`, `region` (`Region::Nordic`, `European`, `International`). |
| `$candidate->format` | The matched format, e.g. `SSCC`, `S10`, `UPS`. |
| `$candidate->service` | Service type when the format encodes one, otherwise `null`. |
| `$candidate->confidence` | `Certain`, `Likely` or `Possible`. Branch on this. |
| `$candidate->checksumVerified` | `true`, or `null` when the format has no check digit. |
| `$candidate->trackingUrl` | Link to the carrier's tracking page, or `null` when unknown. |
| `$candidate->score` | Ordering only; not a contract. |

### Confidence

- **Certain** — a distinctive format (letters, or a carrier's own GS1 range) with a verified check digit, and no other carrier verifies.
- **Likely** — a verified check digit or a distinctive format, but other carriers remain possible.
- **Possible** — the pattern matches, nothing more.

Ties are broken by region, then by the order of carriers in `data/carriers.json`.

### What to expect

| Input | Candidates |
|---|---|
| `1Z5R89390357567127` | UPS, certain |
| `RR123456785SE` | PostNord, certain |
| `84971563697SE` | PostNord, certain |
| `370438101268058536` | Bring, certain (Bring's own GS1 range) |
| `00370438101268058536` | Bring, USPS — likely (the 20-digit form also passes USPS's check) |
| `373999991234567899` | PostNord, Bring, DHL Freight SE, DSV Road, Posti — all likely (sender's GS1 range) |
| `9500486734` | DHL Freight SE, DSV Road — likely (shared Swedish waybill format) |
| `BRING-1234-5678` | Bring, likely |

## Rule data

```
data/
  upstream/         jkeen/tracking_number_data, an untouched copy of a release tag
  glesys/           our rules, in the same schema
  carriers.json     carriers, regions, tracking URLs, upstream mapping, S10 countries
```

- **Upstream** covers UPS, FedEx, USPS, DHL Express and eCommerce, DPD, S10 and more, with test numbers for every format. Update it with `bin/update-upstream-data <tag>` and commit the result on its own.
- **`data/glesys/`** follows the upstream schema. A file may list several carriers in `courier_code` when the format is shared (GS1 SSCC/GSIN, the Swedish waybill). Every rule ships with valid and invalid test numbers taken from the carrier's own documentation.
- **`carriers.json`** names each carrier, its region and tracking URL, and any GS1 company prefixes it issues itself. It maps upstream rules to carriers, maps S10 country codes to Nordic posts, and can switch upstream rules off with a reason under `disabled`.

Checksums beyond upstream's: `gls_mod10` (GLS), and `s10` honours its `weightings` parameter (PostNord's 13-character numeric format needs ten weights).

## Development

```bash
composer install
composer check      # mago format --check, mago lint, mago analyze, pest
```

Every rule's test numbers run as part of the suite, so a rule or upstream update that breaks a format fails there.

## Licence

MIT. The upstream data is MIT-licensed by its authors; see `data/upstream/LICENSE`.
