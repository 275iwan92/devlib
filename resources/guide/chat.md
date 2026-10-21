## Overview

`<x-dl::chat>` is the person-to-person chat screen of DevLib: a left list of people (those who are signed in first, with a green presence dot), a conversation pane on the right (day separators, ticks sent / received / read, "typing...", photo and image lightbox, file attachments), and a "+" panel to start a chat with someone who is not signed in. It is a pure front-end object: it draws what it is given in `me`, `people` and `convos`, and, when `api` is filled, it talks to four to six thin routes of the application. It has no tables and no models of its own.

Two things are called "chat" in DevLib and must not be mixed up:

- **Chat** (this entry, backend `src/Chat`): human to human, exactly two participants per conversation. Rules: `skills/devlib/chat.md`.
- **AI Assistant** (the drawer of `x-dl::layer-shell`, backend `src/Assistant`): a question and answer panel that answers from the application's screens, Help and calculation engines. It is not a chat peer; never put an "assistant" person into `people`. Its drawer is documented in `x-dl::layer-shell` (prop `assistant`); its server contract and data policy are described in this entry because they are the other half of the same "talk to the application" feature (see "Data and events" and "Assistant data policy").

Use `x-dl::chat` when users must exchange short messages and files inside the application. Do NOT use it for comments on a document (use `x-dl::feed` or `x-dl::review-feed`), for notifications (`x-dl::notification-list`), or for group channels, voice, or editing/deleting messages (none of these exist in the contract).

Placement: standalone (`height`), as the `chat` slot of `x-dl::layer-menu` (swap with the menu or dock to the right), or as the Chat drawer of `x-dl::layer-shell` (prop `chat`), always with `compact`.

### Generated markup

```html
<div class="dl-wa [is-compact] [is-open]" data-dl="x-dl::chat" data-dl-chat style="--dl-wa-h: 560px"
     data-me='{"id":1,...}' data-people='[...]' data-convos='{...}' data-api='{...}'>
  <section class="dl-wa__side">
    <div class="dl-wa__bar"> avatar of me, "Chat", [data-wa-new] "+" </div>
    <div class="dl-wa__search"><input type="search" data-wa-q></div>
    <div class="dl-wa__list" data-wa-list></div>
    <div class="dl-wa__new" data-wa-newpanel hidden> ... [data-wa-nq] ... [data-wa-offline] </div>
  </section>
  <section class="dl-wa__main">
    <div class="dl-wa__bar dl-wa__head" data-wa-head></div>
    <div class="dl-wa__msgs" data-wa-msgs></div>
    <div class="dl-wa__preview" data-wa-preview hidden></div>
    <div class="dl-wa__in" data-wa-in> [data-wa-attach] [data-wa-file] [data-wa-text maxlength=4000] [data-wa-case] [data-wa-send] </div>
  </section>
  <div class="dl-wa__lb" data-wa-lb hidden> lightbox: [data-wa-lbx] [data-wa-lbdl] [data-wa-lbimg] [data-wa-lbcap] </div>
</div>
```

The lists, the header and the messages are drawn by the script (`resources/assets/js/components/chat.js`), not by Blade. Extra HTML attributes (`class`, `id`, `style`) and `can="module.action"` go on the root `div`; without the ability nothing is rendered.

## Props

Constructor of `Devlib\Components\Chat` (7 props). Use `:prop="expr"` for arrays and booleans. All array props are written to `data-*` attributes as JSON, so they must contain only JSON-safe values and nothing secret: the user can read them in the page source.

