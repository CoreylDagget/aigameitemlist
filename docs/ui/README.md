# Web App UI — gameitemslist

The `/app` single-page experience provides the MVP user interface for
`gameitemslist`. It focuses on quick onboarding, frictionless list
management, and a simple sharing workflow so that players can exchange
item roadmaps without leaving the browser.

## Entry Points

| Path | Description |
| --- | --- |
| `/app/index.html` | Full SPA that covers registration, login, list curation, and share management. |
| `/app/index.html#shared=<token>` | Read-only view that resolves a published list via share token. |

> The SPA consumes the public API directly. All requests are same-origin,
> so no additional CORS configuration is required when running through
> the provided Docker stack.

## Core Flows

### 1. Onboarding & Authentication

1. Launch `/app/index.html`.
2. Use the **Register** form to create a new account (email + password).
   Successful registration automatically signs the user in and persists the
   bearer token in `localStorage`.
3. Existing accounts use the **Login** form; the active session is displayed
   in the header (“Signed in as …”).

### 2. Creating a List

1. After authentication the **Create a List** card becomes visible.
2. Choose a game from the dropdown (populated from `GET /v1/games`).
3. Provide list name, optional description, and whether the list should be
   published immediately.
4. Submitting the form calls `POST /v1/lists`; the list appears in the sidebar.

### 3. Working With Items

The right-hand pane activates once a list is selected:

- **Templates** — the SPA fetches `GET /v1/games/{gameId}/item-templates`
  and allows adding a prefab via the “From template” selector. Submissions
  call `POST /v1/lists/{listId}/items` with `templateId`.
- **Manual Items** — propose a new definition by filling in name, storage type,
  and optional description/image. Requests are sent to the same endpoint and
  create pending changes awaiting admin approval.
- Items already approved for the list render beneath the forms with their
  storage type and description.

### 4. Share Management

Within the list detail view the **Share** panel drives the link lifecycle:

- `GET /v1/lists/{listId}/share` indicates whether the list is private.
- “Create share link” triggers `POST /v1/lists/{listId}/share` and displays a
  shareable URL (`/app/index.html#shared=<token>`).
- “Rotate link” repeats the POST call with `{ "rotate": true }` to invalidate
  the previous token and issue a new one.
- “Revoke link” calls `DELETE /v1/lists/{listId}/share` and returns the list to
  private mode.
- The “Copy” button uses the Clipboard API for quick sharing.

### 5. Viewing Shared Lists

Consumers who receive a share URL are routed to
`/app/index.html#shared=<token>`. The SPA resolves the token via
`GET /v1/shared/{token}` and renders a read-only list of items with game context.
No authentication is required for this flow.

## UX & Visual Design

- **Frosted glass aesthetic:** cards are semi-transparent with backdrop blur
  and a radial gradient background inspired by modern RPG UI palettes.
- **Responsive grid:** list management switches to a single column on screens
  narrower than 900&nbsp;px.
- **Accessibility:** large tap targets, high-contrast text, and focus styles are
  applied across inputs and buttons.

## Development Notes

- JavaScript is authored as an ES module (`public/app/app.js`). No build step is
  required; the script runs directly in evergreen browsers.
- State is kept minimal (token, lists, templates). API errors surface via
  simple alerts to keep the MVP lightweight.
- The SPA assumes the API lives on the same origin. When deploying behind a
  reverse proxy ensure `/app/` assets are exposed alongside the API routes.

## Future Enhancements

- Surface pending change queues with status badges once admin workflows expose
  richer metadata.
- Persist recently selected lists and template filters in `localStorage`.
- Add tag assignment and personal ownership editing to the UI when the
  corresponding endpoints are ready.
