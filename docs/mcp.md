# Agentic CMS for Laravel MCP server (AI connector)

Agentic CMS for Laravel ships a built-in **Model Context Protocol (MCP) server** so an AI
client such as **Claude** (Claude Code CLI, the VS Code extension, or claude.ai) can manage
your **live** site (posts, pages, categories, users, settings, and theme templates) through
scoped, authenticated tools instead of the admin panel UI.

It is built on the official [`laravel/mcp`](https://laravel.com/docs/12.x/mcp) package
and runs **inside the Laravel app itself** (no separate Node service). Every tool runs
as the OAuth-authenticated admin and is gated by that account's existing Agentic CMS
permissions. There is **no raw code-execution tool**: the only "code" surface is
editing the React page components under `resources/js/pages`, with strict path
allow-listing.

- **Endpoint:** `POST https://<your-site>/mcp/agentic-cms-laravel`
- **Auth:** OAuth 2.1 via Laravel Passport (the MCP-standard mechanism)
- **Transport:** streamable HTTP

---

## What the AI can do (tools)

All tools validate their inputs and return structured results. "Requires X" is the
Agentic CMS for Laravel permission the connected account must hold, or the tool returns
*Permission denied*.

| Domain | Tools | Permission |
|---|---|---|
| **Posts** | `list-posts`, `get-post`, `create-post`, `update-post`, `delete-post` | `manage_posts` |
| **Pages** | `list-pages`, `get-page`, `create-page`, `update-page`, `delete-page` | `manage_pages` |
| **Categories** | `list-categories`, `get-category`, `create-category`, `update-category`, `delete-category` | `manage_post_categories` |
| **Users** | `list-users`, `get-user`, `create-user`, `update-user`, `delete-user`, `list-roles` | `manage_users` |
| **Settings** | `get-general-settings`, `update-general-settings`, `get-seo-settings`, `update-seo-settings` | `manage_general_settings` |
| **Theme** | `list-theme-files`, `read-theme-file`, `write-theme-file` | `manage_general_settings` |

Notes:
- **Multilingual content** (posts/pages/categories) takes an optional `locale`
  argument (e.g. `en`, `ru`); omitting it targets the site default language.
- **User passwords** are always hashed; passwords are never returned by any tool.
  The primary admin (id 1) and your own account cannot be deleted.
- **Theme tools** only touch `*.tsx` page components under `resources/js/pages`
  (the UI is Inertia + React since the migration). Absolute paths, `..` traversal,
  co-located `*.test.tsx`, and non-`.tsx` files are rejected. Components are
  read/written as text, never compiled or executed.

---

## Server-side setup (one time)

The MCP code is part of the repo. To enable it on a deployment you only need to make
sure Passport is migrated and has keys. (`composer install` already pulls in
`laravel/mcp` and `laravel/passport`.)

```bash
# 1. Run migrations — adds Passport's oauth_* tables
php artisan migrate

# 2. Generate Passport encryption keys (writes storage/oauth-*.key; NOT committed)
php artisan passport:keys
#   On a fresh setup you can instead run: php artisan passport:install

# 3. Make sure APP_URL is your real public https URL — OAuth discovery uses it
#    APP_URL=https://your-site.com   (in .env)

# 4. If you cache config in production, refresh it
php artisan config:clear
```

Requirements / notes:
- **HTTPS is required** for OAuth in any real client.
- The app's `api` auth guard is configured to use Passport (`config/auth.php`).
- Keys live in `storage/` and are git-ignored (`/storage/*.key`). Generate them
  **once per environment**; don't copy a dev key to production.
- The consent screen shown to the admin during authorization is
  `resources/views/mcp/authorize.blade.php` (published, customizable).

### Give an account access

A connecting account must be a user whose **role** has the relevant `manage_*`
permission(s) — exactly the same flags used by the admin panel
(**Admin → Users → Roles**). Create a dedicated "AI automation" role granting only
the capabilities you want the assistant to have, and assign it to the user you'll
authenticate with.

---

## Connect from Claude

When the client first calls the server it performs the OAuth flow: your browser
opens to Agentic CMS for Laravel, you **log in as the admin** and **approve** the connection, and
the client stores the token. Tokens expire (15 days by default; see
`AppServiceProvider`) and refresh automatically.

### Claude Code (CLI)

```bash
claude mcp add --transport http agentic-cms-laravel https://your-site.com/mcp/agentic-cms-laravel
```

Then start Claude and run `/mcp` — choose **agentic-cms-laravel → Authenticate** to complete
the OAuth login in your browser. After that you can ask things like
*"list the latest posts"* or *"create a draft page titled About us"*.

### Claude Code (VS Code extension)

The extension reads the same MCP config. Either run the `claude mcp add` command
above in the integrated terminal, or add a `.mcp.json` at your project root:

```json
{
  "mcpServers": {
    "agentic-cms-laravel": {
      "type": "http",
      "url": "https://your-site.com/mcp/agentic-cms-laravel"
    }
  }
}
```

Reload the window; the extension will prompt you to authenticate on first use.

### claude.ai (web)

Add a **Custom Connector** in Settings and paste the endpoint
`https://your-site.com/mcp/agentic-cms-laravel`; complete the OAuth login when prompted.

---

## Test & debug locally

With the stack running (`make up`) you can exercise the server with the bundled
MCP Inspector:

```bash
php artisan mcp:inspector mcp/agentic-cms-laravel
```

Automated tests for authorization and theme-path safety live in
`tests/Feature/Mcp/AgenticCmsLaravelServerTest.php`:

```bash
php artisan test --filter=AgenticCmsLaravelServerTest
```

---

## How it's wired (for contributors)

- **Route:** `routes/ai.php` — `Mcp::oauthRoutes()` + `Mcp::web('/mcp/agentic-cms-laravel', …)`
  behind `auth:api` (Passport) and `throttle:120,1`.
- **Server:** `app/Mcp/Servers/AgenticCmsLaravelServer.php` registers all tools.
- **Tools:** `app/Mcp/Tools/<Domain>/…` — thin classes that validate input and
  delegate to the existing `CPanel*Repository` classes (same code path as the admin
  panel), so business rules and observers are reused rather than re-implemented.
- **Concerns:** `app/Mcp/Concerns/`
  - `AuthorizesAccess` — per-tool permission gate, read from the bearer-token user
    (never the web session).
  - `ResolvesLocale` — validates and applies the request locale for translatable writes.
  - `HydratesRequest` — mirrors validated input onto the global request so the
    request-coupled model observers behave exactly as they do for an admin form post.
  - `ResolvesThemePath` — path allow-listing for the theme-file tools.

To add a capability: create a tool with `php artisan make:mcp-tool`, gate it with
`AuthorizesAccess::deny()`, delegate to a repository, and register it in
`AgenticCmsLaravelServer::$tools`.
