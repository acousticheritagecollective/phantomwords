
<img width="2344" height="1546" alt="phantomwords-2026-07-03T22-16-52" src="https://github.com/user-attachments/assets/e12be14c-7751-4e89-bd9f-5740eff109da" />

## TEST AT
AUDIENCE WORD TRANSMITTER: https://acousticheritagecollective.org/phantomwords/audience.html
ARTIST WORD RECEIVER: https://acousticheritagecollective.org/phantomwords/artist.html 

# Phantom Words — behaviour guide

**Ginebra Raventós — Phantom Words**

Files on the host, all in the same `phantomwords/` folder:
`audience.html` (the public's phone page) · `artist.html` (your console + projection)
· `api.php` (backend, PHP + SQLite, zero configuration — creates
`phantomwords.sqlite` by itself) · `.htaccess` (protects the data file)
· `bg.jpeg` (audience background image, optional) · your self-hosted font files.

All visual behaviour lives in **`artist.html`**, inside the `<script>` block.
Per-word personality: **`ensureNode()`** · global rendering: **`frame()`** ·
colour palettes: the **`THEMES`** object · realtime: **`poll()`**.
Edit, save, reload. Change ONE number at a time.

---

## 0. The console at a glance

Sidebar, top to bottom: title · live stats (received / unique / **link** — ON
green, OFF red) · **Session Name** and **Location** boxes (whatever you type is
printed live in small italic serif at the bottom-left of the projection, and is
included in PNG snapshots) · activity log · repetition ranking · button bar.

Buttons: **CSV / TXT** export the ranking · **PNG** instant snapshot (full
resolution, active theme, includes the session caption) · **Timeline / Cloud**
switches display mode (§3) · **◐** inverts colours (§1) · **Reset** (two clicks)
wipes the database, the screen, the thread, the time span and the session boxes.

## 1. Colour themes & ◐

Starts in the **light theme** (`#e9e9eb` background, words in `#28282a` at
varying opacity). ◐ switches live to dark (`#28282a` background, light greys).
Start dark instead: `let inverted = true;` → `false`.

Every canvas colour lives in the `THEMES` object — background, trail fade, fog,
constellation lines, trajectory traces, timeline thread, glitches. Retint the
piece by editing only that block.

- **Light theme hierarchy = opacity**: once-seen words faint (base 0.18–0.40,
  random per word), champion reaches 0.95, never solid.
- **Dark theme hierarchy = brightness**: random base grey (`shade`, §8)
  brightening with repetitions, capped at 200, never white.

The audience page shares the light palette via the CSS variables at the top of
its `<style>` (currently all-black ink at the artist's request).

## 2. Movement

In `ensureNode()`:

```js
speed: .00005 + Math.random() * .00010,   // very slow (current)
wobble: .005 + Math.random() * .012,      // travel distance (short)
```

| feeling         | speed range                     |
|-----------------|---------------------------------|
| almost frozen   | `.00002 + Math.random()*.00004` |
| current (slow)  | `.00005 + Math.random()*.00010` |
| lively          | `.0004  + Math.random()*.0008`  |

In `frame()` — free wander, no constant orbit:

```js
const wob = Math.sin(t * nd.speed + nd.phase) * nd.wobble;   // in/out pulse
const angDrift = nd.dir * (                                  // two overlapping
    Math.sin(t * nd.speed * 0.60 + nd.phase * 2) * 0.05      // swings, bounded:
  + Math.sin(t * nd.speed * 0.23 + nd.phase2)    * 0.08      // short strokes,
);                                                           // never a circle
```

`nd.dir` (random `+1`/`-1`) gives each word its own turning sense — half swing
one way, half the other, all reversing naturally. Raise `0.05`/`0.08` for wider
arcs, lower for tighter scribbles.

Mode-switch / ranking-change easing: `nd.px += (tx - nd.px) * 0.035;` —
`0.01` ceremonial migrations, `0.1` snappy.

## 3. Cloud mode vs Timeline mode

**Cloud (default)** — rank maps to distance from centre with an equal-area
spread, stretched to the full screen through a **superellipse** boundary:

```js
const base = n <= 1 ? 0 : Math.sqrt(i / (n - 1));            // equal-area
nd.targetR = Math.min(1, Math.max(0, base * 0.92 + nd.jitter));
...
MAXRX = W * 0.46;  MAXRY = H * 0.44;    // reach: full width AND height
const P = 3.5;   // 2 = ellipse · 3.5 = rounded rectangle · 8 = almost rect
```

- `Math.sqrt` keeps the outer rings populated and separates the top words from
  the exact centre (plain `x` = old centre-heavy look).
- `nd.jitter` (`±0.1`, set in `ensureNode()`) breaks the rings formed by
  equal-count words — raise for messier, `0` for exact rings.
- Raise `P` to push words into the corners; `0.92` = outer margin.

**Timeline** — X is fixed by each word's **first-arrival timecode**, scaled from
the session's first word (left) to the latest (right); the axis rescales as new
words arrive. All movement lives on the Y axis:

```js
tx = W * 0.05 + span * W * 0.90;          // horizontal margins of the axis
const offY = Math.sin(nd.baseAngle * 3);  // fixed per-word lane
ty = CY + ( offY * 0.34                   // lane spread around the axis
          + wob * 3                       // vertical breathing
          + angDrift * 0.5 ) * MAXRY;     // vertical wander
```

A faint horizontal axis is drawn at `theme().lines(0.15)`. Switching modes
morphs smoothly (the easing above) — the transition is performable.

## 4. The timeline thread (permanent lines)

Every arrival draws a straight line from the previous word to the new one —
**it never fades**; the thread is the session's chronological record and only
Reset clears it. Drawn each frame between the words' live positions:

```js
ctx.strokeStyle = theme().lines(0.3);     // thread opacity
ctx.lineWidth = devicePixelRatio * 0.7;   // thickness
if (links.length > 2000) links.shift();   // safety cap only
```

Consecutive repeats of the same word draw no line (the glitch burst covers it).

## 5. Trajectory traces (fine path lines)

Each word samples its position every 200 ms and draws a thin line through its
last 40 samples (~8 s), crisp at the word, fading at the tail:

```js
if (t - nd.lastSample > 200){ ... }        // sampling interval
if (nd.hist.length > 40) nd.hist.shift();  // samples kept (40 × 200ms = 8s)
ctx.strokeStyle = theme().trace(0.22 * fade);   // opacity at the fresh end
```

Longer memory: raise `40`; ghostlier: lower `0.22`; disable: delete the
`if (nd.hist.length > 1){ ... }` block.

## 6. Ghost trails / motion blur

The `trail` value in each theme — the last number is per-frame erasure:
`0.04` long smears · `0.10` current · `0.25` crisp · `1.0` off. Its RGB must
equal that theme's background or trails smear toward the wrong colour.

## 7. Word size

```js
const rel  = maxCount > 1 ? (nd.count - 1) / (maxCount - 1) : 0;
const size = (12 + Math.pow(rel, 0.55) * 65) * devicePixelRatio;
```

`rel`: 0 = word seen once → 1 = current champion. `12` = minimum px · `65` =
champion's extra (max ≈ 77 px) · `0.55` = curve (`1.0` linear, lower inflates
mid-ranked words — current — `1.5` reserves bigness for champions).
`rel` also drives opacity/brightness.

## 8. Per-word randomness (assigned once, kept forever)

```js
t0: tc,                                   // first-arrival timecode (timeline X)
baseAngle: Math.random() * Math.PI * 2,   // direction from centre / lane seed
rot: (Math.random() - .5) * 0.07,         // resting tilt ±2°; 0 = horizontal
dir: Math.random() < .5 ? -1 : 1,         // turning sense
jitter: (Math.random() - .5) * 0.2,       // radial offset ±10%
font: FONTS[...],
shade: 60 + (Math.random() * 80) | 0      // dark: base grey · light: base opacity
```

Animated tilt on top: `* 0.03` in `ctx.rotate(...)` = ±1.7° breathing sway.

## 9. Fonts

The pool = 10 system fonts + 11 self-hosted (Sofia Pro Light, Chapbook,
BLHelium BoldCond, BLHelium BookWide, Roboto Medium, GT America Compressed
Light, Roboto Condensed Light, Letter Gothic Std, Myriad Pro, Bodoni 72
Oldstyle, Saga). Each word picks one at random on first appearance, forever.

**Adding a self-hosted font**: drop the file in `phantomwords/`, then in
`artist.html`: (1) an `@font-face` at the top of `<style>`:

```css
@font-face{font-family:'MyFont';src:url('myfont.woff2') format('woff2');font-display:swap;}
```

(2) add `"'MyFont'"` to the `FONTS` array. The preload line
(`FONTS.slice(10).forEach(...)`) fetches every entry after the first 10
automatically — keep custom fonts after the system ones. Filenames are
**case-sensitive** on the server. FontAwesome was deliberately excluded — it's
an icon font, words would render as blank boxes.

Curation tip: delete the system-font lines to let only your 11 speak.

## 10. Constellation lines, fog & glitches

```js
const link = MAXR * 0.35;                 // constellation: max distance for a line
theme().lines(0.05 * (1 - d/link))        // 0.05 = max opacity
```

```js
Array.from({length:4}, ...)               // fog patches
theme().fog                                // strength (light 0.03 · dark 0.012)
```

Glitches — two words arriving within `GLITCH_WINDOW = 3000` ms fire a jagged
discharge between them (same word twice → radial burst): `glitches.length < 5`
max simultaneous · `life: 500 + Math.random()*500` · flicker `0.75` ·
jaggedness `40` px · opacities `0.55` line / `0.18` scanlines. Busy audience →
shrink the window to ~1000 for rare, striking events.

## 11. Realtime, rate limits & the live show

```js
let pollDelay = 2000;                     // console polls the server every 2s
pollDelay = Math.min(pollDelay*2, 15000); // on errors backs off 4s → 8s → 15s
```

On **429 Too Many Requests** (shared-hosting per-IP limit) the console backs
off automatically and the audience page shows "the room is crowded — wait a
moment and try again". Know this for the show: hosts rate-limit **per IP**, and
an audience on the venue WiFi shares one public IP. In order of effort: ask the
audience to use **mobile data** (each phone = own IP, put it next to the QR);
ask your host to raise the limit for this folder; or move `api.php` to a small
VPS. PHP + SQLite itself handles a 100-person audience fine — the ceiling is
the host's limiter, not the database.

**Pre-show checklist**: upload all files + fonts + `bg.jpeg` · open artist.html
on the show machine, check link = ON and all fonts load (F12 → Network, all
200) · send a test word from a phone on mobile data · fill Session Name /
Location · Export CSV of the test, then Reset · rehearse the Timeline ↔ Cloud
morph and ◐. During: PNG for captures. After: CSV/TXT before Reset — the
export always captures the current state.

## 12. UI text sizes (CSS)

**Artist console** (`artist.html`): title `aside header h1` 14px · subtitle
`aside header p` 10px · stats `.stats b` 18px / `.stats span` 9px · section
labels `.zone-label` 9px · session boxes `.session input` 11px · log `#log`
11px · ranking `#rank table` 12px / words `#rank td.w` 13px · buttons
`.export button` 11px · waiting text `#empty` 22px. Sidebar lines: `--line`
(currently `#85858f`) · bright labels: `#c9c9d1` · session caption on the
projection: `13px Georgia italic`, bottom-left, in the caption block at the end
of `frame()`.

**Audience** (`audience.html`): eyebrow `.eyebrow` 17px · title `h1`
`clamp(13px,4vw,20px)` · intro `.intro` 16px · rules `.rules` 12px · textarea
20px (keep ≥16px or iPhones auto-zoom) · SEND `button` 14px · status 12px ·
footer 10px. All ink `#000`; textarea background `#fff`; background image
`bg.jpeg` under a veil — the `.82` in the body's `linear-gradient` (lower =
more image).

---

## Quick recipes

**"Séance"** — speed `.00002–.00006`, trail `0.05`, trace samples `80`, easing `0.01`.

**"Nervous swarm"** — speed `.0004–.0012`, wobble `.02–.06`, trail `0.15`,
`GLITCH_WINDOW 5000`.

**"Clean typographic poster"** — speed ≈ `0`, trail `1.0`, delete fog +
constellation + glitch + trace blocks, `rot: 0`, single font. The timeline
thread alone over this, in the light theme, is a beautiful minimal variant.
