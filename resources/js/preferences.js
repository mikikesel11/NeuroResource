/*
 | NeuroScouts — Accessibility preferences (client side).
 |
 | Applies and persists the user's display preferences (theme, text size, line
 | height, letter spacing, reduced motion) to <html> as data-* attributes and
 | CSS custom properties. Persisted to localStorage immediately for a flicker-
 | free experience; logged-in users can additionally sync server-side via the
 | preferences endpoint (wired in a later increment). See design §3.5.
 |
 | The matching read-before-paint snippet lives in the layout <head> so the
 | correct theme is applied before first paint (no flash of default theme).
 */

const STORAGE_KEY = 'ns:preferences';

const DEFAULTS = {
  theme: null, // null => follow OS (prefers-color-scheme)
  textScale: 1,
  lineHeight: 1.6,
  letterSpacing: 0,
  reduceMotion: null, // null => follow OS (prefers-reduced-motion)
};

export function loadPreferences() {
  try {
    return { ...DEFAULTS, ...JSON.parse(localStorage.getItem(STORAGE_KEY) || '{}') };
  } catch {
    return { ...DEFAULTS };
  }
}

export function applyPreferences(prefs = loadPreferences()) {
  const root = document.documentElement;

  const theme =
    prefs.theme ??
    (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
  root.setAttribute('data-theme', theme);

  const reduceMotion =
    prefs.reduceMotion ??
    window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  root.setAttribute('data-reduce-motion', reduceMotion ? 'true' : 'false');

  root.style.setProperty('--ns-text-scale', String(prefs.textScale));
  root.style.setProperty('--ns-line-height', String(prefs.lineHeight));
  root.style.setProperty('--ns-letter-spacing', `${prefs.letterSpacing}em`);
}

export function savePreferences(update) {
  const next = { ...loadPreferences(), ...update };
  localStorage.setItem(STORAGE_KEY, JSON.stringify(next));
  applyPreferences(next);
  return next;
}

// Convenience API exposed to the accessibility widget (see public-layout).
const TEXT_MIN = 0.85;
const TEXT_MAX = 1.6;

window.nsPrefs = {
  load: loadPreferences,
  apply: applyPreferences,
  save: savePreferences,
  setTheme: (theme) => savePreferences({ theme }), // pass null to follow OS
  toggleMotion: () => savePreferences({ reduceMotion: !loadPreferences().reduceMotion }),
  adjustText: (delta) => {
    const next = +(loadPreferences().textScale + delta).toFixed(2);
    return savePreferences({ textScale: Math.min(TEXT_MAX, Math.max(TEXT_MIN, next)) });
  },
  reset: () => {
    localStorage.removeItem('ns:preferences');
    applyPreferences();
  },
};

// Apply as early as possible on load.
applyPreferences();
