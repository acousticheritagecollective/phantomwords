
<img width="2344" height="1546" alt="phantomwords-2026-07-03T22-16-52" src="https://github.com/user-attachments/assets/e12be14c-7751-4e89-bd9f-5740eff109da" />


# Phantom Words — cloud behaviour guide

All visual behaviour lives in **`artist.html`**, inside the `<script>` block.
Per-word personality: **`ensureNode()`** · global rendering: **`frame()`** ·
colour palettes: the **`THEMES`** object · realtime: the **`poll()`** function.
Edit with any text editor, save, reload the page. No other file needs touching.

Tip: change ONE number at a time and reload — fastest way to build intuition.

---

## 1. Colour themes & the ◐ invert button

The page **starts in the light theme** (whitish background `#e9e9eb`, words in
`#28282a` at varying opacity). The ◐ button switches live to the dark theme
(background `#28282a`, words in light greys). To start dark instead, change:

```js
let inverted = true;   // light theme by default  →  false = start dark
```

Every canvas colour (background, trail fade, fog, constellation lines, trajectory
traces, glitches) comes from the `THEMES` object — retint the piece by editing
only that block.

- **Light theme hierarchy = opacity**: once-seen words are faint (base 0.18–0.40,
  random per word), the most repeated word reaches 0.95, never fully solid:

```js
const base = 0.18 + (nd.shade - 60) / 80 * 0.22;
ctx.globalAlpha = Math.min(0.95, base + rel * 0.6);
```

- **Dark theme hierarchy = brightness**: random base grey (`shade`, §9) that
  brightens with repetitions, capped at 200 (never pure white).

The PNG snapshot always captures whichever theme is active. The sidebar console
keeps its own dark styling in both themes — only the cloud inverts.

## 2. Speed of movement

In `ensureNode()`:

```js
speed: .00005 + Math.random() * .00010,   // current: very slow
```

| feeling         | try                             |
|-----------------|---------------------------------|
| almost frozen   | `.00002 + Math.random()*.00004` |
| current (slow)  | `.00005 + Math.random()*.00010` |
| previous (calm) | `.00012 + Math.random()*.00025` |
| lively          | `.0004  + Math.random()*.0008`  |

Relative speeds of each motion, in `frame()`:

```js
const wob = Math.sin(t * nd.speed + nd.phase) * nd.wobble;   // in/out pulse
const angDrift = nd.dir * (                                  // free wander:
    Math.sin(t * nd.speed * 0.60 + nd.phase * 2) * 0.05      //  two overlapping
  + Math.sin(t * nd.speed * 0.23 + nd.phase2)    * 0.08      //  swings, bounded
);
const ang = nd.baseAngle + angDrift;                         // no constant orbit
ctx.rotate(nd.rot + Math.sin(t * nd.speed * 0.4 + nd.phase) * 0.03);  // tilt wobble
```

There is **no constant rotation**: each word meanders back and forth in short
strokes. `nd.dir` (set randomly to `+1` or `-1` in `ensureNode()`) gives every
word its own turning sense, so trajectories go both ways. The `0.05` and `0.08`
are the two swing amplitudes in radians — raise them for wider arcs, lower for
tighter scribbles. To bring back a slow collective orbit, add
`+ t * nd.speed * 0.08` to `ang` (multiply by `nd.dir` to keep mixed directions).

## 3. Amplitude / travel distance

- `wobble: .005 + Math.random() * .012` in `ensureNode()` → in/out travel as a
  fraction of the cloud radius (currently short). Double for floatier words.
- `* 0.03` inside `ctx.rotate(...)` → tilt wobble in radians.

## 4. Trajectory traces (the fine path lines)

Each word records its position every 200 ms and draws a thin line through its
last 40 samples (~8 s of path), crisp at the word and fading to nothing at the
tail. In `frame()`, inside the word loop:

```js
if (t - nd.lastSample > 200){ ... }        // 200 = sampling interval (ms)
if (nd.hist.length > 40) nd.hist.shift();  // 40 = samples kept → 40×200ms = 8s of path
ctx.lineWidth = devicePixelRatio * 0.6;    // line thickness
ctx.strokeStyle = theme().trace(0.22 * fade);   // 0.22 = max opacity at the fresh end
```

- Longer memory: raise `40` (and/or the `200` ms) — e.g. `80` samples ≈ 16 s.
- More visible: raise `0.22`; ghostlier: lower it.
- Trace colour per theme: the `trace:` entries in `THEMES`.
- Disable: delete the whole `if (nd.hist.length > 1){ ... }` block.

## 5. Ghost trails / motion blur

The `trail` value inside each theme, e.g. `"rgba(233,233,235,0.10)"`.
The **last number** is how strongly each frame erases the previous one:
`0.04` long smears · `0.10` current · `0.25` crisp · `1.0` off.
The RGB must equal that theme's background (light: 233,233,235 · dark: 40,40,42)
or trails smear toward the wrong colour.