| Prop | Type, default | Meaning and effect |
|---|---|---|
| `me` | `array`, `[]` | The signed-in user: `['id', 'name', 'photo']`. `photo` is a URL or data URI; without it the avatar shows the initials of `name`. `id` is compared with the sender of real-time frames (`chat.message`), so it must be the same key the server uses. An empty array only shows `?` as avatar and breaks that comparison. `ChatService::boot()` returns it ready. |
| `people` | `array`, `[]` | Everybody who can be chatted with, WITHOUT the current user: `[['id', 'name', 'role', 'photo', 'online' => bool, 'seen' => epoch seconds or text]]`. `online` decides the list section and the green ring; `seen` is shown as "last seen ..." for offline people (a number is treated as epoch seconds in the application time zone; a string, as in the preview data, is printed as is). `role` is shown under the name and is also searched. |
| `convos` | `array`, `[]` | Existing conversations, keyed by the id of the other person: `[personId => ['unread' => n, 'read' => id, 'msgs' => [MSG, ...]]]`. `unread` is the badge; `read` is the id of the last message of the current user that the other person has read (drives the blue ticks). Written as a JSON object even when empty. People without an entry have an empty conversation. |
| `api` | `array`, `[]` | Server connection. Empty (default) = PREVIEW: nothing is stored, sending only adds a local bubble and fires `dl:chat-send`. Filled with a `poll` address = LIVE mode (see "Data and events" for every key). The switch is exactly `api.poll`: an `api` with only `send` stays a preview. |
| `compact` | `bool`, `false` | Adds `is-compact`: one column that alternates between the list and the conversation (a back arrow appears in the conversation header). Used inside drawers and the Layer Menu. When the surrounding drawer is maximised the CSS shows both columns again. Below 700 px width the normal chat collapses the same way even without `compact`. |
| `height` | `string`, `'560px'` | CSS length written to the custom property `--dl-wa-h` (`height:var(--dl-wa-h,560px)`). Ignored inside a `.dl-drawer`, where the chat fills the drawer (`flex:1`). Any CSS length works (`600px`, `70vh`). |
| `source` | `?string`, `null` | Text for the developer catalog / Manifest (`data-dl-src`). The default is `sys_chat_conversations / sys_chat_messages + sys_user_presence`; pass your own (for example `Devlib\Chat\ChatService::boot()`) when your storage differs. No effect on the screen. |

### Shapes used by the props

`MSG` (one message), as produced by `Devlib\Chat\Message::toArray($viewer)`:

| Key | Meaning |
|---|---|
| `id` | Integer that only grows; the order of messages and the "read up to" marker. |
| `me` | `true` when written by the viewer. |
| `t` | Text (may be empty when there is a `file`). |
| `ts` | Epoch seconds; the clock and the day separator are derived from it in the application time zone. |
| `file` | `['name', 'size' (bytes), 'url', 'image' => bool]`. An image is shown inline and opens the lightbox; any other file is a download card. |
| `peer` | Only in `poll` answers: the id of the other person of that message (also for messages the viewer wrote elsewhere). |
| `at`, `day`, `img`, `st` | Preview-only shortcuts: ready-made clock text (`'09:12'`), day label (`'Today'`), image URL and tick state (`sent`, `delivered`, `read`) for data without a server. |

## Behavior

### Layout and lists

- Left list, top section "Signed in" (count): people with `online` true, filtered by the search box (name or role), newest conversation first. Below, "Other conversations": offline people that already have messages. The last message (or the role when there is none) is the second line; own messages show their tick; an unread badge and a bold time mark new messages.
- The "+" button opens the "New chat" panel: every person who is NOT online (also those with a conversation), searchable, with role and "last seen". Choosing one opens the conversation and closes the panel.
- The list is redrawn only when its HTML changed (so a click is never lost to a refresh). Every redraw dispatches `dl:chat-unread` with the total unread count.
- Empty states: nobody online, no match, "pick a conversation" in the right pane. All texts come from the dictionary keys `ch_*` (Indonesian and English).

### Conversation

