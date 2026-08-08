# Core updates (WordPress-style)

Run a fleet of blogs off this CMS and pull core updates from the admin panel
with a button. Build shared functionality once in the upstream core; each fork
consumes it as a signed, prebuilt release. No Node or shell on the host is
required — the admin button works on locked-down shared hosting.

## How it works

A release is a `tar.gz` of the **core-owned** paths plus the prebuilt front-end
(`public/build`, `bootstrap/ssr`), `vendor/`, and a `release-manifest.json`
(version, `min_php`, `min_from_version`, per-file sha256). It carries a sha256
and an Ed25519 signature.

The updater (`app/Support/Updater`) runs this sequence and rolls back on any
failure after it starts writing:

1. Preflight — PHP version, `min_from_version`, writable install.
2. Maintenance mode on.
3. Download and verify sha256 + signature.
4. Back up the files about to change (and a best-effort DB dump).
5. Extract, validate every file against the manifest.
6. Apply — atomic per-file swap, **core-owned paths only**.
7. Dependencies — use the shipped `vendor/`, or run composer if opted in.
8. `migrate --force`, rebuild caches.
9. Maintenance mode off.

Everything runs via `Artisan::call` and Symfony `Process` — never `shell_exec`,
which Hostinger disables.

## The ownership boundary

`config/cms.php` `paths` (enforced by `App\Support\Updater\PathManifest`)
classifies every path. Longest-prefix match wins, so a nested `site` path beats
its `core` parent.

- **core** — overwritten on update: `app/**` (except `app/Site`), `config/**`
  (except `config/site.php`), core migrations, `resources/js` (except
  `resources/js/site`), prebuilt assets, `vendor/`.
- **site** — never touched: `app/Site/**`, `config/site.php`,
  `database/site-migrations`, `resources/js/site`, `public/front/<theme>`.
- **preserve** — never touched: `.env`, `storage`, `public/uploads`, keys, DB.

Editing a core-owned file directly means losing that edit on the next update,
exactly like editing WordPress core. Put your changes in the site zone, in a
plugin (`app/Site/Plugins`), or in theme settings.

## Two theming tiers

The front-end is a single Vite bundle and the host has no Node, so a fork's
code-level theme can't live in core's prebuilt bundle. Two tiers resolve this:

- **Tier 1 — data/token theming (no rebuild).** Set logo, colours, font, and
  radius in **Settings → Theme**. They are injected as CSS variables at request
  time. A tier-1 site consumes **core's** prebuilt releases directly. This is
  the default for a fleet.
- **Tier 2 — code-level theming (needs a rebuild).** A fork that edits React
  components or adds models runs its **own CI** to build its **own** release,
  with the theme baked in.

`config('cms.update.channel')` selects which feed a site follows — core's
releases for tier 1, the fork's own releases for tier 2. The admin button
behaves identically either way.

## Set up a fleet site

1. Point the site at a channel and trust the signing key:

   ```env
   CMS_UPDATE_CHANNEL=https://github.com/<owner>/<repo>/releases/download/releases.json
   CMS_UPDATE_PUBLIC_KEY=<base64 ed25519 public key>
   ```

2. Add one cron entry so the scheduler runs (drives the background check and
   surfaces the "update available" banner):

   ```
   * * * * * cd /path/to/site && php artisan schedule:run >> /dev/null 2>&1
   ```

3. Update from **Admin → Settings → Updates**, or from the CLI:

   ```bash
   php artisan cms:update --check   # report only
   php artisan cms:update           # apply
   ```

## Cut a release (core, or a tier-2 fork)

1. Generate a signing keypair once:

   ```bash
   php artisan tinker --execute="print_r(App\Support\Updater\Signer::generateKeypair());"
   ```

   Put `secret` in the repo's CI secret `CMS_RELEASE_SIGN_KEY`; give `public`
   to each site as `CMS_UPDATE_PUBLIC_KEY`.

2. Bump `version` in `config/cms.php`, tag `vX.Y.Z`, and push the tag. The
   `release` workflow installs prod deps, builds the front-end, runs
   `php artisan cms:build-release`, and publishes the archive + manifest +
   sha256 + signature to a GitHub Release.

## Tier-2 forks: track core

A tier-2 fork keeps its own code but still wants core's improvements. Add core
as an upstream remote and merge it, then let the fork's CI build a new release:

```bash
git remote add upstream https://github.com/<core-owner>/agentic-cms-laravel.git
git fetch upstream
git merge upstream/main
```

`.github/workflows/core-sync.yml` (shipped, disabled by default) opens a PR with
the upstream changes on a schedule. Enable it in a fork to automate the fetch.

## Environments differ

The updater probes the host (`App\Support\Updater\Environment`) and adapts:

| Capability            | Local | Shared hosting | VPS |
|-----------------------|:-----:|:--------------:|:---:|
| Node (build on host)  |  yes  |       no       | yes |
| composer at update    |  yes  |    often no    | yes |
| shell_exec/exec       |  yes  |       no       | yes |
| proc_open             |  yes  |      yes       | yes |

The shipped `vendor/` and prebuilt assets mean an update is pure extraction, so
it works even where composer and Node are absent.

## Rollback and safety notes

- Any failure after apply restores the file snapshot and brings the site back
  up. The attempt is recorded in `cms_updates` (visible in the admin history).
- A full transactional DB rollback isn't guaranteed on shared hosting, so keep
  migrations **additive-only**; the file snapshot plus a best-effort `mysqldump`
  cover the rest.
- A signature is mandatory in production: an unsigned or invalid release is
  refused.