## 6. Word size (currently −30%)

```js
const rel  = maxCount > 1 ? (nd.count - 1) / (maxCount - 1) : 0;
const size = (12 + Math.pow(rel, 0.55) * 65) * devicePixelRatio;
```

`rel` runs 0 (word seen once) → 1 (current most-repeated word).

- `12` → px size of a once-seen word
- `65` → extra px for the champion, max ≈ 77 px
- `0.55` → the curve: `1.0` linear · lower inflates mid-ranked words (current,
  so words with a few repetitions are clearly visible) · `1.5` only champions huge

`rel` also drives opacity/brightness, so colour follows the same curve.

## 7. Placement (centre ↔ edges)

In `rebuild()`:

```js
const base = n <= 1 ? 0 : Math.sqrt(i / (n - 1));           // equal-area spread
nd.targetR = Math.min(1, Math.max(0, base * 0.92 + nd.jitter));
```

- `Math.sqrt(...)` distributes words by **equal area** instead of equal radius:
  without it, the top words pile onto the centre and the outer rings look empty.
  Replace `sqrt(x)` with `x` for the old centre-heavy look, or `Math.pow(x, 0.7)`
  for something in between.
- `0.92` keeps words slightly off the extreme edge (lower = more margin).
- `nd.jitter` is a fixed per-word radial offset (`±0.06`, set in `ensureNode()`)
  that breaks the perfect rings otherwise formed by words with equal counts —
  currently `±0.1` (factor `0.2`); raise for a messier spread, `0` for exact rings.

Cloud reach, in `resize()`: `MAXRX = W * 0.46` (horizontal) and
`MAXRY = H * 0.44` (vertical) — the cloud stretches to the full screen, not a
circle. In `frame()`, the boundary is a **superellipse**:

```js
const P = 3.5;   // 2 = pure ellipse · 3.5 = rounded rectangle · 8 = almost rect
```

Raise `P` to push words deeper into the corners; `2` restores a smooth oval.
`MAXR` still exists for fog and constellation-line scale.

Glide when the ranking changes, in `frame()`:
`nd.r += (nd.targetR - nd.r) * 0.01;` — currently ceremonial; `0.05` snappier.

## 8. Glitch discharges (two words within 3 s)

Two words arriving less than `GLITCH_WINDOW` ms apart fire a glitch between them:
jagged lightning re-randomised every frame, flicker dropout, scanline slashes.
The *same* word twice within the window → radial burst around it instead.

```js
const GLITCH_WINDOW = 3000;              // coincidence window (ms)
glitches.length < 5                      // max simultaneous
life: 500 + Math.random() * 500          // flash duration (ms)
const flick = Math.random() < 0.75;      // fraction of frames visible
const off = (Math.random()-.5) * 40 ...  // jaggedness (px)
theme().glitch(0.55 * k)                 // line opacity · slashes 0.18
```

Busy audience → coincidences become constant; shrink the window to ~1000 or cap
to 2 for rare, striking events. Delete the `/* glitch discharges */` block in
`frame()` to disable.

## 9. Per-word randomness (assigned once, kept forever)

In `ensureNode()`:

```js
baseAngle: Math.random() * Math.PI * 2,   // direction from centre
rot: (Math.random() - .5) * 0.07,         // resting tilt ±2°; 0 = all horizontal
font: FONTS[...],                          // random pick from the FONTS list
shade: 60 + (Math.random() * 80) | 0      // dark theme: base grey 60–140
                                          // light theme: feeds base opacity
```

Edit the `FONTS` array for the typographic mix — one entry = uniform cloud.

### Changing the cloud's font list

The pool lives near the top of the script in `artist.html`:

```js
const FONTS = [
  "Georgia","'Times New Roman'","'Courier New'","Verdana","'Trebuchet MS'",
  "Palatino","Garamond","'Arial Black'","'Brush Script MT'","'Lucida Console'"
];
```

Each word picks one at random on first appearance and keeps it forever.
Add or remove entries freely; names containing spaces need the inner quotes,
exactly like `"'Times New Roman'"`. These are *system* fonts — they must exist
on the machine running the artist page, otherwise the browser silently
substitutes a default. Check the mix once on the actual show computer.

### Using your own font files (self-hosted, guaranteed identical everywhere)

Drop the font files (`.woff2` is best, `.ttf`/`.otf` also work) into the
`phantomwords/` folder on your host, then do two things in `artist.html`:

**1.** Declare each font at the top of the `<style>` block:

```css
@font-face{
  font-family:'MiFuente';
  src:url('mifuente.woff2') format('woff2');
}
```

**2.** Add its name to the `FONTS` array — and preload it so the canvas can use
it from the very first frame (canvas text does NOT trigger font loading by
itself). Right after the `FONTS` array, add one line per custom font:

