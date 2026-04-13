// ---- config ----
const maxVisibleToasts = 3;
const maxActiveModals = 1;

// ---- state ----
const toastQueue = [];
const modalQueue = [];
let activeToasts = 0;
let activeModals = 0;

// ---- utils ----
const VALID = {
  themes: ["bootstrap-5-light", "bootstrap-5-dark"],
  icons: ["info", "success", "error", "warning", "question"],
  offsets: ["none", "0", "1", "2", "3", "4", "5", "8"],
  positions: ["top", "top-start", "top-end", "center", "center-start", "center-end", "bottom", "bottom-start", "bottom-end"],
};

function normalize(value, validList, fallback) {
  const v = String(value).toLowerCase();
  return validList.includes(v) ? v : fallback;
}

function getEntryAnimationClass(pos) {
  switch (pos) {
    case "top":
      return "bounce-in-top";
    case "top-start":
    case "center-start":
    case "bottom-start":
      return "bounce-in-left";
    case "top-end":
    case "center-end":
    case "bottom-end":
      return "bounce-in-right";
    case "center":
      return "bounce-in-fwd";
    case "bottom":
      return "bounce-in-bottom";
    default:
      return "bounce-in-right";
  }
}

function getExitAnimationClass(pos) {
  switch (pos) {
    case "top":
      return "slide-out-blurred-top";
    case "top-start":
      return "slide-out-blurred-tr";
    case "top-end":
      return "slide-out-blurred-tl";
    case "center":
      return "slide-out-blurred-bottom";
    case "center-start":
      return "slide-out-blurred-left";
    case "center-end":
      return "slide-out-blurred-right";
    case "bottom":
      return "slide-out-blurred-bottom";
    case "bottom-start":
      return "slide-out-blurred-bl";
    case "bottom-end":
      return "slide-out-blurred-br";
    default:
      return "slide-out-blurred-right";
  }
}

function getToastExitAnimationClass(pos) {
  switch (pos) {
    case "top":
      return "slide-out-blurred-top";
    case "top-start":
    case "center-start":
    case "bottom-start":
      return "slide-out-blurred-left";
    case "top-end":
    case "center-end":
    case "bottom-end":
      return "slide-out-blurred-right";
    case "center":
      return "slide-out-blurred-bottom";
    case "bottom":
      return "slide-out-blurred-bottom";
    default:
      return "slide-out-blurred-right";
  }
}

function hashToast(t) {
  return JSON.stringify({
    i: t.icon,
    t: t.title,
    tm: t.timer,
    p: t.position,
    th: t.theme,
  });
}

// ---- queue processors ----
function processToastQueue() {
  if (activeToasts >= maxVisibleToasts) return;
  if (toastQueue.length === 0) return;

  const { instance, data, hash } = toastQueue.shift();
  activeToasts++;

  instance.fire({
    ...data,
    didClose: () => {
      activeToasts--;
      activeToastHashes.delete(hash);
      processToastQueue();
    },
  });
}

function processModalQueue() {
  if (activeModals >= maxActiveModals) return;
  if (modalQueue.length === 0) return;

  const data = modalQueue.shift();
  activeModals++;

  Swal.fire({
    ...data,
    didClose: () => {
      activeModals--;
      processModalQueue();
    },
  });
}

// ---- dedupe tracking ----
const activeToastHashes = new Set();

// ---- public API ----
export function ToastVersion(theme = "bootstrap-5-light", title = "This is a toast notification!", icon = "info", timer = 3000, position = "top-end", topOffset = "none") {
  let themeToApply = normalize(theme, VALID.themes, "bootstrap-5-light");
  let iconToApply = normalize(icon, VALID.icons, "info");
  let positionToApply = normalize(position, VALID.positions, "top-end");
  let topOffsetToApply = normalize(topOffset, VALID.offsets, "none");

  const isStandalone =
    window.matchMedia("(display-mode: standalone)").matches ||
    window.matchMedia("(display-mode: fullscreen)").matches ||
    window.matchMedia("(display-mode: minimal-ui)").matches ||
    window.matchMedia("(display-mode: window-controls-overlay)").matches ||
    window.navigator.standalone === true;

  if (isStandalone) {
    positionToApply = "top";
  }

  const entryAnimation = getEntryAnimationClass(positionToApply);
  const exitAnimation = getToastExitAnimationClass(positionToApply);

  const data = {
    icon: iconToApply,
    title,
    timer: timer === 0 ? false : timer,
    position: positionToApply,
    theme: themeToApply,
  };

  const hash = hashToast(data);

  if (activeToastHashes.has(hash)) return;

  const instance = Swal.mixin({
    toast: true,
    position: positionToApply,
    showConfirmButton: false,
    timerProgressBar: true,
    showClass: { popup: "" },
    hideClass: { popup: exitAnimation },
    customClass: {
      popup: "bg-blur-5 bg-semi-transparent border-1 rounded-3 fade-bg " + (topOffsetToApply !== "none" ? `mt-${topOffsetToApply}` : ""),
      timerProgressBar: "rounded-3 bg-gradient",
      container: "overflow-hidden",
    },
    didOpen: (toastEl) => {
      toastEl.classList.remove("bounce-in-top", "bounce-in-left", "bounce-in-right", "bounce-in-bottom", "bounce-in-fwd");
      void toastEl.offsetWidth;
      toastEl.classList.add(entryAnimation);
    },
  });

  activeToastHashes.add(hash);
  toastQueue.push({ instance, data, hash });
  processToastQueue();
}

export function ModalVersion(theme = "bootstrap-5-light", title = "This is a modal!", text = "Here is some more information about this modal.", icon = "info", timer = 0, position = "center") {
  const themeToApply = normalize(theme, VALID.themes, "bootstrap-5-light");
  const iconToApply = normalize(icon, VALID.icons, "info");
  const positionToApply = normalize(position, VALID.positions, "center");

  const exitAnimation = getExitAnimationClass(positionToApply);

  modalQueue.push({
    theme: themeToApply,
    title,
    text,
    icon: iconToApply,
    showConfirmButton: false,
    timer: timer === 0 ? false : timer,
    position: positionToApply,
    allowOutsideClick: timer === 0,
    customClass: {
      popup: "bg-blur-5 bg-semi-transparent border-1 rounded-2",
      container: "overflow-hidden",
    },
    hideClass: { popup: exitAnimation },
  });

  processModalQueue();
}