- Header: back arrow (compact / narrow), photo + name (click = enlarged photo when `photo` exists), status line: "typing...", "online", "last seen ..." or "offline".
- Messages: grouped under day separators (today, yesterday, or the date), time per bubble, ticks on own messages: clock (sending), one tick (sent), two ticks (delivered), blue two ticks (read), red mark (failed). When the server reports more history (`more`), a button "Earlier messages" loads the previous page and keeps the scroll position.
- In LIVE mode the ticks are computed: `read` when the message id is not above the peer's `read` marker; `delivered` when the peer is online or was seen after the message time; otherwise `sent`.
- Sending: Enter or the paper-plane button. Text is trimmed; empty text without attachment is ignored; the input has `maxlength="4000"`. The `aa / Aa / AA` button next to the box cycles lower / Title Case / UPPER and rewrites the text (project rule for text inputs); it is not posted separately.
- Attachments: the paperclip opens the file picker (any type). A 0-byte file and a file above `api.max_mb` are refused with a warning toast. One file at a time waits in a preview strip (thumbnail for jpeg/png/gif/webp, an icon otherwise) with an X to cancel; the text box then acts as the caption. In LIVE mode the file is uploaded in chunks (progress bar in the bubble) before the message is sent.
- Preview mode: the bubble is added locally; for an online peer it turns delivered after 0.6 s and read after 1.5 s (simulation). Nothing is saved.
- Failed sends stay in the list with the failed mark; there is no retry button, the user types again.

### Reading, unread and sound (LIVE mode)

- A conversation counts as "being looked at" only when it is the open one, the browser window has focus, and the chat element is visible. Messages arriving for a conversation that is not being looked at increase `unread`, play two short sine tones (only after the first click or key press on the page, a browser rule) and, when the whole chat is hidden (for example the drawer is closed), show a toast "New message from ...".
- Opening a conversation, or returning to the window, clears its unread count and the next poll carries `open=<peerId>` so the server marks the messages read (the other side gets blue ticks).

### Polling and real-time

- `api.poll` is called every `api.poll_seconds` (default 5). Each poll is also the presence heartbeat.
- With `api.realtime` the chat additionally opens a WebSocket (Pusher protocol 7, as served by Laravel Reverb), subscribes to the listed channels after the server signs each one, and then polls only every `api.realtime_poll_seconds` (default 30) as a safety net. On disconnect it returns to fast polling and retries the connection every 30 s. "Typing..." is sent (at most every 2.5 s) and shown (4 s) only while the socket is connected.
- HTTP 401 or 419 on any call = session over: one toast, polling and socket stop (the login page will answer next time). HTTP 422 shows the `message` of the answer; other failures show a generic toast.

### Accessibility as implemented

- Buttons are native `<button>`s with `aria-label` (new chat, back, attach, send, close, download); the two search inputs have `aria-label`. Enter sends, Esc closes the lightbox, all rows are in the Tab order.
- The message list is not an `aria-live` region and bubbles have no roles, so incoming messages are not announced by screen readers; the sound and the unread badge are the only signals. Tick icons carry a `title` (sent, delivered, read), not an `aria-label`.

### Assistant data policy

The AI Assistant (`src/Assistant`) is where DevLib enforces what an automated helper may touch. The rules are fixed by the owner and apply to every project that installs a `DataPolicy` (`src/Assistant/DataPolicy.php`, rules in `skills/devlib/assistant.md`):

| Level | Content | Who | Enforcement |
|---|---|---|---|
| (a) Knowledge | menus, screens, Help, procedures | per screen rights (`Navigator` already filters per asker) | default |
| (b) Quantity | counts, stock, orders, attendance | module right of the asker (`Asker::can`) | `Sensitivity::Quantity` (default of a `Tool`) |
| (c) Money values | prices, totals, receivables, costs, inventory value | only askers the program declares as finance people (`ValueAsker::canSeeValue()`, or per tool `ScopedValueAsker::canSeeValueOf($toolKey)`); the RESULT of a costing calculation may also be opened to sales by the program | `Sensitivity::Value`; otherwise `Reply::DENIED` |
| (d) Never | salary and payroll data, the FORMULA of costing (HPP: rules, components, parameters), personal data of any person | nobody, including finance, HR, admin and the data owner | not read, not indexed, not answered, never a `Tool` |

