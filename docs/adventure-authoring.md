# Authoring the Adventure

The Adventure is a branching, click-through story. The content lives in **one
file** — `resources/adventure/story.json` — so a writer can edit it directly, or
author in **Twine** and export/convert to this shape.

The story is rendered by an accessible engine (`resources/js/adventure.js`); you
never touch code to add or change scenes.

## The format

```json
{
  "title": "The Quiet Path",
  "start": "clearing",
  "scenes": {
    "clearing": {
      "title": "A Clearing in the Woods",
      "text": ["First paragraph.", "Second paragraph."],
      "choices": [
        { "label": "Follow the mossy path", "to": "stream" },
        { "label": "Follow the open path", "to": "meadow" }
      ]
    },
    "rest": {
      "title": "A Moment of Rest",
      "text": ["You settle in."],
      "ending": true
    }
  }
}
```

- **`title`** — the story name (shown as the page heading).
- **`start`** — the id of the first scene.
- **`scenes`** — an object keyed by scene id. Each scene has:
  - **`title`** — the scene heading (focus moves here on arrival).
  - **`text`** — an array of paragraphs.
  - **`choices`** — a list of `{ "label", "to" }`, where `to` is another scene id.
  - **`ending`** — set `true` for a scene with no choices (a stopping point).

## Writing style

Follow [the Capitalize Key Terms style](writing-style.md). Keep choices short,
literal, and never time-pressured — there are **no wrong choices**.

## Safety net

Every scene's choices must point to a scene that exists, and `start` must exist.
A test (`Story::validate()`) checks this, so if a link is broken the CI build
fails with a clear message before it can ship. Add scenes freely — just keep the
ids matched up.

## Later

The engine is framework-agnostic and can move to a static `play.neuroresource.org`
build without rewriting the content. Account-based cross-device save is a planned
future increment (progress is currently saved in the browser).