```js
document.fonts.load("20px 'MiFuente'");
```

Repeat the pair for every font file. Free fonts to self-host legally:
fonts.google.com (download the family, take the .ttf, or convert to .woff2
with any online converter). Self-hosting keeps the piece identical on any
computer and works even without internet at the venue.

## 10. Constellation lines & fog

```js
const link = MAXR * 0.35;                 // max distance for a constellation line
theme().lines(0.05 * (1 - d/link))        // 0.05 = max line opacity (try 0.12)
```
Delete the double `for` loop over `pts` to remove lines.

```js
Array.from({length:4}, ...)               // fog patches
theme().fog                                // fog strength (light: 0.03 · dark: 0.012)
s: 15000 + ...                             // drift speed (bigger = slower)
r: .25 + ...                               // patch size (fraction of cloud radius)
```
Fog accumulates through the trail effect — tiny changes are visible.

## 11. Realtime, rate limits & housekeeping

```js
let pollDelay = 2000;                     // artist page asks the server every 2s
pollDelay = Math.min(pollDelay*2, 15000); // on errors: backs off 4s → 8s → 15s
```

If the server answers **429 Too Many Requests** (shared-hosting per-IP limit),
the artist page backs off automatically and the audience page shows
"the room is crowded — wait a moment and try again" instead of failing.

For a live show, know this: shared hosts rate-limit **per IP address**. A single
person testing everything from one machine can trip it; more importantly, an
audience on the **venue WiFi shares one public IP** and can trip it together.
In rough order of effort: ask the audience to use **mobile data** (each phone =
its own IP); ask your hosting provider to raise/disable the limit for this
folder; or move `api.php` to a small VPS or a realtime service. PHP + SQLite
itself handles a 100-person audience without trouble — the ceiling is the
host's limiter, not the database.

Other knobs:
- `while (logEl.children.length > 200)` → lines kept in the activity log
- `setTimeout(disarm, 4000)` → reset-confirmation window
- Buttons: CSV / TXT export the ranking · PNG saves an instant snapshot (full
  resolution, opaque background, active theme) · ◐ inverts colours ·
  Reset (two clicks) wipes the database and the screen

## 12. UI text sizes (CSS)

These are interface sizes, independent from the cloud (§6). All in the
`<style>` block of each file — find the selector, change the `font-size`.

**Artist console (`artist.html` sidebar):**

| element                    | selector             | current            |
|----------------------------|----------------------|--------------------|
| "phantom words" title      | `aside header h1`    | `font-size:20px`   |
| subtitle under it          | `aside header p`     | `font-size:10px`   |
| big numbers (received…)    | `.stats b`           | `font-size:18px`   |
| labels under the numbers   | `.stats span`        | `font-size:9px`    |
| section labels             | `.zone-label`        | `font-size:9px`    |
| activity log lines         | `#log`               | `font-size:11px`   |
| ranking table numbers      | `#rank table`        | `font-size:12px`   |
| ranking table words        | `#rank td.w`         | `font-size:13px`   |
| bottom buttons             | `.export button`     | `font-size:11px`   |
| "waiting for the first…"   | `#empty`             | `font-size:22px`   |

**Audience page (`audience.html`):**

| element                  | selector      | current                       |
|--------------------------|---------------|-------------------------------|
| "Ginebra Raventós" line  | `.eyebrow`    | `font-size:11px`              |
| main title               | `h1`          | `clamp(26px,8vw,40px)`        |
| invitation paragraph     | `.intro`      | `font-size:16px`              |
| instruction lines        | `.rules`      | `font-size:12px`              |
| the text box             | `textarea`    | `font-size:20px`              |
| SEND button              | `button`      | `font-size:14px`              |
| status message           | `.status`     | `font-size:12px`              |
| footer line              | `footer`      | `font-size:10px`              |

The title uses `clamp(min, preferred, max)`: it scales with the phone width
(`8vw` = 8% of screen width) but never below 26px nor above 40px — adjust any
of the three values. Keep `textarea` at 16px or larger: below that, iPhones
auto-zoom into the page when the box is tapped.

The audience page shares the cloud's light palette (`#e9e9eb` background, ink
in opacities of `#28282a`) via the CSS variables at the top of its `<style>`.

---

## Quick recipes

**"Séance" — near-still, long memory:** speed `.00002–.00006`, trail `0.05`,
trace samples `80`, glide `0.005`.

**"Nervous swarm":** speed `.0004–.0012`, wobble `.02–.06`, trail `0.15`,
`GLITCH_WINDOW 5000`.

**"Clean typographic poster":** speed ≈ `0`, trail `1.0`, delete fog +
constellation + glitch + trace blocks, `rot: 0`, single font — works beautifully
in the default light theme.
