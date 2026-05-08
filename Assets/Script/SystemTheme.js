const THEME_STORAGE_KEY = "ojt_theme_mode";
const VALID_THEME_MODES = ["light", "dark", "auto"];
const DEFAULT_THEME_MODE = "dark";

let ThemeColor = null;
let sameTheme = DEFAULT_THEME_MODE;
let systemThemeListener = null;
let systemThemeQuery = null;

function normalizeThemeMode(themeMode = DEFAULT_THEME_MODE) {
  const normalized = String(themeMode || "").toLowerCase().trim();
  return VALID_THEME_MODES.includes(normalized) ? normalized : DEFAULT_THEME_MODE;
}

function getSystemThemeColor() {
  return window.matchMedia("(prefers-color-scheme: dark)").matches ? "dark" : "light";
}

function applyResolvedTheme(themeMode = DEFAULT_THEME_MODE) {
  const normalizedMode = normalizeThemeMode(themeMode);
  const resolvedTheme = normalizedMode === "auto" ? getSystemThemeColor() : normalizedMode;

  document.documentElement.setAttribute("data-bs-theme", resolvedTheme);
  document.documentElement.setAttribute("data-theme-mode", normalizedMode);
  ThemeColor = resolvedTheme;
  sameTheme = normalizedMode;

  return resolvedTheme;
}

function bindSystemThemeListener() {
  systemThemeQuery = systemThemeQuery || window.matchMedia("(prefers-color-scheme: dark)");

  if (systemThemeListener) {
    systemThemeQuery.removeEventListener("change", systemThemeListener);
  }

  systemThemeListener = () => {
    if (sameTheme === "auto") {
      applyResolvedTheme("auto");
    }
  };

  systemThemeQuery.addEventListener("change", systemThemeListener);
}

export function GetThemeMode() {
  return normalizeThemeMode(localStorage.getItem(THEME_STORAGE_KEY) || sameTheme);
}

export function SetThemeMode(themeMode = DEFAULT_THEME_MODE, persist = true) {
  const normalizedMode = normalizeThemeMode(themeMode);

  if (persist) {
    localStorage.setItem(THEME_STORAGE_KEY, normalizedMode);
  }

  const resolvedTheme = applyResolvedTheme(normalizedMode);
  bindSystemThemeListener();
  return {
    mode: normalizedMode,
    resolvedTheme,
    swalTheme: resolvedTheme === "dark" ? "bootstrap-5-dark" : "bootstrap-5-light",
  };
}

export function MatchsystemThemes(isEnabled = true, ForceDefaultTheme = DEFAULT_THEME_MODE) {
  if (!isEnabled) {
    return SetThemeMode(ForceDefaultTheme, false).mode;
  }

  return SetThemeMode(GetThemeMode(), false).mode;
}

export function SwalTheme() {
  const resolvedTheme = ThemeColor || SetThemeMode(GetThemeMode(), false).resolvedTheme;
  return resolvedTheme === "dark" ? "bootstrap-5-dark" : "bootstrap-5-light";
}

export function BGcircleTheme(isEnabled = true, themeVersion = "primary", animationSpeed = "normal") {
  if (!isEnabled) {
    return;
  }

  const validThemes = ["primary", "success", "danger", "warning", "light", "dark", "cv"];
  const validSpeeds = ["slow", "normal", "fast"];
  if (!validSpeeds.includes(animationSpeed.toLowerCase())) {
    animationSpeed = "normal";
  }

  const targetTheme = validThemes.includes(themeVersion.toLowerCase()) ? themeVersion.toLowerCase() : "primary";

  const $circles = [$(".circle1"), $(".circle2"), $(".circle3")];

  $circles.forEach(($circle, index) => {

    const dataSpeed = $circle.attr("data-speed");
    const isNormal = animationSpeed.toLowerCase() === "normal";
    if (isNormal) {
      if (dataSpeed) $circle.removeAttr("data-speed");
    } else if (dataSpeed !== animationSpeed.toLowerCase()) {
      $circle.attr("data-speed", animationSpeed.toLowerCase());
    }

    const currentClass = `circle${index + 1}-${targetTheme}`;
    if ($circle.hasClass(currentClass)) return;

    $circle.removeClass(function (i, className) {
      return className.match(/circle\d+-(success|danger|warning|light|dark|cv|primary)/g)?.join(" ") || "";
    });

    $circle.addClass(`circle${index + 1}-${targetTheme}`);
  });
}