- Personal data means personal identity and contact data: identity card / tax / passport numbers, home address, birth date and place, private phone or e-mail, family data, private bank account, health records, passwords. Customer delivery addresses, supplier contacts and company tax numbers are business data and stay available by module right. Attendance is not payroll and stays level (b).
- Protected people: the program lists names / logins / aliases (`new DataPolicy(protected: [...])`); DevLib stores no names. A question that mentions any listed person (all words of one entry present, any order, case, punctuation and underscores ignored) gets a fixed `DENIED` before any tool, screen, help topic or language model sees it, and is also removed from the history sent to the model. A partial name alone does not match; the program registers short aliases as separate entries.
- A forbidden or personal-data question never reaches a `Tool` or the language model. For personal-data phrasing only screens and Help topics can match (so "where do I manage my own data" can still show the screen), otherwise the answer is `DENIED`. Screens, topics and tools whose key, label, path, module, title or phrases contain a forbidden word, a personal-data phrase or a protected person are never offered, and a `Tool` like that makes `new Assistant(...)` throw `AssistantException`. A tool declared `Sensitivity::Forbidden` always throws, with or without a policy.
- Without a `DataPolicy`, or in a program that has no such modules and people, nothing is blocked (the DevLib catalog site behaves this way). The match is on declared text only: DevLib cannot see that a tool labelled `Quantity` really returns money or personal data. Correct labels, the protected list and the policy are the program's duty (`IndexAudit::leaks()` is the required test).
- The human chat of this entry is NOT scanned or filtered by `DataPolicy` and the Assistant has no access to `Devlib\Chat` (nothing in `src/Assistant` references it). The policy only governs what the Assistant answers.

## Data and events

### Backend contracts of Chat (`Devlib\Chat`)

DevLib holds the logic, the project holds the storage. `ChatService` knows no table, route or user class; it talks through four interfaces the project binds:

| Class | Role | Bound by project |
|---|---|---|
| `Directory` | who can be chatted with: `people()` (list of `Person`: `id`, `name`, `role`, `photo`; must include the caller), `find($id)` | yes |
| `ChatStore` | messages: `append`, `between($a, $b, $before, $limit)` (oldest first), `after($user, $afterId)`, `hasOlder`, `markRead($reader, $peer, $upToId)` (only forward), `readBy($peer, $reader)`, `unread($me, $peer)` | yes |
| `Presence` | `touch`, `signOut`, `isOnline`, `lastSeen` (online = signed in AND recent heartbeat; the time limit is the implementation's) | yes |
| `Uploads` | chunked attachments: `receive($uid, $offset, $chunk)` returns total bytes, `complete($uid, $name, $owner)` returns an `Attachment` | optional |
| `Message`, `Attachment`, `Person`, `Channel` | value objects; `Channel::key($a, $b)` is symmetric (`"<smaller>:<larger>"`, compared as strings): keep it UNIQUE in storage | no |
| `ChatException` | the only failure type (unknown user, chat to yourself, empty or too long message, attachment too big, attachments disabled, out-of-order chunk) | no |
| `MemoryChat` | `ChatStore` + `Presence` + `Uploads` in memory, for tests and the reference site | no |

`new ChatService(directory, store, presence, ?uploads, maxChars: 4000, maxMb: 25, pageSize: 50)` offers:

| Method | Does |
|---|---|
| `boot($me, $api = [])` | Marks the caller present and returns `['me', 'people', 'convos', 'api']` ready for the component. Skips the caller in `people`; includes in `convos` only people with messages (last `pageSize`, plus `unread` and `read`); adds `max_mb` and `last_id` to `api`. |
| `poll($me, $after = 0, $open = null)` | Heartbeat + new messages with id > `$after` (each with `peer`), marks the `$open` conversation read, returns `['msgs', 'last_id', 'read' => [peerId => id], 'presence' => [peerId => [online, seen]]]`. |
| `messages($me, $peer, $before = null)` | One page (oldest first): `['msgs', 'more', 'read']`. |
| `upload($me, $uid, $offset, $chunk)` | Receives one chunk, returns `['received' => bytes]`; throws when the total exceeds `maxMb`. |
| `send($me, $to, $text, $upload = null, $name = null)` | Trims, validates (not yourself, known user, length <= `maxChars`, not empty unless an attachment), completes the upload and returns `['msg' => MSG]`. |
| `read($reader, $peer)` | Marks everything the peer sent (latest page) as read. Normally called through `poll(..., open:)`. |
| `signOut($me)` | Makes the caller offline at once (call it on logout). |

### Routes the application must provide

The project writes thin routes (auth middleware of the project, one line each, `ChatException` mapped to HTTP 422 with `{"message": "..."}`) and passes their addresses in `api`:

| `api` key | Route | Request | Answer |
|---|---|---|---|
| `poll` | `GET` | `?after=<lastId>&open=<peerId>` | `poll()` result |
| `messages` | `GET` | `?peer=<id>&before=<messageId>` | `messages()` result |
| `upload` | `POST` multipart | `uid`, `offset`, `chunk` (file part) | `{received}` |
| `send` | `POST` JSON | `{to, text, upload, name}` | `{msg}` |
| `typing` | `POST` JSON | `{to}` | anything (failures are ignored); only called when the socket is connected |
| `realtime.auth` | `POST` JSON | `{socket_id, channel_name}` | `{auth}` (signed channel permission) |

Other `api` keys: `csrf` (sent as `X-CSRF-TOKEN`; falls back to `window.DL_CSRF`), `last_id` (highest message id already known; `boot()` sets it), `poll_seconds` (default 5), `max_mb` (default 20 in the script, 25 from `boot()`), `chunk` (bytes per upload chunk, default 1 048 576), `realtime` = `['key', 'host', 'port', 'tls' => bool, 'auth' => url, 'channels' => [names]]` (host defaults to the page host) and `realtime_poll_seconds` (default 30).

Every request is `fetch` with `credentials: 'same-origin'`, `Accept: application/json`, `X-Requested-With: XMLHttpRequest`. The route addresses must NOT already contain a query string (the script appends `?after=`, `?peer=` itself).

Real-time frames the server must broadcast on the user's channels (event names): `chat.message {from, to, msg}`, `chat.read {reader, id}`, `chat.typing {from}`, `chat.presence {id, online, seen}`. Broadcasting is the project's job; `ChatService` does not emit anything, and real-time is an optional speed-up, never part of the storage contract.

Project rules for the routes: only the two participants may read a message or download an attachment (serve attachments from a private disk through a route that checks both); `poll` and `upload` should not write the session (so they do not disturb flash data or session expiry); the project owns retention, indexes and the table layout.

### Events and JS API

- `dl:chat-send` (bubbles from the root; `detail = {to, text, file}` with `file` a `File` or `null`): fired for every send, in preview and in LIVE mode, before the network call. In preview it is the hook to simulate an answer.
- `dl:chat-unread` (bubbles; `detail = {count}`): total unread; the Layer Menu and the Layer Shell listen to it for their badges.
- `el.dlChat` on the root: `unread` (getter), `open(personId)`, `send(personId, text)`, `convos` (getter, the live data object).
- `el.dlComponent` is the `Chat` instance (methods such as `conv(id)`, `drawList()`, `drawMain()`, `poll()`); this object sets no `data-dl-js`, so a project that needs different behaviour redefines the class with `Devlib.define('Chat', class extends Devlib.classes.Chat {...})` before the page boots.
- Markup hooks: `data-dl-chat`, `data-wa-list`, `data-wa-head`, `data-wa-msgs`, `data-wa-text`, `data-wa-send`, `data-wa-attach`, `data-wa-file`, `data-wa-case`, `data-wa-new`, `data-wa-newpanel`, `data-wa-q`, `data-wa-nq`, `data-wa-lb`; row / bubble classes `dl-wa__row`, `dl-wa__msg`, `dl-wa__av`, `dl-wa__badge`, `dl-wa__tk`.

### Backend contracts of the Assistant (`Devlib\Assistant`)

The Assistant is a model that "learns" only from the program it is installed in: screens from `Navigator`, help from `Knowledge`, numbers from `Tool`s (usually calculation engines), rights from `Asker`. It is not trained and DevLib stores no knowledge.

| Class | Role | Bound by project |
|---|---|---|
| `Asker` | the person asking: `id()`, `name()`, `level()` (higher = more rights), `can($module)` | yes |
| `Navigator` | `screens($asker)`: screens the asker may open, each `key` (= tab code), `label`, `url`, optional `path`, `words`, `about`, `help` (topic key), `icon` | yes |
| `Knowledge` | `topics($asker)`: Help topics `key`, `title`, optional `purpose`, `words`, `steps` | yes |
| `Tool` | one ability: `key`, `title`, `phrases` (lowercase triggers), `module`, `minLevel`, `answer($asker, $question): Reply` | yes, or `EngineTool` |
| `EngineTool` | wraps an engine of `EngineRegistry` as a tool; only `Calc` and `Suggest` engines are accepted (others throw `AssistantException`); a Suggest result is shown as a draft | used by the project |
| `Classified`, `Sensitivity`, `ValueAsker`, `ScopedValueAsker` | optional sensitivity label of a tool (`Quantity` default, `Value`, `Forbidden`) and the money-value rights of the asker | optional |
| `DataPolicy` | forbidden words, personal-data phrases, protected people (see "Assistant data policy") | optional |
| `LanguageModel`, `NullModel` | optional adapter whose only job is to pick ONE key from at most 5 candidates (`choose($question, $options, $history)`); it never computes, writes SQL or composes text. `NullModel` is the default | optional |
| `HistoryStore`, `MemoryHistory` | per-user history and thumbs (`store`, `recent`, `feedback`); `UNKNOWN` answers and thumbs-down are the material for new Help | optional |
| `Index\*` | background indexer of menu / screen / Help metadata (`IndexSource`, `IndexStore`, `Indexer`, `Budget`, `IndexRunner`, `IndexThrottle`, `IndexAccess`, `IndexAudit`, command `devlib:index`): metadata only, never business rows; money entries keep their NAME only | optional |
| `Reply` | the answer as data: `kind` = `SCREEN`, `HELP`, `NUMBER`, `DENIED`, `UNKNOWN`, `OUT_OF_SCOPE`; `toArray()` gives `kind, title, text, big, rows, steps, note, actions` | no |
| `Assistant` | `ask($asker, $question)`, `topic($asker, $key)`, `history($asker, $limit = 30)`, `feedback($asker, $id, $value)`, `suggestions()` | built by the project |

`new Assistant(navigator, knowledge, tools: [], model: new NullModel, history: null, outOfScope: null, policy: null, index: null)`. Decision order of `ask()` (the question is trimmed and cut to 500 characters):

1. With a `policy`: a question that mentions a forbidden word or a protected person = fixed `DENIED`; a personal-data phrase skips tools, the index and the model.
2. A tool phrase appears in the question (plain substring, lower-case) = module and level gate, then the value gate for `Sensitivity::Value`, then `Tool::answer`. A failing engine becomes `UNKNOWN` with the reason, never an empty catch.
3. Words "how / cara / bagaimana" plus a Help topic that matches well = the steps from `Knowledge`.
4. A screen name matches = `SCREEN` with an Open button (and "How to use" when the screen has a help key).
5. A Help topic matches = its purpose.
6. Not sure and a language model is available = the model picks one candidate.
7. Otherwise `OUT_OF_SCOPE` when the project set `outOfScope` AND not one word of the question exists in the vocabulary of screens, Help and tools (pure word matching, no model); else `UNKNOWN`.

Matching is word overlap (stop words such as "open", "how", "cara", "buka" are ignored; scores 0.7 = sure, 0.45 for "how" topics, 0.34 = candidate). Each `ask()` is stored in `HistoryStore` and `Reply::$id` is used for thumbs up/down.

Routes of the project for the drawer (`assistant` prop of `x-dl::layer-shell`): `POST ask {q, topic}` returns `{id, reply: $assistant->ask(...)->toArray()}` (when `topic` is sent, answer with `$assistant->topic(...)`); `POST feedback {id, value}` (1, -1 or 0) calls `feedback()`. The drawer also understands `reply.refresh` (fires the document event `dl:refresh`) and an action of the form `{label, page}`; `Reply` has no field for these, so the project adds them to the array itself.

## Rules and pitfalls

- Opt-in and neutral: nothing is registered automatically, no project table names and no real names belong in `src/Chat`, `src/Assistant` or the demos. Adapters that name project tables live in the project.
- Only two participants may see a message or an attachment. Never serve attachments from a public URL; the `url` in `file` must point to a route that checks both participants.
- `api.poll` is the live switch. If you pass `api` without `poll`, the chat stays a preview and `send` is never called. Fill `poll`, `messages`, `send` (and `upload` for attachments) together with `csrf`.
- Route addresses must be plain paths without `?`; the script appends its own query string.
- Make the routes answer JSON. A redirect to a login page makes `r.json()` fail and the call is treated as a generic failure; use HTTP 401 / 419 for an ended session so the chat stops polling instead of retrying forever.
- Map `ChatException` to HTTP 422 with `{"message": ...}`; the message is shown to the user as a toast (so keep it user-readable and translated by the project).
- The `uid` of an upload is chosen by the browser (random UUID). Scope your `Uploads` adapter per user (store under the owner id) and validate the file name and type in `complete()`; `ChatService::upload()` does not pass the user to `Uploads::receive()`.
- `poll` is a heartbeat every 5 s per open chat: keep `ChatStore::after()` and `Presence::touch()` cheap (index by user and id). Mark offline on logout with `signOut()`.
- `height` is ignored inside a drawer; for a standalone chat give a fixed height, the lists scroll inside.
- Texts typed by users are escaped by the script; names, roles and file names in `people` / `convos` are escaped too, but `photo` and `file.url` are put into `src` / `href`, so only emit URLs or data URIs you trust.
- Preview data may carry `at`, `day`, `img`, `st`; production data should carry `ts`. Do not mix both in one conversation.
- The Assistant is a selector, not a writer: numbers only from tools, Help text only quoted, no `Auto` / `Flow` engines as tools, answers `Suggest` as drafts. Never create a tool for salary, payroll, costing formulas or personal data, and never label a tool that shows money as `Quantity`.
- The history table of the project stores the question text as typed, including refused ones. Protect access to it.
- Code oddity (not fixed): `src/Chat/ChatService.php:78` takes `$me` in `upload()` but ignores it, and `Uploads::receive()` (`src/Chat/Uploads.php:9`) has no owner parameter, so chunks of two users with the same `uid` would be mixed; the owner is only known at `complete()`.
- Code oddity (not fixed): `src/Chat/ChatService.php:81` checks the size limit AFTER the chunk was already stored by `Uploads::receive()`, so an oversized upload is rejected only once its bytes are on disk (the browser also checks `max_mb` first).
- Code oddity (not fixed): `src/Chat/ChatService.php:148-153` (`lastId()`) calls `ChatStore::after($me, 0)`, i.e. loads every message of the user, on each `boot()` and on each `poll()` (every 5 s per open chat). Implementations of `after()` that cannot be limited will grow slower with history.
- Code oddity (not fixed): `src/Components/Chat.php:84` hard-codes `maxlength="4000"` while `ChatService` takes `maxChars` as a constructor argument, so a custom `maxChars` is not reflected in the input; also the script default `max_mb` is 20 (`resources/assets/js/components/chat.js:24`) against 25 in `ChatService` (only matters when `api` is built without `boot()`).
- Code oddity (not fixed): `src/Components/Chat.php:53` (default `source()`) and `resources/views/components/shell/overlays.blade.php:4` name the tables `sys_chat_conversations / sys_chat_messages + sys_user_presence`, which are project table names in a neutral library.
- Code oddity (not fixed): `site/resources/views/demos/layer-menu.blade.php:119` calls `chat.now()`, but the `Chat` class (`chat.js`) has no `now()` method, so the simulated reply of that demo throws after 1.5 s.
- Code oddity (not fixed): `skills/devlib/chat.md` (route table) lists only `poll`, `messages`, `upload`, `send`; the script also calls `api.typing` and `api.realtime.auth` (`chat.js:300-352`), which are described only in the header of `src/Components/Chat.php`.

## Example

Preview (no server), data produced in the shape of the catalog demo `site/resources/views/demos/chat-model.blade.php`:

```blade
@php
    $chat = \App\Support\ModelDemo::chat();   // ChatService::boot() over MemoryChat, fictional people
@endphp
<x-dl::chat :me="$chat['me']" :people="$chat['people']" :convos="$chat['convos']" height="520px" source="Devlib\Chat\ChatService::boot()" />
```

Compact version in the `chat` slot of the Layer Menu (shape of `demos/layer-menu.blade.php`):

```blade
<x-slot:chat><x-dl::chat compact :me="$chat['me']" :people="$chat['people']" :convos="$chat['convos']" /></x-slot:chat>
```

LIVE mode in a project: bind the service once, add the routes, pass the addresses.

```php
// AppServiceProvider::register()
$this->app->singleton(\Devlib\Chat\ChatService::class, fn ($app) => new \Devlib\Chat\ChatService(
    directory: $app->make(MyDirectory::class),   // implements Directory
    store: $app->make(MyChatStore::class),       // implements ChatStore
    presence: $app->make(MyPresence::class),     // implements Presence
    uploads: $app->make(MyUploads::class),       // optional, implements Uploads (private disk)
));

// routes (project auth middleware; ChatException -> 422 {"message": ...})
Route::get('chat/poll', fn (Request $r) => response()->json(app(ChatService::class)->poll(auth()->id(), (int) $r->query('after'), $r->query('open'))))->name('chat.poll');
Route::get('chat/messages', fn (Request $r) => response()->json(app(ChatService::class)->messages(auth()->id(), $r->query('peer'), $r->query('before') ? (int) $r->query('before') : null)))->name('chat.messages');
Route::post('chat/upload', fn (Request $r) => response()->json(app(ChatService::class)->upload(auth()->id(), $r->input('uid'), (int) $r->input('offset'), file_get_contents($r->file('chunk')->getRealPath()))))->name('chat.upload');
Route::post('chat/send', fn (Request $r) => response()->json(app(ChatService::class)->send(auth()->id(), $r->input('to'), (string) $r->input('text'), $r->input('upload'), $r->input('name'))))->name('chat.send');
```

```blade
@php
    $chat = app(\Devlib\Chat\ChatService::class)->boot(auth()->id(), [
        'poll' => route('chat.poll'), 'messages' => route('chat.messages'), 'upload' => route('chat.upload'), 'send' => route('chat.send'),
        'csrf' => csrf_token(), 'poll_seconds' => 5,
    ]);
@endphp
<x-dl::chat :me="$chat['me']" :people="$chat['people']" :convos="$chat['convos']" :api="$chat['api']" height="600px" />
```

On logout call `app(\Devlib\Chat\ChatService::class)->signOut(auth()->id())` so the person goes offline at once.

Assistant wiring (project, with the data policy installed):

```php
$this->app->singleton(\Devlib\Assistant\Assistant::class, fn ($app) => new \Devlib\Assistant\Assistant(
    navigator: $app->make(MyNavigator::class),
    knowledge: $app->make(MyKnowledge::class),
    tools: [new \Devlib\Assistant\EngineTool($app->make(\Devlib\Engine\EngineRegistry::class), 'reorder-point',
        ['reorder point'], fn ($asker, $q) => ['item' => 'SKU-001'], module: 'inventory')],
    history: $app->make(MyHistoryStore::class),
    outOfScope: 'I only know this application.',
    policy: new \Devlib\Assistant\DataPolicy(extra: ['bonus'], protected: ['Sample Owner', 'sowner']),
));
```

## Related

- `x-dl::layer-menu`: hosts one compact chat in its `chat` slot (swap with the menu or dock right) and shows the unread badges from `dl:chat-unread`.
- `x-dl::layer-shell`: the Chat drawer (prop `chat`) and the AI Assistant drawer (prop `assistant`, routes `ask` and `feedback`); relays `dl:chat-unread` as the badge on the avatar.
- `x-dl::engine-catalog`: lists the engines that `EngineTool` turns into assistant tools.
- `x-dl::feed`, `x-dl::review-feed`, `x-dl::notification-list`: comments and notifications, which are not chat.
- `skills/devlib/chat.md`, `skills/devlib/assistant.md`, `docs/assistant-indexer.md`: the rules (contracts, data policy, indexer); tests `site/tests/Feature/AssistantChatContractTest.php`.
