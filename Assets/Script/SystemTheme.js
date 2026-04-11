let ThemeColor = null;
let sameTheme = "default";

let FORCE_DEFAULT_THEME = "dark";

export function MatchsystemThemes(isEnabled = true, ForceDefaultTheme = FORCE_DEFAULT_THEME) {
  const validThemes = ["default", "light", "dark"];
  isEnabled =false;
  if (!isEnabled && ForceDefaultTheme.toLowerCase() !== "default") {
    const themeToApply = validThemes.includes(ForceDefaultTheme.toLowerCase()) ? ForceDefaultTheme.toLowerCase() : "light";
    document.documentElement.setAttribute("data-bs-theme", themeToApply);
    ThemeColor = themeToApply;
    sameTheme = themeToApply;
    return;
  }

  if (!isEnabled) {
    document.documentElement.setAttribute("data-bs-theme", "light");
    ThemeColor = "light";
    sameTheme = "light";
    return;
  }

  const html = document.documentElement;
  if (window.matchMedia("(prefers-color-scheme: dark)").matches) {
    html.setAttribute("data-bs-theme", "dark");
    ThemeColor = "dark";
  } else {
    html.setAttribute("data-bs-theme", "light");
    ThemeColor = "light";
  }
  window.matchMedia("(prefers-color-scheme: dark)").addEventListener("change", (e) => {
    html.setAttribute("data-bs-theme", e.matches ? "dark" : "light");
    ThemeColor = e.matches ? "dark" : "light";
  });
}

export function SwalTheme(isEnabled = true) {
  let ForceDefaultTheme = sameTheme;
  const validThemes = ["default", "light", "dark"];
  if (!isEnabled && ForceDefaultTheme.toLowerCase() !== "default") {
    const themeToApply = validThemes.includes(ForceDefaultTheme.toLowerCase()) ? ForceDefaultTheme.toLowerCase() : "light";
    return themeToApply === "dark" ? "bootstrap-5-dark" : "bootstrap-5-light";
  }

  if (!isEnabled) {
    return "bootstrap-5-light";
  }

  if (ThemeColor === "dark") {
    return "bootstrap-5-dark";
  } else {
    return "bootstrap-5-light";
  }
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
